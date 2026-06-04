<?php

namespace console\controllers;

use common\components\GamesCountRecounter;
use common\components\GameQuery;
use common\components\InstantGaming\IgClient;
use common\models\Game;
use common\models\GameOffer;
use common\models\Store;
use DateTime;
use Symfony\Component\DomCrawler\Crawler;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\mutex\FileMutex;

class SteamController extends Controller
{
    private const string APPDETAILS_URL = 'https://store.steampowered.com/api/appdetails';
    private const string SEARCH_URL = 'https://store.steampowered.com/search/results/';
    private const string APP_LIST_URL = 'http://api.steampowered.com/ISteamApps/GetAppList/v0002';

    private const string LOCK_SYNC         = 'steam/sync';
    private const string LOCK_COMING_SOON  = 'steam/coming-soon';
    private const string LOCK_APP_LIST     = 'steam/create-app-list';
    private const string LOCK_BACKFILL     = 'steam/backfill-offers';

    private const string STEAM_STORE_SLUG = 'steam';

    /**
     * Steam regions to read prices from, keyed by the currency we store. The
     * main appdetails call (us) already carries USD with the full payload, so
     * only the other two cost an extra (price-only) request per game.
     */
    private const array STEAM_PRICE_REGIONS = [
        'USD' => 'us',
        'EUR' => 'de',
        'PLN' => 'pl',
    ];

    private const int SYNC_DELAY_MIN = 2;
    private const int SYNC_DELAY_MAX = 4;
    private const int SEARCH_DELAY_MIN = 5;
    private const int SEARCH_DELAY_MAX = 15;
    private const int SEARCH_PAGE_SIZE = 50;
    private const int SEARCH_MAX_PAGES = 200;

    /**
     * Throttle for the post-sync recount. With a 5-minute cron the recount would
     * otherwise run ~every few minutes; once every 4 hours is plenty for category
     * counts and saves the repeated full-table aggregation.
     */
    private const string RECOUNT_CACHE_KEY = 'steam/last-recount';
    private const int RECOUNT_INTERVAL = 4 * 3600;

    /**
     * Fraction of each sync run reserved for first-time syncs (status =
     * STATUS_WAIT_TO_SYNC). Guarantees new games keep getting synced even when a
     * crawler has flooded the queue with force_sync re-syncs. Unused slots in
     * either bucket spill over to the other, so capacity is never wasted.
     */
    private const float SYNC_NEW_RESERVED_RATIO = 0.4;

    /**
     * Age (in days) after which an already-synced game is considered stale and
     * eligible for a background refresh. These backfill the sync run only when
     * new games and force_sync re-syncs haven't used up the limit, so games no
     * real visitor ever opened (and thus were never force_sync flagged) still
     * get refreshed eventually.
     */
    private const int SYNC_STALE_AFTER_DAYS = 90;

