<?php

namespace console\controllers;

use common\components\Gamivo\GamivoClient;
use common\components\Gamivo\GamivoMatcher;
use common\models\Game;
use common\models\GameOffer;
use common\models\GameStoreScan;
use common\models\Store;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\mutex\FileMutex;

/**
 * Builds and refreshes Gamivo offers for our games.
 *
 * Two phases, meant to run on different cadences:
 *   - `match`         (heavy, rare): search Gamivo by title, match, create offers.
 *                     Confident Global matches are published; uncertain ones
 *                     (region-locked) are saved with STATUS_REVIEW and stay
 *                     hidden until checked.
 *   - `refresh-prices`(light, frequent): re-read prices for offers we already
 *                     matched, by their stored product id.
 *
 * Prices: the Gamivo catalogue price is EUR; we convert to each configured
 * currency with the rates Gamivo publishes, and store them in {{%game_offer_price}}.
 */
class GamivoController extends Controller
{
    private const string STORE_SLUG = 'gamivo';

    private const string LOCK_MATCH   = 'gamivo/match';
    private const string LOCK_REFRESH = 'gamivo/refresh-prices';

    private const int DELAY_MIN = 3;
    private const int DELAY_MAX = 7;

    /** How long before a previously-scanned game is eligible for matching again. */
    private const int REMATCH_COOLDOWN_DAYS = 90;

    public bool $verbose = false;

    /** Ignore the cooldown and re-scan games checked recently (incl. past misses). */
    public bool $recheck = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['verbose', 'recheck']);
    }

    /**
     * Match our games to Gamivo products and create offers.
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

        $client = new GamivoClient();
        $matcher = new GamivoMatcher();
        $rates = $client->fetchRates();
        $currencies = $this->currencies();

        // Games we don't have a Gamivo offer for yet.
        $existing = GameOffer::find()->select('game_id')->where(['store_id' => $store->id]);
        $query = Game::find()
            ->where(['status' => Game::STATUS_ACTIVE, 'type' => Game::TYPE_GAME])
            // Skip free-to-play games entirely — they aren't sold on stores.
            ->andWhere(['or', ['is_free' => 0], ['is_free' => null]])
            ->andWhere(['not in', 'id', $existing])
            ->orderBy(['id' => SORT_DESC]);

        // Skip games already checked within the cooldown so misses aren't
        // re-queried every run (--recheck forces a full re-scan).
        if (!$this->recheck) {
            $cutoff = (new \DateTime('-' . self::REMATCH_COOLDOWN_DAYS . ' days'))->format('Y-m-d H:i:s');
            $recentlyScanned = GameStoreScan::find()
                ->select('game_id')
                ->where(['store_id' => $store->id])
                ->andWhere(['>=', 'checked_at', $cutoff]);
            $query->andWhere(['not in', 'id', $recentlyScanned]);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $games = $query->all();
        $matched = $review = $missed = 0;

        foreach ($games as $game) {
            $hits = $client->search((string)$game->title);
            $result = $matcher->match((string)$game->title, $hits);

            // No title match, or the match has nothing buyable — treat as a miss.
            if ($result === null || !$this->isBuyable($result['hit'])) {
                $missed++;
                GameStoreScan::record($game->id, $store->id, GameStoreScan::RESULT_NO_MATCH);
                if ($this->verbose) {
                    $this->stdout("· no match: {$game->title}\n");
                }
                $this->throttle();
                continue;
            }

            $confident = $result['confidence'] === GamivoMatcher::CONFIDENCE_HIGH;
            if ($this->saveOffer($store, $game, $result['hit'], $client, $rates, $currencies, $confident)) {
                GameStoreScan::record(
                    $game->id,
                    $store->id,
                    $confident ? GameStoreScan::RESULT_MATCHED : GameStoreScan::RESULT_REVIEW
                );
            }

            if ($confident) {
                $matched++;
            } else {
                $review++;
            }

            if ($this->verbose) {
                $flag = $confident ? 'OK ' : 'REVIEW';
                $region = $result['hit']['region']['region'] ?? '?';
                $this->stdout("{$flag} {$game->title} → #{$result['hit']['id']} ({$region})\n");
            }

            $this->throttle();
        }

        $this->stdout(sprintf("Gamivo match — matched=%d review=%d missed=%d\n", $matched, $review, $missed));
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

        $client = new GamivoClient();
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
            $hit = $this->findHitById($client, (string)($offer->game->title ?? ''), (string)$offer->external_id);

            // Disappeared from the catalogue, or no longer buyable (out of stock /
            // no offers) — hide it and bump updated_at so it rotates to the back
            // of the queue instead of being re-checked every run.
            if ($hit === null || !$this->isBuyable($hit)) {
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
            $offer->region = $hit['region']['region'] ?? $offer->region;
            $offer->url = $client->buildProductUrl($hit);

            // Revive an offer we'd previously marked gone — re-publish based on
            // the current region (Global = confident). Offers an admin set
            // (ACTIVE/REVIEW) are left untouched.
            if ((int)$offer->status === GameOffer::STATUS_INACTIVE) {
                $offer->status = strtolower((string)($hit['region']['slug'] ?? '')) === 'global'
                    ? GameOffer::STATUS_ACTIVE
                    : GameOffer::STATUS_REVIEW;
            }

            $offer->save(false);
            $updated++;

            $this->throttle();
        }

        $this->stdout(sprintf("Gamivo refresh — updated=%d gone=%d\n", $updated, $gone));
        return ExitCode::OK;
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
        GamivoClient $client,
        array $rates,
        array $currencies,
        bool $confident
    ): bool {
        $offer = GameOffer::findOne(['game_id' => $game->id, 'store_id' => $store->id])
            ?? new GameOffer(['game_id' => $game->id, 'store_id' => $store->id]);

        $offer->external_id = (string)$hit['id'];
        $offer->region = $hit['region']['region'] ?? null;
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
     * `lowestPrice` is the cheapest current offer (what the buyer pays);
     * `officialPrice` is the publisher RRP, shown struck-through only when it is
     * actually higher than the price we sell at.
     *
     * @param array<string, mixed> $hit
     * @param array<string, float> $rates
     * @param array<int, string>   $currencies
     */
    private function applyPrices(GameOffer $offer, array $hit, array $rates, array $currencies): void
    {
        $priceEur  = (float)($hit['lowestPrice'] ?? 0);
        $retailEur = (float)($hit['officialPrice'] ?? 0);
        $onSale = $retailEur > $priceEur;

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
     * A hit is buyable when it's in stock and has a positive price; otherwise
     * there's nothing to link to (out of stock / no active offers).
     *
     * @param array<string, mixed> $hit
     */
    private function isBuyable(array $hit): bool
    {
        return (bool)($hit['inStock'] ?? false) && (float)($hit['lowestPrice'] ?? 0) > 0;
    }

    /**
     * Re-finds a specific Gamivo product among search hits for the title.
     *
     * @return array<string, mixed>|null
     */
    private function findHitById(GamivoClient $client, string $title, string $productId): ?array
    {
        if ($title === '' || $productId === '') {
            return null;
        }

        foreach ($client->search($title) as $hit) {
            if ((string)($hit['id'] ?? '') === $productId) {
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
        return Yii::$app->params['gamivo']['currencies'] ?? ['EUR', 'USD', 'PLN'];
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
