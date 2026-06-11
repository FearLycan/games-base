<?php

namespace console\controllers;

use common\components\Kinguin\KinguinClient;
use common\components\Kinguin\KinguinMatcher;
use common\models\Game;
use common\models\GameOffer;
use common\models\GameStoreScan;
use common\models\GameVideo;
use common\models\Store;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\mutex\FileMutex;

/**
 * Builds and refreshes Kinguin offers for our games.
 *
 * Two phases, meant to run on different cadences:
 *   - `match`         (heavy, rare): search Kinguin by title, match by Steam appid
 *                     (with a title/edition guard), create offers. Confident
 *                     region-free matches are published; uncertain ones
 *                     (region-locked) are saved with STATUS_REVIEW and stay hidden
 *                     until checked.
 *   - `refresh-prices`(light, frequent): re-read price/stock for offers we already
 *                     matched, by their stored kinguinId — one precise API call
 *                     each, no re-search.
 *
 * Kinguin's catalogue price is EUR; we convert to each configured currency with
 * ECB reference rates and store them in {{%game_offer_price}}. The API exposes no
 * RRP, so there's no struck-through "initial" price — only the live final price.
 * See {@see KinguinClient} / {@see KinguinMatcher}.
 */
class KinguinController extends Controller
{
    private const string STORE_SLUG = 'kinguin';

    private const string LOCK_MATCH   = 'kinguin/match';
    private const string LOCK_REFRESH = 'kinguin/refresh-prices';
    private const string LOCK_ENRICH  = 'kinguin/enrich-media';

    private const int DELAY_MIN = 2;
    private const int DELAY_MAX = 5;

    public bool $verbose = false;