    /**
     * When enabled, actionSync prints per-game progress (appid, title, time).
     * Off by default; turn on with `--verbose=1`.
     */
    public bool $verbose = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if ($actionID === 'sync' || $actionID === 'backfill-offers') {
            $options[] = 'verbose';
        }
        return $options;
    }

    public function actionSync(int $limit = 100): int
    {
        return $this->withLock(self::LOCK_SYNC, fn(): int => $this->runSync($limit));
    }

    private function runSync(int $limit): int
    {
        // Snapshot candidate appids up front instead of using each(): syncing a
        // game removes it from the matching set, which shifts each()'s OFFSET
        // and skips rows mid-iteration. A fixed list guarantees forward progress.
        $appids = $this->collectSyncAppids($limit);
        $total = count($appids);
        $batchStart = microtime(true);
        if ($this->verbose) {
            $this->stdout("Starting sync of {$total} game(s)\n");
        }

        foreach ($appids as $i => $appid) {
            $position = $i + 1;
            $start = microtime(true);
            try {
                $this->actionGetInfo((int)$appid);
                if ($this->verbose) {
                    $elapsed = round(microtime(true) - $start, 2);
                    $title = Game::find()
                        ->select('title')
                        ->andWhere(['steam_appid' => $appid])
                        ->scalar() ?: '(unknown title)';
                    $this->stdout("[{$position}/{$total}] Synced {$appid} \"{$title}\" in {$elapsed}s\n");
                }
            } catch (Exception $e) {
                $elapsed = round(microtime(true) - $start, 2);
                $this->stderr("[{$position}/{$total}] Failed {$appid} after {$elapsed}s: {$e->getMessage()}\n");
            }
            sleep(random_int(self::SYNC_DELAY_MIN, self::SYNC_DELAY_MAX));
        }

        if ($this->verbose) {
            $totalElapsed = round(microtime(true) - $batchStart, 2);
            $this->stdout("Finished syncing {$total} game(s) in {$totalElapsed}s\n");
        }

        if ($total && $this->shouldRecount()) {
            $this->stdout("Recounting games\n");
            (new GamesCountRecounter())->recountAll(['genre', 'tag', 'category', 'publisher', 'developer']);
            $this->stdout("Done\n");
        }

        return ExitCode::OK;
    }

    /**
     * Atomic "once per RECOUNT_INTERVAL" gate. cache->add() only writes (and
     * returns true) when the key is absent; the entry then expires after the
     * interval, so the next run past the window recounts again. Console cache is
     * a persistent FileCache, so the gate survives across cron invocations.
     */
    private function shouldRecount(): bool
    {
        return Yii::$app->cache->add(self::RECOUNT_CACHE_KEY, time(), self::RECOUNT_INTERVAL);
    }

    /**
     * Builds the ordered list of appids to sync for one run, by descending
     * priority and never exceeding $limit:
     *   1. New games (status = STATUS_WAIT_TO_SYNC) up to a reserved share, so a
     *      flood of crawler-triggered force_sync re-syncs can't starve them.
     *   2. force_sync re-syncs (recently viewed / explicitly flagged).
     *   3. Long-stale games (not synced in 90+ days) that were never flagged.
     *   4. Extra new games, so the run still does a full $limit of work when the
     *      higher-priority buckets are small. No slot is wasted.
     *
     * @return int[]
     */
    private function collectSyncAppids(int $limit): array
    {
        // No cap: sync everything, by priority.
        if ($limit <= 0) {
            return array_merge(
                $this->newGamesQuery()->column(),
                $this->forceSyncQuery()->column(),
                $this->staleGamesQuery()->column(),
            );
        }

        // 1. New games up to their reserved share of the run.
        $reservedNew = (int)ceil($limit * self::SYNC_NEW_RESERVED_RATIO);
        $appids = $this->newGamesQuery()->limit($reservedNew)->column();
        $newTaken = count($appids);

        // 2. force_sync re-syncs fill the next slice.
        if (($remaining = $limit - count($appids)) > 0) {
            $appids = array_merge($appids, $this->forceSyncQuery()->limit($remaining)->column());
        }

        // 3. Still room? Backfill with long-stale games no visitor ever flagged.
        if (($remaining = $limit - count($appids)) > 0) {
            $appids = array_merge($appids, $this->staleGamesQuery()->limit($remaining)->column());
        }

        // 4. Anything left goes to extra new games (continue past the reserved
        //    slice) so the run still does a full $limit of work.
        if (($remaining = $limit - count($appids)) > 0) {
            $appids = array_merge($appids, $this->newGamesQuery()
                ->offset($newTaken)
                ->limit($remaining)
                ->column());
        }

        return $appids;
    }

    /** First-time syncs: games added by the app-list / coming-soon crawlers. */
    private function newGamesQuery(): GameQuery
    {
        return Game::find()
            ->select('steam_appid')
            ->andWhere(['status' => Game::STATUS_WAIT_TO_SYNC])
            ->orderBy(['id' => SORT_DESC]);
    }

    /** Already-synced games flagged for a refresh (recently viewed). */
    private function forceSyncQuery(): GameQuery
    {
        return Game::find()
            ->select('steam_appid')
            ->andWhere(['force_sync' => 1])
            ->andWhere(['not', ['status' => Game::STATUS_WAIT_TO_SYNC]])
            ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Active games not refreshed in SYNC_STALE_AFTER_DAYS days and not already
     * force_sync flagged. Oldest first, so the most outdated entries go before
     * the rest. Only used to fill leftover capacity in a run.
     */
    private function staleGamesQuery(): GameQuery
    {
        $cutoff = (new DateTime('-' . self::SYNC_STALE_AFTER_DAYS . ' days'))->format('Y-m-d H:i:s');

        return Game::find()
            ->select('steam_appid')
            ->andWhere(['status' => Game::STATUS_ACTIVE])
            ->andWhere(['force_sync' => 0])
            ->andWhere(['<', 'synchronized_at', $cutoff])
            ->orderBy(['synchronized_at' => SORT_ASC]);
    }

    public function actionGetInfo(int $app_id): int
    {
        $game = Game::findOne(['steam_appid' => $app_id]);

        if (!$game) {
            $this->stderr("no game with id $app_id\n");
            return ExitCode::DATAERR;
        }

        $client = new Client(['baseUrl' => self::APPDETAILS_URL]);
        $request = $client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET')
            ->setData(['appids' => $app_id, 'cc' => 'us']);

        $response = $request->send();

        if (!$response->isOk) {
            throw new Exception(
                "Request to $request->url failed with response: \n"
                . VarDumper::dumpAsString($response->data)
            );
        }

        if (empty($response->data[$app_id]['success'])) {
            $game->status = Game::STATUS_SUCCESS_FALSE;
            $game->force_sync = false;
            $game->synchronized_at = date('Y-m-d H:i:s');
            $game->save(false);
            return ExitCode::OK;
        }

        $game->setBaseInformation($response->data[$app_id]['data']);

        // Model Steam as a first-class offer (its own store row) so the cheapest
        // offer / deal rankings can include it. The us payload already has USD;
        // EUR/PLN come from two small price-only calls.
        $this->syncSteamOffer($game, $response->data[$app_id]['data']);

        return ExitCode::OK;
    }

    /**
     * Upserts the Steam store offer for a game, with prices in each currency we
     * support. Mirrors how keyshops build their offers (see
     * {@see InstantGamingController::saveOffer()}). Free games get no offer —
     * there is nothing to buy.
     *
     * @param array<string, mixed> $usData the appdetails payload already fetched
     *                                      with cc=us (carries the USD price).
     */
    private function syncSteamOffer(Game $game, array $usData): void
    {
        if ((int)$game->is_free === 1) {
            return;
        }

        $store = Store::findOne(['slug' => self::STEAM_STORE_SLUG]);
        if (!$store) {
            return; // migration not run yet — skip silently
        }

        // USD is free (already fetched); EUR/PLN need their own region call.
        $prices = ['USD' => $this->extractPriceOverview($usData)];
        foreach (self::STEAM_PRICE_REGIONS as $currency => $cc) {
            if ($currency === 'USD') {
                continue;
            }
            $prices[$currency] = $this->fetchSteamPrice((int)$game->steam_appid, $cc);
            sleep(1);
        }

        // Nothing priced anywhere (unreleased / region-locked) — no offer to make.
        if (array_filter($prices) === []) {
            return;
        }

        $offer = GameOffer::findOne(['game_id' => $game->id, 'store_id' => $store->id])
            ?? new GameOffer(['game_id' => $game->id, 'store_id' => $store->id]);
        $offer->url = 'https://store.steampowered.com/app/' . $game->steam_appid;
        $offer->region = 'Worldwide';
        $offer->status = GameOffer::STATUS_ACTIVE;

        if (!$offer->save()) {
            $this->stderr("  ! could not save Steam offer for {$game->title}: " . json_encode($offer->getErrors()) . "\n");
            return;
        }

        foreach ($prices as $currency => $price) {
            if ($price === null) {
                continue;
            }
            $offer->setPrice($currency, $price['final'], $price['initial']);
        }
    }

    /**
     * Reads Steam's price_overview into our minor-unit shape, or null when the
     * game has no usable price. `initial` is only kept when it's a real
     * pre-discount price (greater than final).
     *
     * @param array<string, mixed> $data appdetails `data` node
     * @return array{final:int,initial:int|null}|null
     */
    private function extractPriceOverview(array $data): ?array
    {
        $po = $data['price_overview'] ?? null;
        $final = (int)($po['final'] ?? 0);
        if (!$po || $final <= 0) {
            return null;
        }

        $initial = (int)($po['initial'] ?? 0);

        return [
            'final'   => $final,
            'initial' => $initial > $final ? $initial : null,
        ];
    }

    /**
     * Price-only appdetails call for one region, so EUR/PLN prices reflect
     * Steam's real regional pricing rather than an FX conversion of USD.
     *
     * @return array{final:int,initial:int|null}|null
     */
    private function fetchSteamPrice(int $appid, string $cc): ?array
    {
        $client = new Client(['baseUrl' => self::APPDETAILS_URL]);
        $response = $client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET')
            ->setData(['appids' => $appid, 'cc' => $cc, 'filters' => 'price_overview'])
            ->send();

        if (!$response->isOk || empty($response->data[$appid]['success'])) {
            return null;
        }

        return $this->extractPriceOverview($response->data[$appid]['data'] ?? []);
    }

    /**
     * One-off bootstrap: create Steam offers for games we've already synced,
     * from the stored USD `steam_price_final`, FX-converting to EUR/PLN with
     * IG's published rates so the deal board has data immediately. The paced
     * sync later overwrites these with true regional prices.
     *
     * @param int $limit max games to process (0 = no limit)
     */
    public function actionBackfillOffers(int $limit = 0): int
    {
        return $this->withLock(self::LOCK_BACKFILL, fn(): int => $this->runBackfillOffers($limit));
    }

    private function runBackfillOffers(int $limit): int
    {
        $store = Store::findOne(['slug' => self::STEAM_STORE_SLUG]);
        if (!$store) {
            $this->stderr("Store 'steam' not found — run migrations first.\n");
            return ExitCode::DATAERR;
        }

        // Keep memory flat over a catalogue-sized run: the query log and profiler
        // would otherwise accumulate a message per statement until the process is
        // OOM-killed.
        $db = Yii::$app->db;
        $db->enableLogging = false;
        $db->enableProfiling = false;

        // EUR-based rates (EUR => 1.0, USD => …, PLN => …) as keyshops use them.
        $rates = (new IgClient())->fetchRates();
        $usdRate = (float)($rates['USD'] ?? 0);
        $plnRate = (float)($rates['PLN'] ?? 0);

        // Keyset pagination by id: only one batch of models is held at a time, and
        // (unlike an unbuffered each()) we can still run the per-row writes below
        // on the same connection. Each batch is released before the next is read.
        $batchSize = 200;
        $lastId = PHP_INT_MAX;
        $created = 0;

        while (true) {
            $games = Game::find()
                ->where(['status' => Game::STATUS_ACTIVE, 'type' => Game::TYPE_GAME])
                ->andWhere(['or', ['is_free' => 0], ['is_free' => null]])
                ->andWhere(['>', 'steam_price_final', 0])
                ->andWhere(['<', 'id', $lastId])
                ->orderBy(['id' => SORT_DESC])
                ->limit($batchSize)
                ->all();

            if ($games === []) {
                break;
            }

            foreach ($games as $game) {
                $lastId = (int)$game->id;

                $finalUsd = (int)$game->steam_price_final;
                $initialUsd = (int)$game->steam_price_initial;
                $initialUsd = $initialUsd > $finalUsd ? $initialUsd : 0;

                $offer = GameOffer::findOne(['game_id' => $game->id, 'store_id' => $store->id])
                    ?? new GameOffer(['game_id' => $game->id, 'store_id' => $store->id]);
                $offer->url = 'https://store.steampowered.com/app/' . $game->steam_appid;
                $offer->region = 'Worldwide';
                $offer->status = GameOffer::STATUS_ACTIVE;
                if (!$offer->save()) {
                    continue;
                }

                // USD straight from the catalogue; EUR via USD→EUR, PLN via EUR→PLN.
                $offer->setPrice('USD', $finalUsd, $initialUsd ?: null);

                if ($usdRate > 0) {
                    $finalEur = $finalUsd / $usdRate;
                    $initialEur = $initialUsd > 0 ? $initialUsd / $usdRate : 0.0;
                    $offer->setPrice('EUR', (int)round($finalEur), $initialEur > 0 ? (int)round($initialEur) : null);

                    if ($plnRate > 0) {
                        $offer->setPrice(
                            'PLN',
                            (int)round($finalEur * $plnRate),
                            $initialEur > 0 ? (int)round($initialEur * $plnRate) : null
                        );
                    }
                }

                $created++;
                if ($limit > 0 && $created >= $limit) {
                    $this->stdout("Steam backfill — offers written: {$created}\n");
                    return ExitCode::OK;
                }
            }

            // Release per-batch buildup (Yii identity map / any buffered log).
            Yii::getLogger()->flush();
            gc_collect_cycles();

            if ($this->verbose) {
                $this->stdout("… {$created} Steam offers\n");
            }
        }

        $this->stdout("Steam backfill — offers written: {$created}\n");
        return ExitCode::OK;
    }

    public function actionGetComingSoon(): int
    {
        return $this->withLock(self::LOCK_COMING_SOON, fn(): int => $this->runGetComingSoon());
    }

    private function runGetComingSoon(): int
    {
        $client = new Client(['baseUrl' => self::SEARCH_URL]);

        $start = 0;
        $page = 0;
        $query = [
            'query'              => '',
            'count'              => self::SEARCH_PAGE_SIZE,
            'dynamic_data'       => '',
            'sort_by'            => 'Released_ASC',
            'ignore_preferences' => 1,
            'os'                 => 'win',
            'filter'             => 'comingsoon',
            'infinite'           => 1,
            'cc'                 => 'us',
        ];

        do {
            $query['start'] = $start;

            $request = $client->createRequest()
                ->setHeaders(['Content-language' => 'en'])
                ->setMethod('GET')
                ->setData($query);

            $response = $request->send();

            if (!$response->isOk || empty($response->data['success'])) {
                $this->stderr("comingsoon request failed at start={$start}\n");
                break;
            }

            $crawler = new Crawler($response->data['results_html']);
            $appidGroups = $crawler->filter('a[data-ds-appid]')->extract(['data-ds-appid']);

            foreach ($appidGroups as $group) {
                foreach (explode(',', $group) as $appid) {
                    $appid = trim($appid);
                    if ($appid === '') {
                        continue;
                    }
                    $this->stdout("$appid\n");

                    if (!Game::find()->where(['steam_appid' => $appid])->exists()) {
                        $game = new Game();
                        $game->steam_appid = (int)$appid;
                        $game->save(false);
                    }
                }
            }

            $total = (int)($response->data['total_count'] ?? 0);
            $start += self::SEARCH_PAGE_SIZE;
            $page++;

            if ($page >= self::SEARCH_MAX_PAGES) {
                $this->stderr("hit SEARCH_MAX_PAGES safety cap at start={$start}, total={$total}\n");
                break;
            }

            if ($start < $total) {
                sleep(random_int(self::SEARCH_DELAY_MIN, self::SEARCH_DELAY_MAX));
            }
        } while ($start < $total);

        (new GamesCountRecounter())->recountAll(['genre', 'tag', 'category', 'publisher', 'developer']);

        return ExitCode::OK;
    }

    public function actionCreateAppList(): int
    {
        return $this->withLock(self::LOCK_APP_LIST, fn(): int => $this->runCreateAppList());
    }

    private function runCreateAppList(): int
    {
        $client = new Client(['baseUrl' => self::APP_LIST_URL]);

        $request = $client->createRequest()
            ->setMethod('GET')
            ->setData([
                'key'    => Yii::$app->params['steamkey'],
                'format' => 'json',
            ]);

        $response = $request->send();

        if (!$response->isOk) {
            $this->stderr("GetAppList request failed\n");
            return ExitCode::TEMPFAIL;
        }

        $existing = array_flip(Game::find()
            ->select('steam_appid')
            ->where(['is not', 'steam_appid', null])
            ->column()
        );
        $newCount = 0;

        foreach ($response->data['applist']['apps'] as $app) {
            if (isset($existing[$app['appid']])) {
                continue;
            }

            $game = new Game();
            $game->steam_appid = (int)$app['appid'];
            $game->title = $app['name'];

            if ($game->save()) {
                $existing[$app['appid']] = true;
                $newCount++;
                $this->stdout("Nowa gra {$app['name']}\n");
            }
        }

        $this->stdout("Dodano nowych gier: {$newCount}\n");
        return ExitCode::OK;
    }

    /**
     * Runs $work while holding a named file lock, so overlapping cron invocations
     * don't hit Steam in parallel. A busy lock is a no-op (exit OK), not an
     * error — the next scheduled run will pick up where this one left off.
     *
     * @param callable():int $work
     */
    private function withLock(string $name, callable $work): int
    {
        $mutex = new FileMutex();

        if (!$mutex->acquire($name)) {
            $this->stdout("{$name}: another run is already in progress — skipping.\n");
            return ExitCode::OK;
        }

        try {
            return $work();
        } finally {
            $mutex->release($name);
        }
    }
}