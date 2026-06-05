<?php

namespace console\controllers;

use common\components\InstantGaming\IgClient;
use common\components\InstantGaming\IgMatcher;
use common\models\Game;
use common\models\GameOffer;
use common\models\GameSale;
use common\models\GameStoreScan;
use common\models\Store;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\mutex\FileMutex;

/**
 * Builds and refreshes Instant Gaming offers for our games.
 *
 * Two phases, meant to run on different cadences:
 *   - `match`         (heavy, rare): search IG by title, match, create offers.
 *                     Confident worldwide matches are published; uncertain ones
 *                     are saved with STATUS_REVIEW and stay hidden until checked.
 *   - `refresh-prices`(light, frequent): re-read prices for offers we already
 *                     matched, by their stored product id.
 *
 * Prices: the IG catalogue price is EUR; we convert to each configured currency
 * with the rates IG publishes, and store them in {{%game_offer_price}}.
 */
class InstantGamingController extends Controller
{
    private const string STORE_SLUG = 'instant-gaming';

    private const string LOCK_MATCH   = 'instant-gaming/match';
    private const string LOCK_REFRESH = 'instant-gaming/refresh-prices';
    private const string LOCK_LIST    = 'instant-gaming/list/';

    /**
     * IG listing pages we mirror onto the homepage, each into its own
     * {{%game_sale}} type. Adding a list is one entry here (plus a GameSale type
     * constant and a homepage section) — no new tables or import code. IgClient
     * handles both the inlined-Algolia pages and the HTML-card ones (bestsellers).
     *
     * @var array<string, array{url:string, type:int}>
     */
    private const array LISTS = [
        'trending'    => ['url' => 'https://www.instant-gaming.com/en/trending/',    'type' => GameSale::TYPE_IG_TRENDING],
        'pre-orders'  => ['url' => 'https://www.instant-gaming.com/en/pre-orders/',  'type' => GameSale::TYPE_IG_PREORDERS],
        'bestsellers' => ['url' => 'https://www.instant-gaming.com/en/bestsellers/', 'type' => GameSale::TYPE_IG_BESTSELLERS],
    ];

    private const int DELAY_MIN = 3;
    private const int DELAY_MAX = 7;

    public bool $verbose = false;