    /** Ignore the cooldown and re-scan games checked recently (incl. past misses). */
    public bool $recheck = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['verbose', 'recheck']);
    }

    /**
     * Match our games to Kinguin products and create offers.
     *
     * @param int $limit max games to process (0 = no limit)
     */
    public function actionMatch(int $limit = 100): int
    {
        return $this->withLock(self::LOCK_MATCH, fn(): int => $this->runMatch($limit));
    }

    private function runMatch(int $limit): int
    {
        $store = Store::findOne(['slug' => self::STORE_SLUG]);
        if (!$store) {
            $this->stderr("Store '" . self::STORE_SLUG . "' not found — run migrations first.\n");
            return ExitCode::DATAERR;
        }

        $client = new KinguinClient();
        $matcher = new KinguinMatcher();
        $rates = $client->fetchRates();
        $currencies = $this->currencies();

        if ($client->lastError !== null || count($rates) <= 1) {
            $this->stderr('  ! Kinguin rates feed problem: '
                . ($client->lastError ?? 'only EUR returned')
                . " — non-EUR prices may be skipped. Run `yii kinguin/diagnose` to check.\n");
        }

        // Priced, real games without a Kinguin offer that aren't still serving a
        // miss-backoff (--recheck ignores the backoff and re-scans everything).
        $query = Game::find()->keyshopMatchCandidates($store->id, $this->recheck);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $games = $query->all();
        $matched = $review = $missed = $errors = 0;

        foreach ($games as $game) {
            $hits = $client->search((string)$game->title);
            $searchError = $client->lastError;
            if ($searchError !== null) {
                $errors++;
            }
            $result = $matcher->match((string)$game->title, $game->steam_appid !== null ? (int)$game->steam_appid : null, $hits);

            if ($result === null) {
                $missed++;
                GameStoreScan::record($game->id, $store->id, GameStoreScan::RESULT_NO_MATCH);
                if ($this->verbose) {
                    $reason = $searchError ?? (count($hits) . ' hits, no appid/title match');
                    $this->stdout("· no match: {$game->title} ({$reason})\n");
                }
                $this->throttle();
                continue;
            }

            // A match (confident or review) lives in game_offer; no scan row —
            // game_offer already excludes it from the candidate set above.
            $confident = $result['confidence'] === KinguinMatcher::CONFIDENCE_HIGH;
            $this->saveOffer($store, $game, $result['hit'], $client, $rates, $currencies, $confident);

            if ($confident) {
                $matched++;
            } else {
                $review++;
            }

            if ($this->verbose) {
                $flag = $confident ? 'OK ' : 'REVIEW';
                $region = $result['hit']['regionalLimitations'] ?? '?';
                $this->stdout("{$flag} {$game->title} → #{$result['hit']['kinguinId']} ({$region})\n");
            }

            $this->throttle();
        }

        $this->stdout(sprintf("Kinguin match — matched=%d review=%d missed=%d errors=%d\n", $matched, $review, $missed, $errors));
        if ($errors > 0) {
            $this->stderr("  ! {$errors} search request(s) failed — check the API key / rate limit. "
                . "Run `yii kinguin/diagnose \"<a title you know exists>\"` for details.\n");
        }
        return ExitCode::OK;
    }

    /**
     * Diagnoses connectivity to the Kinguin API from *this* machine — use it when
     * every game comes back "no match" to tell a bad key / rate-limit apart from a
     * genuine matching gap. Prints the raw HTTP status and a body snippet for both
     * the product search and the ECB rates feed.
     *
     * Example: yii kinguin/diagnose "Elden Ring"
     */
    public function actionDiagnose(string $title = 'Elden Ring'): int
    {
        $client = new KinguinClient();

        $this->stdout("Kinguin diagnostics (from this server)\n");
        $this->stdout(str_repeat('-', 60) . "\n");

        // 1) ECB rates feed.
        $rates = $client->fetchRates();
        $ratesOk = $client->lastError === null && count($rates) > 1;
        $this->stdout(sprintf(
            "rates feed : %s (%d rates%s)\n",
            $ratesOk ? 'OK' : 'PROBLEM',
            count($rates),
            $client->lastError ? '; ' . $client->lastError : ''
        ));
        $this->stdout(sprintf(
            "             EUR=%s USD=%s PLN=%s\n",
            $rates['EUR'] ?? '-', $rates['USD'] ?? '-', $rates['PLN'] ?? '-'
        ));

        // 2) Product search.
        $d = $client->diagnoseSearch($title);
        $this->stdout(sprintf(
            "search     : %s  http=%d  type=%s  bytes=%d  returned=%d%s\n",
            $d['ok'] ? 'OK' : 'PROBLEM',
            $d['status'],
            $d['contentType'] !== '' ? $d['contentType'] : '-',
            $d['length'],
            $d['returned'],
            $d['error'] ? '  error=' . $d['error'] : ''
        ));
        if (!$d['ok'] || $d['returned'] === 0) {
            $this->stdout('  body snippet: ' . ($d['snippet'] !== '' ? $d['snippet'] : '(empty)') . "\n");
        }

        // 3) What the matcher sees for the title (appid unknown in this ad-hoc run).
        $hits = $client->search($title);
        $result = (new KinguinMatcher())->match($title, null, $hits);
        if ($result === null) {
            $this->stdout('matcher    : NO MATCH among ' . count($hits) . " steam hits\n");
            foreach (array_slice($hits, 0, 8) as $hit) {
                $this->stdout(sprintf(
                    "  · [steam=%s/%s] %s — %s€\n",
                    $hit['steam'] ?? '?',
                    $hit['regionalLimitations'] ?? '?',
                    $hit['name'] ?? '?',
                    $hit['price'] ?? '?'
                ));
            }
        } else {
            $hit = $result['hit'];
            $this->stdout(sprintf(
                "matcher    : %s → #%s steam=%s | %s | %s€\n",
                $result['confidence'],
                $hit['kinguinId'] ?? '?',
                $hit['steam'] ?? '?',
                $hit['regionalLimitations'] ?? '?',
                $hit['price'] ?? '?'
            ));
        }

        $this->stdout(str_repeat('-', 60) . "\n");
        $blocked = !$d['ok'] || $d['status'] === 0;
        $this->stdout($blocked
            ? "VERDICT: this server can't reach the Kinguin API or the key is rejected (see above).\n"
            : "VERDICT: connectivity to the Kinguin API is OK from this server.\n");

        return ExitCode::OK;
    }

    /**
     * Refresh price/stock for offers we already matched.
     *
     * @param int $limit max offers to refresh (0 = no limit)
     */
    public function actionRefreshPrices(int $limit = 150): int
    {
        return $this->withLock(self::LOCK_REFRESH, fn(): int => $this->runRefreshPrices($limit));
    }

    private function runRefreshPrices(int $limit): int
    {
        $store = Store::findOne(['slug' => self::STORE_SLUG]);
        if (!$store) {
            $this->stderr("Store '" . self::STORE_SLUG . "' not found — run migrations first.\n");
            return ExitCode::DATAERR;
        }

        $client = new KinguinClient();
        $matcher = new KinguinMatcher();
        $rates = $client->fetchRates();
        $currencies = $this->currencies();

        $query = GameOffer::find()
            ->with('game')
            ->where(['store_id' => $store->id])
            ->andWhere(['not', ['external_id' => null]])
            ->orderBy(['updated_at' => SORT_ASC]);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $updated = $gone = 0;

        foreach ($query->all() as $offer) {
            $hit = $client->fetchProduct((string)$offer->external_id);

            // Disappeared from the catalogue, or no longer a buyable Steam key —
            // hide it and bump updated_at so it rotates to the back of the queue
            // instead of being re-checked every run.
            if ($hit === null || !$matcher->isBuyable($hit)) {
                $gone++;
                $offer->status = GameOffer::STATUS_INACTIVE;
                $offer->save(false);
                if ($this->verbose) {
                    $this->stdout("? gone: #{$offer->external_id} ({$offer->game->title})\n");
                }
                $this->throttle();
                continue;
            }

            $this->applyPrices($offer, $hit, $rates, $currencies);
            if ($offer->game !== null) {
                $this->persistMedia($offer->game, $hit);
            }
            $offer->region = $hit['regionalLimitations'] ?? $offer->region;
            $offer->url = $client->buildProductUrl($hit);

            // Revive an offer we'd previously marked gone — re-publish based on the
            // current region (region-free = confident). Offers an admin set
            // (ACTIVE/REVIEW) are left untouched.
            if ((int)$offer->status === GameOffer::STATUS_INACTIVE) {
                $region = strtolower((string)($hit['regionalLimitations'] ?? ''));
                $offer->status = $region === 'region free'
                    ? GameOffer::STATUS_ACTIVE
                    : GameOffer::STATUS_REVIEW;
            }

            $offer->save(false);
            $updated++;

            $this->throttle();
        }

        $this->stdout(sprintf("Kinguin refresh — updated=%d gone=%d\n", $updated, $gone));
        return ExitCode::OK;
    }

    /**
     * Backfill (and keep fresh) trailers + the pre-order flag for games we
     * already matched on Kinguin, by re-reading each product once. Kinguin is
     * the only source that hands us a YouTube trailer id, so this is what lights
     * up the homepage "In motion" strip and the "Pre-order" badge. Cheap to
     * re-run: videos upsert, the flag only writes on change.
     *
     * @param int $limit max offers to process (0 = all)
     */
    public function actionEnrichMedia(int $limit = 0): int
    {
        return $this->withLock(self::LOCK_ENRICH, fn(): int => $this->runEnrichMedia($limit));
    }

    private function runEnrichMedia(int $limit): int
    {
        $store = Store::findOne(['slug' => self::STORE_SLUG]);
        if (!$store) {
            $this->stderr("Store '" . self::STORE_SLUG . "' not found — run migrations first.\n");
            return ExitCode::DATAERR;
        }

        $client = new KinguinClient();

        $query = GameOffer::find()
            ->with('game')
            ->where(['store_id' => $store->id])
            ->andWhere(['not', ['external_id' => null]])
            ->orderBy(['updated_at' => SORT_ASC]);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $videos = $preorders = $gone = 0;

        foreach ($query->each() as $offer) {
            if ($offer->game === null) {
                continue;
            }

            $hit = $client->fetchProduct((string)$offer->external_id);
            if ($hit === null) {
                $gone++;
                $this->throttle();
                continue;
            }

            $stats = $this->persistMedia($offer->game, $hit);
            $videos += $stats['videos'];
            $preorders += $stats['preorder'];

            if ($this->verbose && ($stats['videos'] > 0 || $stats['preorder'] > 0)) {
                $this->stdout(sprintf(
                    "+ %s — %d video(s)%s\n",
                    $offer->game->title,
                    $stats['videos'],
                    $stats['preorder'] ? ', pre-order' : ''
                ));
            }

            $this->throttle();
        }

        $this->stdout(sprintf("Kinguin enrich-media — videos=%d preorders=%d gone=%d\n", $videos, $preorders, $gone));
        return ExitCode::OK;
    }

    /**
     * Persists a product's trailers and pre-order flag onto the game. Videos are
     * upserted by (game_id, provider, video_id) so re-runs don't duplicate; the
     * pre-order flag is written only when it actually changes (no needless
     * updated_at churn). Returns counts for the caller's summary.
     *
     * @param array<string, mixed> $hit
     * @return array{videos: int, preorder: int}
     */
    private function persistMedia(Game $game, array $hit): array
    {
        $savedVideos = 0;

        foreach (($hit['videos'] ?? []) as $i => $video) {
            $videoId = trim((string)($video['video_id'] ?? ''));
            $url = trim((string)($video['video_url'] ?? ''));
            if ($videoId === '' || $url === '') {
                continue;
            }

            $row = GameVideo::findOne([
                'game_id'  => $game->id,
                'provider' => GameVideo::PROVIDER_YOUTUBE,
                'video_id' => $videoId,
            ]) ?? new GameVideo([
                'game_id'  => $game->id,
                'provider' => GameVideo::PROVIDER_YOUTUBE,
                'video_id' => $videoId,
            ]);

            $row->url = $url;
            $row->position = (int)$i;
            $row->status = GameVideo::STATUS_ACTIVE;

            if ($row->save()) {
                $savedVideos++;
            } else {
                $this->stderr("  ! video save failed for {$game->title}: " . json_encode($row->getErrors()) . "\n");
            }
        }

        $isPreorder = !empty($hit['isPreorder']);
        $preorderChanged = (bool)$game->is_preorder !== $isPreorder;
        if ($preorderChanged) {
            $game->is_preorder = $isPreorder;
            $game->save(false, ['is_preorder']);
        }

        return ['videos' => $savedVideos, 'preorder' => ($isPreorder && $preorderChanged) ? 1 : 0];
    }

    /**
     * @param array<string, mixed>  $hit
     * @param array<string, float>  $rates
     * @param array<int, string>    $currencies
     */
    private function saveOffer(
        Store $store,
        Game $game,
        array $hit,
        KinguinClient $client,
        array $rates,
        array $currencies,
        bool $confident
    ): bool {
        $offer = GameOffer::findOne(['game_id' => $game->id, 'store_id' => $store->id])
            ?? new GameOffer(['game_id' => $game->id, 'store_id' => $store->id]);

        $offer->external_id = (string)($hit['kinguinId'] ?? '');
        $offer->region = $hit['regionalLimitations'] ?? null;
        $offer->url = $client->buildProductUrl($hit);
        $offer->status = $confident ? GameOffer::STATUS_ACTIVE : GameOffer::STATUS_REVIEW;

        if (!$offer->save()) {
            $this->stderr("  ! could not save offer for {$game->title}: " . json_encode($offer->getErrors()) . "\n");
            return false;
        }

        $this->applyPrices($offer, $hit, $rates, $currencies);

        return true;
    }

    /**
     * Converts the EUR catalogue price into each currency and stores it. Kinguin
     * exposes no RRP, so the price is stored as the final price with no
     * struck-through initial (we never invent a discount).
     *
     * @param array<string, mixed> $hit
     * @param array<string, float> $rates
     * @param array<int, string>   $currencies
     */
    private function applyPrices(GameOffer $offer, array $hit, array $rates, array $currencies): void
    {
        $priceEur = (float)($hit['price'] ?? 0);

        foreach ($currencies as $currency) {
            $rate = $rates[$currency] ?? null;
            if ($rate === null || $priceEur <= 0) {
                continue; // no rate or no price — don't write a bogus value
            }

            $offer->setPrice($currency, (int)round($priceEur * $rate * 100), null);
        }
    }

    /**
     * @return array<int, string>
     */
    private function currencies(): array
    {
        return Yii::$app->params['kinguin']['currencies'] ?? ['EUR', 'USD', 'PLN'];
    }

    private function throttle(): void
    {
        sleep(random_int(self::DELAY_MIN, self::DELAY_MAX));
    }

    /**
     * Runs $work while holding a named file lock, so overlapping cron invocations
     * don't hit the store in parallel. A busy lock is a no-op (exit OK), not an
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
