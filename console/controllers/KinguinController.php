<?php

namespace console\controllers;

use common\components\Kinguin\KinguinClient;
use common\components\Kinguin\KinguinMatcher;
use common\models\Game;
use common\models\GameOffer;
use common\models\GameStoreScan;
use common\models\Store;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;
use yii\httpclient\StreamTransport;
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

    /** Curated campaign page we'd scrape for ranked rails (TOP Offers, Hot Right Now, …). */
    private const string CAMPAIGN_URL = 'https://www.kinguin.net/campaign/game-releases';
    private const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    private const string LOCK_MATCH   = 'kinguin/match';
    private const string LOCK_REFRESH = 'kinguin/refresh-prices';

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
     * Quick connectivity test for the curated campaign page we'd scrape to build
     * ranked homepage rails (TOP Offers / Hot Right Now / Recent / Upcoming).
     *
     * Run this ON THE PRODUCTION SERVER before committing to building the rails —
     * the page sits behind Cloudflare (like Gamivo/GameSeal), so a datacenter IP
     * may get a "Just a moment" challenge even though it loads fine from a normal
     * connection. It fetches the page with BOTH the cURL and the stream transport
     * (the curl-vs-stream difference is exactly what decided Gamivo vs GameSeal),
     * reports HTTP status / size / challenge detection, and — when the HTML comes
     * through — parses `window._preloadedState` and lists the carousels it finds
     * with their product/post counts, proving the data is actually extractable.
     *
     * Example: yii kinguin/diagnose-campaign
     */
    public function actionDiagnoseCampaign(): int
    {
        $url = (string)(Yii::$app->params['kinguin']['campaign_url'] ?? self::CAMPAIGN_URL);

        $this->stdout("Kinguin campaign-page diagnostics (from this server)\n");
        $this->stdout("URL: {$url}\n");
        $this->stdout(str_repeat('-', 64) . "\n");

        $anyUsable = false;
        foreach (['cURL' => CurlTransport::class, 'stream' => StreamTransport::class] as $label => $transport) {
            $r = $this->fetchCampaign($url, $transport);

            $this->stdout(sprintf(
                "%-7s: %s  http=%d  type=%s  bytes=%d%s\n",
                $label,
                $r['ok'] && !$r['challenge'] && $r['hasState'] ? 'OK' : 'PROBLEM',
                $r['status'],
                $r['contentType'] !== '' ? $r['contentType'] : '-',
                $r['bytes'],
                $r['error'] !== null ? '  error=' . $r['error'] : ''
            ));
            $this->stdout(sprintf(
                "         cloudflare-challenge=%s  preloadedState=%s\n",
                $r['challenge'] ? 'YES' : 'no',
                $r['hasState'] ? 'found' : 'MISSING'
            ));

            if ($r['sections']) {
                foreach ($r['sections'] as $s) {
                    $this->stdout(sprintf("         · %-34s %s=%d\n", $s['title'], $s['kind'], $s['count']));
                }
            } elseif ($r['hasState']) {
                $this->stdout("         (preloadedState found but no carousels parsed — markup may have changed)\n");
            } elseif (!$r['challenge'] && $r['bytes'] > 0) {
                $this->stdout('         body snippet: ' . $r['snippet'] . "\n");
            }

            $anyUsable = $anyUsable || ($r['ok'] && !$r['challenge'] && $r['hasState'] && $r['sections']);
        }

        $this->stdout(str_repeat('-', 64) . "\n");
        $this->stdout($anyUsable
            ? "VERDICT: scraping the campaign page WILL work from this server (use the transport marked OK above).\n"
            : "VERDICT: this server is BLOCKED/challenged or the markup changed — scraping won't work as-is (consider a proxy, like Gamivo).\n");

        return $anyUsable ? ExitCode::OK : ExitCode::TEMPFAIL;
    }

    /**
     * Fetches the campaign page with the given transport and inspects the result.
     *
     * @param class-string $transport
     * @return array{ok:bool, status:int, contentType:string, bytes:int, challenge:bool, hasState:bool, sections:array<int,array{title:string,kind:string,count:int}>, snippet:string, error:?string}
     */
    private function fetchCampaign(string $url, string $transport): array
    {
        $blank = [
            'ok' => false, 'status' => 0, 'contentType' => '', 'bytes' => 0,
            'challenge' => false, 'hasState' => false, 'sections' => [], 'snippet' => '', 'error' => null,
        ];

        $request = (new Client(['transport' => $transport]))
            ->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->addHeaders([
                'User-Agent'      => self::USER_AGENT,
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en',
            ])
            ->addOptions($this->campaignTransportOptions($transport));

        try {
            $response = $request->send();
        } catch (\Throwable $e) {
            return ['error' => 'request failed: ' . $e->getMessage()] + $blank;
        }

        $body = (string)$response->content;
        $challenge = str_contains($body, 'challenge-platform')
            || str_contains($body, 'Just a moment')
            || str_contains($body, 'cf-chl');

        $state = $this->extractPreloadedState($body);

        return [
            'ok'          => $response->isOk,
            'status'      => $response->statusCode,
            'contentType' => (string)$response->headers->get('content-type', ''),
            'bytes'       => strlen($body),
            'challenge'   => $challenge,
            'hasState'    => $state !== null,
            'sections'    => $state !== null ? $this->summariseSections($state) : [],
            'snippet'     => trim(preg_replace('/\s+/', ' ', mb_substr($body, 0, 200)) ?? ''),
            'error'       => $response->isOk ? null : "HTTP {$response->statusCode}",
        ];
    }

    /** Timeout (+ optional proxy, reusing the offers config) for the campaign fetch. */
    private function campaignTransportOptions(string $transport): array
    {
        $options = ['timeout' => (int)(Yii::$app->params['kinguin']['timeout'] ?? 25)];

        $proxy = (string)(Yii::$app->params['kinguin']['proxy'] ?? '');
        if ($proxy !== '' && $transport === CurlTransport::class) {
            $options[CURLOPT_PROXY] = $proxy;
            $proxyAuth = (string)(Yii::$app->params['kinguin']['proxy_auth'] ?? '');
            if ($proxyAuth !== '') {
                $options[CURLOPT_PROXYUSERPWD] = $proxyAuth;
            }
        }

        return $options;
    }

    /**
     * Extracts and JSON-decodes the `window._preloadedState = {…}` object from the
     * page HTML, brace-matching so a `}` inside a string doesn't end it early.
     *
     * @return array<mixed>|null
     */
    private function extractPreloadedState(string $html): ?array
    {
        $pos = strpos($html, 'window._preloadedState');
        if ($pos === false) {
            return null;
        }
        $start = strpos($html, '{', $pos);
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inStr = false;
        $esc = false;
        $len = strlen($html);
        for ($k = $start; $k < $len; $k++) {
            $ch = $html[$k];
            if ($inStr) {
                if ($esc) {
                    $esc = false;
                } elseif ($ch === '\\') {
                    $esc = true;
                } elseif ($ch === '"') {
                    $inStr = false;
                }
            } elseif ($ch === '"') {
                $inStr = true;
            } elseif ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                if (--$depth === 0) {
                    $data = json_decode(substr($html, $start, $k - $start + 1), true);
                    return is_array($data) ? $data : null;
                }
            }
        }

        return null;
    }

    /**
     * Walks the decoded state and summarises every CMS carousel/grid component as
     * {title, kind (products/posts), count} — so the test shows the four sections
     * and how many items each yields.
     *
     * @param array<mixed> $state
     * @return array<int, array{title:string, kind:string, count:int}>
     */
    private function summariseSections(array $state): array
    {
        $components = [];
        $this->collectComponents($state, $components);

        $sections = [];
        foreach ($components as $c) {
            $component = (string)($c['__component'] ?? '');
            if (!str_contains($component, 'carousel') && !str_contains($component, 'grid')) {
                continue;
            }
            if (isset($c['products'])) {
                $kind = 'products';
                $count = is_array($c['products']) ? count($c['products']) : 0;
            } elseif (isset($c['posts'])) {
                $kind = 'posts';
                $count = is_array($c['posts']) ? count($c['posts']) : 0;
            } else {
                continue;
            }
            $sections[] = [
                'title' => (string)($c['title'] ?? '(untitled)'),
                'kind'  => $kind,
                'count' => $count,
            ];
        }

        return $sections;
    }

    /**
     * @param array<mixed> $node
     * @param array<int, array<mixed>> $out
     */
    private function collectComponents(array $node, array &$out): void
    {
        if (isset($node['__component'])) {
            $out[] = $node;
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $this->collectComponents($v, $out);
            }
        }
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