    /** Ignore the cooldown and re-scan games checked recently (incl. past misses). */
    public bool $recheck = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['verbose', 'recheck']);
    }

    /**
     * Match our games to IG products and create offers.
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

        $client = new IgClient();
        $matcher = new IgMatcher();
        $rates = $client->fetchRates();
        $currencies = $this->currencies();

        // Priced, real games without an IG offer that aren't still serving a
        // miss-backoff (--recheck ignores the backoff and re-scans everything).
        $query = Game::find()->keyshopMatchCandidates($store->id, $this->recheck);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $games = $query->all();
        $matched = $review = $missed = 0;

        foreach ($games as $game) {
            $hits = $client->search((string)$game->title);
            $result = $matcher->match((string)$game->title, $hits);

            if ($result === null) {
                $missed++;
                GameStoreScan::record($game->id, $store->id, GameStoreScan::RESULT_NO_MATCH);
                if ($this->verbose) {
                    $this->stdout("· no match: {$game->title}\n");
                }
                $this->throttle();
                continue;
            }

            // A match (confident or review) lives in game_offer; no scan row —
            // game_offer already excludes it from the candidate set above.
            $confident = $result['confidence'] === IgMatcher::CONFIDENCE_HIGH;
            $this->saveOffer($store, $game, $result['hit'], $client, $rates, $currencies, $confident);

            if ($confident) {
                $matched++;
            } else {
                $review++;
            }

            if ($this->verbose) {
                $flag = $confident ? 'OK ' : 'REVIEW';
                $this->stdout("{$flag} {$game->title} → #{$result['hit']['prod_id']} ({$result['hit']['region']})\n");
            }

            $this->throttle();
        }

        $this->stdout(sprintf("Instant Gaming match — matched=%d review=%d missed=%d\n", $matched, $review, $missed));
        return ExitCode::OK;
    }

    /**
     * Refresh prices for offers we already matched.
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

        $client = new IgClient();
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
            $hit = $this->findHitByProdId($client, (string)($offer->game->title ?? ''), (int)$offer->external_id);

            if ($hit === null) {
                $gone++;
                // Hide the dead offer and bump updated_at so it rotates to the
                // back of the queue instead of being re-checked every run.
                $offer->status = GameOffer::STATUS_INACTIVE;
                $offer->save(false);
                if ($this->verbose) {
                    $this->stdout("? gone: #{$offer->external_id} ({$offer->game->title})\n");
                }
                $this->throttle();
                continue;
            }

            $this->applyPrices($offer, $hit, $rates, $currencies);
            $offer->region = $hit['region'] ?? $offer->region;
            $offer->url = $client->buildProductUrl($hit);

            // Revive an offer we'd previously marked gone — re-publish based on
            // the current region (Worldwide = confident). Offers an admin set
            // (ACTIVE/REVIEW) are left untouched.
            if ((int)$offer->status === GameOffer::STATUS_INACTIVE) {
                $offer->status = strtolower((string)($hit['region'] ?? '')) === 'worldwide'
                    ? GameOffer::STATUS_ACTIVE
                    : GameOffer::STATUS_REVIEW;
            }

            $offer->save(false);
            $updated++;

            $this->throttle();
        }

        $this->stdout(sprintf("Instant Gaming refresh — updated=%d gone=%d\n", $updated, $gone));
        return ExitCode::OK;
    }

    /**
     * Imports one IG listing page (see {@see LISTS}) into {{%game_sale}} as a
     * ranked homepage section.
     *
     * Matching, in order of confidence:
     *   - by IG product id against an offer we already matched on this store —
     *     unambiguous, published straight away (ACTIVE);
     *   - else by title via {@see \common\components\GameQuery::onlyWithTitle()} —
     *     fuzzy, so it lands in REVIEW for an admin to accept or reject.
     *
     * Re-runs honour admin decisions: an accepted row stays ACTIVE, a rejected one
     * stays INACTIVE, and a pending one is only ever upgraded to ACTIVE once a
     * confident id match appears. Games that drop out of the live list are removed.
     *
     * @param string $list one of the keys in {@see LISTS}, e.g. "trending"
     */
    public function actionList(string $list): int
    {
        $config = self::LISTS[$list] ?? null;
        if ($config === null) {
            $this->stderr("Unknown list '{$list}'. Known: " . implode(', ', array_keys(self::LISTS)) . "\n");
            return ExitCode::USAGE;
        }

        return $this->withLock(self::LOCK_LIST . $list, fn(): int => $this->runList($list, $config));
    }

    /**
     * @param array{url:string, type:int} $config
     */
    private function runList(string $list, array $config): int
    {
        $store = Store::findOne(['slug' => self::STORE_SLUG]);
        if (!$store) {
            $this->stderr("Store '" . self::STORE_SLUG . "' not found — run migrations first.\n");
            return ExitCode::DATAERR;
        }

        $client = new IgClient();
        $hits = $client->fetchList($config['url']);
        if (!$hits) {
            // A transient empty fetch (network blip, markup change) must not wipe a
            // good section — keep what we have and try again next run.
            $this->stderr("No items parsed for '{$list}' — keeping existing data.\n");
            return ExitCode::OK;
        }

        $rates = $client->fetchRates();
        $currencies = $this->currencies();

        // game_id => external_id (IG prod_id), in display order. A game claimed by a
        // higher hit (e.g. base game before its Deluxe edition) keeps its position.
        $desired = [];
        $matched = $review = $missed = 0;

        foreach ($hits as $hit) {
            $result = $this->matchListHit((int)$store->id, $hit);

            if ($result === null) {
                $missed++;
                if ($this->verbose) {
                    $this->stdout("· no match: {$hit['name']}\n");
                }
                continue;
            }

            if (isset($desired[$result['game_id']])) {
                continue;
            }

            $desired[$result['game_id']] = (string)$hit['prod_id'];
            $result['confident'] ? $matched++ : $review++;

            // Give the matched game a real IG offer (price + affiliate URL) straight
            // from the listing payload — no extra Algolia call. The offer is the
            // single publish gate: a confident id match is ACTIVE (and the game shows
            // in the rail at once), a title match is REVIEW (it surfaces in the Offers
            // queue and only reaches the rail once accepted there).
            $this->upsertListOffer($store, (int)$result['game_id'], $hit, $result['confident'], $client, $rates, $currencies);

            if ($this->verbose) {
                $flag = $result['confident'] ? 'LIVE  ' : 'REVIEW';
                $this->stdout("{$flag} #{$hit['prod_id']} {$hit['name']} → game {$result['game_id']}\n");
            }
        }

        $this->syncList((int)$config['type'], $desired);

        $this->stdout(sprintf(
            "Instant Gaming list '%s' — matched=%d review=%d missed=%d (of %d)\n",
            $list,
            $matched,
            $review,
            $missed,
            count($hits)
        ));

        return ExitCode::OK;
    }

    /**
     * Resolves a listing hit to one of our games. Returns the game id and whether
     * the match is confident (id match) or needs review (title match), or null
     * when nothing fits.
     *
     * @param array{prod_id:int, name:string, seo_name:string, region:?string, discount:?int} $hit
     * @return array{game_id:int, confident:bool}|null
     */
    private function matchListHit(int $storeId, array $hit): ?array
    {
        $gameId = (int)GameOffer::find()
            ->select('game_id')
            ->where(['store_id' => $storeId, 'external_id' => (string)$hit['prod_id']])
            ->scalar();

        if ($gameId > 0) {
            return ['game_id' => $gameId, 'confident' => true];
        }

        $title = trim((string)$hit['name']);
        if ($title === '') {
            return null;
        }

        // onlyWithTitle is built for search — it happily returns a fuzzy "closest"
        // game, which here would mean Forza Horizon 6 → Forza Horizon 5. We use it
        // only to retrieve candidates, then keep one solely if its normalized title
        // is identical to the IG title. No exact match → a miss, not a wrong review.
        $matcher = new IgMatcher();
        $needle = $matcher->normalize($title);

        $candidates = Game::find()
            ->alias('game')
            ->active()
            ->andWhere(['game.type' => Game::TYPE_GAME])
            ->onlyWithTitle($title)
            ->limit(5)
            ->all();

        foreach ($candidates as $game) {
            if ($matcher->normalize((string)$game->title) === $needle) {
                return ['game_id' => (int)$game->id, 'confident' => false];
            }
        }

        return null;
    }

    /**
     * Creates or refreshes the IG offer for a matched listing game, using the
     * prices already in the payload.
     *
     * A brand-new offer is born ACTIVE for a confident id match, or REVIEW for a
     * title match (hidden until the list entry is accepted). An offer that already
     * exists keeps its current status — price/region/url are refreshed, but the
     * existing match/refresh pipeline and any admin decision stay in charge of
     * whether it's published.
     *
     * Guard: a fuzzy title match must never repoint an established offer that
     * already targets a *different* IG product. A confident id match can't trip
     * this (we found the game via that very prod_id), so this only bites the
     * uncertain path — there we leave the existing offer untouched and just let
     * syncList rank the game; it still shows iff that offer is already active.
     *
     * @param array{prod_id:int, name:string, seo_name:string, region:?string, discount:?int, price_eur:float, retail_eur:float} $hit
     * @param array<string, float> $rates
     * @param array<int, string>   $currencies
     */
    private function upsertListOffer(
        Store $store,
        int $gameId,
        array $hit,
        bool $confident,
        IgClient $client,
        array $rates,
        array $currencies
    ): void {
        $offer = GameOffer::findOne(['game_id' => $gameId, 'store_id' => $store->id])
            ?? new GameOffer(['game_id' => $gameId, 'store_id' => $store->id]);
        $isNew = $offer->getIsNewRecord();

        $prodId = (string)$hit['prod_id'];
        if (!$isNew && (string)$offer->external_id !== '' && (string)$offer->external_id !== $prodId) {
            if ($this->verbose) {
                $this->stdout("  ~ keep offer for game {$gameId}: already on IG #{$offer->external_id}, not repointing to #{$prodId}\n");
            }
            return;
        }

        $offer->external_id = $prodId;
        $offer->region = $hit['region'] ?? $offer->region;
        $offer->url = $client->buildProductUrl($hit);

        if ($isNew) {
            $offer->status = $confident ? GameOffer::STATUS_ACTIVE : GameOffer::STATUS_REVIEW;
        }

        if (!$offer->save()) {
            $this->stderr("  ! could not save offer for game {$gameId}: " . json_encode($offer->getErrors()) . "\n");
            return;
        }

        // applyPrices expects EUR `price`/`retail`; the listing carries those as
        // `price_eur`/`default_retail` (mapped to price_eur/retail_eur by the parser).
        $this->applyPrices(
            $offer,
            $hit + ['price' => $hit['price_eur'], 'retail' => $hit['retail_eur']],
            $rates,
            $currencies
        );
    }

    /**
     * Atomically reconciles {{%game_sale}} for one type with the freshly matched
     * list: re-ranks/keeps current entries, inserts new ones, and removes any that
     * dropped out. This table only holds the ranking — whether a game actually
     * shows in the rail is decided by its IG offer's status, not here.
     *
     * @param array<int, string> $desired game_id => external_id (IG prod_id), in rank order
     */
    private function syncList(int $type, array $desired): void
    {
        /** @var array<int, GameSale> $existing */
        $existing = GameSale::find()->where(['type' => $type])->indexBy('game_id')->all();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $order = 1;
            $kept = [];

            foreach ($desired as $gameId => $externalId) {
                $row = $existing[$gameId] ?? new GameSale(['game_id' => $gameId, 'type' => $type]);
                $row->order = $order++;
                $row->external_id = $externalId;

                $row->save(false);
                $kept[$gameId] = true;
            }

            $stale = array_keys(array_diff_key($existing, $kept));
            if ($stale) {
                GameSale::deleteAll(['type' => $type, 'game_id' => $stale]);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed>      $hit
     * @param array<string, float>      $rates
     * @param array<int, string>        $currencies
     */
    private function saveOffer(
        Store $store,
        Game $game,
        array $hit,
        IgClient $client,
        array $rates,
        array $currencies,
        bool $confident
    ): bool {
        $offer = GameOffer::findOne(['game_id' => $game->id, 'store_id' => $store->id])
            ?? new GameOffer(['game_id' => $game->id, 'store_id' => $store->id]);

        $offer->external_id = (string)$hit['prod_id'];
        $offer->region = $hit['region'] ?? null;
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
     * Converts the EUR catalogue price into each currency and stores it.
     *
     * @param array<string, mixed> $hit
     * @param array<string, float> $rates
     * @param array<int, string>   $currencies
     */
    private function applyPrices(GameOffer $offer, array $hit, array $rates, array $currencies): void
    {
        $priceEur = (float)($hit['price'] ?? 0);
        $retailEur = (float)($hit['retail'] ?? 0);
        $onSale = (int)($hit['discount'] ?? 0) > 0 && $retailEur > $priceEur;

        foreach ($currencies as $currency) {
            $rate = $rates[$currency] ?? null;
            if ($rate === null || $priceEur <= 0) {
                continue; // no rate or no price — don't write a bogus value
            }

            $final = (int)round($priceEur * $rate * 100);
            $initial = $onSale ? (int)round($retailEur * $rate * 100) : null;

            $offer->setPrice($currency, $final, $initial);
        }
    }

    /**
     * Re-finds a specific IG product among search hits for the title.
     *
     * @return array<string, mixed>|null
     */
    private function findHitByProdId(IgClient $client, string $title, int $prodId): ?array
    {
        if ($title === '' || $prodId <= 0) {
            return null;
        }

        foreach ($client->search($title) as $hit) {
            if ((int)($hit['prod_id'] ?? 0) === $prodId) {
                return $hit;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function currencies(): array
    {
        return Yii::$app->params['instant_gaming']['currencies'] ?? ['EUR', 'USD', 'PLN'];
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
