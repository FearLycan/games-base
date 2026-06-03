<?php

namespace common\components\GameSeal;

use Yii;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;
use yii\httpclient\Request;
use yii\httpclient\Response;

/**
 * Thin client for GameSeal (https://gameseal.com).
 *
 * GameSeal runs on Shopware 6 behind Cloudflare. Its Store API (/store-api) is
 * disabled ("access restricted"), so unlike Instant Gaming (Algolia) or Gamivo
 * (Elasticsearch) there is no public JSON search endpoint — we scrape the
 * storefront's own AJAX *suggest* dropdown instead. It returns a small HTML
 * fragment per query with one block per product (name, region, delivery type,
 * current + struck price), which we parse with DOMXPath.
 *
 * Two things make the scrape deterministic:
 *   - Currency follows the URL locale. The default (prefix-less) storefront is
 *     EUR; the `/pl/` channel is PLN, `/en/` 404s. We therefore warm an EUR
 *     session on the homepage and query the prefix-less `/suggest`, so prices
 *     are always EUR — then convert to the other currencies with ECB rates.
 *   - A session cookie from the homepage is required before /suggest answers
 *     (a cold hit redirect-loops). We capture it once and replay it.
 *
 * Cloudflare blocks some datacenter IPs. The suggest endpoint answers fine from
 * a normal IP; set `proxy` to route the calls through an allowed IP when the
 * server is blocked (see `gameseal/diagnose`).
 *
 * Config (common/config/params.php, key `gameseal`):
 *   - home_url / suggest_url: storefront endpoints (EUR channel)
 *   - rates_url:     EUR-base FX feed (ECB daily reference rates)
 *   - currencies:    which currencies to store, e.g. ['EUR', 'USD', 'PLN']
 *   - affiliate_query: query string appended to product URLs (e.g. "ref=you")
 *   - proxy / proxy_auth / timeout: same semantics as the Gamivo client
 */
class GsClient
{
    private const string HOME_URL      = 'https://gameseal.com/';
    private const string SUGGEST_URL   = 'https://gameseal.com/suggest';
    private const string ECB_RATES_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
    private const string USER_AGENT    = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /** Temp cookie-jar file shared by every cURL call, so the EUR session sticks. */
    private ?string $cookieJar = null;

    /** Whether the EUR-session handshake has run (and succeeded) this instance. */
    private bool $sessionReady = false;

    /** @var array<string, float>|null code => exchange rate (EUR base, EUR = 1.0) */
    private ?array $rates = null;

    /**
     * Reason the last call returned nothing, or null on success. Lets the caller
     * (and `gameseal/diagnose`) tell "blocked / endpoint moved" apart from "the
     * title genuinely isn't on GameSeal".
     */
    public ?string $lastError = null;

    private function config(string $key, string $default = ''): string
    {
        return (string)(Yii::$app->params['gameseal'][$key] ?? $default);
    }

    /**
     * Searches the GameSeal catalogue by title and returns flattened hits.
     *
     * Each hit is a flat array (like IG's Algolia / Gamivo's ES hits):
     *   slug, url, title, name, region, delivery, price (EUR), retail (EUR)
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 12): array
    {
        $response = $this->sendSuggest($query, $limit);
        if ($response === null) {
            return []; // lastError already set
        }

        if (!$response->isOk) {
            $this->lastError = "HTTP {$response->statusCode}";
            Yii::warning("GameSeal search '{$query}' → HTTP {$response->statusCode}: " . $this->snippet($response), __METHOD__);
            return [];
        }

        $body = (string)$response->content;
        if (str_contains($body, 'challenge-platform') || str_contains($body, 'Just a moment')) {
            // A Cloudflare interstitial instead of the suggest fragment.
            $this->lastError = 'Cloudflare challenge';
            Yii::warning("GameSeal search '{$query}' → Cloudflare challenge", __METHOD__);
            return [];
        }

        $hits = $this->parseSuggest($body);
        $this->lastError = null;

        return $hits;
    }

    /**
     * Performs the suggest request (warming an EUR session first), returning the
     * raw Response, or null when the request itself failed.
     */
    private function sendSuggest(string $query, int $limit): ?Response
    {
        if (!$this->ensureSession()) {
            return null; // lastError set by ensureSession()
        }

        $request = $this->httpClient()
            ->createRequest()
            ->setMethod('GET')
            ->setUrl($this->config('suggest_url', self::SUGGEST_URL))
            ->setData(['search' => $query, 'limit' => $limit])
            ->addHeaders([
                'User-Agent'       => self::USER_AGENT,
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept'           => 'text/html, */*; q=0.01',
                'Referer'          => self::HOME_URL,
            ]);

        try {
            return $this->withTransportOptions($request)->send();
        } catch (\Throwable $e) {
            $this->lastError = 'request failed: ' . $e->getMessage();
            Yii::warning("GameSeal search '{$query}' request failed: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Warms an EUR storefront session into the shared cookie jar. Memoized for
     * the lifetime of the client. Returns false (and sets lastError) when the
     * handshake itself fails — e.g. a blocked server. libcurl carries the cookies
     * it sets here across the homepage's redirects and into the suggest call.
     */
    private function ensureSession(): bool
    {
        if ($this->sessionReady) {
            return true;
        }

        $request = $this->httpClient()
            ->createRequest()
            ->setMethod('GET')
            ->setUrl($this->config('home_url', self::HOME_URL))
            ->addHeaders([
                'User-Agent'      => self::USER_AGENT,
                'Accept'          => 'text/html',
                'Accept-Language' => 'en',
            ]);

        try {
            $response = $this->withTransportOptions($request)->send();
        } catch (\Throwable $e) {
            $this->lastError = 'handshake failed: ' . $e->getMessage();
            Yii::warning('GameSeal handshake failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }

        if (!$response->isOk) {
            $this->lastError = "handshake HTTP {$response->statusCode}";
            Yii::warning('GameSeal ' . $this->lastError . ': ' . $this->snippet($response), __METHOD__);
            return false;
        }

        return $this->sessionReady = true;
    }

    /** Path of the per-instance cookie jar, created lazily. */
    private function cookieJar(): string
    {
        if ($this->cookieJar === null) {
            $this->cookieJar = (string)tempnam(sys_get_temp_dir(), 'gs_cookies_');
        }

        return $this->cookieJar;
    }

    public function __destruct()
    {
        if ($this->cookieJar !== null && is_file($this->cookieJar)) {
            @unlink($this->cookieJar);
        }
    }

    /**
     * Parses a suggest HTML fragment into flat product hits. Each product is an
     * `<a class="search-suggest-product-link">`; the trailing "show all results"
     * link and anything without a price is skipped.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseSuggest(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        // Force UTF-8 so accented titles survive; suppress malformed-fragment noise.
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($doc);
        $anchors = $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' search-suggest-product-link ')]");
        if ($anchors === false) {
            return [];
        }

        $hits = [];
        foreach ($anchors as $a) {
            /** @var \DOMElement $a */
            $url = trim($a->getAttribute('href'));
            if ($url === '') {
                continue;
            }

            $price = $this->parsePrice($this->nodeText($xpath, ".//*[contains(@class,'product-price-regular')]", $a));
            if ($price <= 0) {
                continue; // nothing buyable to link to
            }

            $retail = $this->parsePrice($this->nodeText($xpath, ".//*[contains(@class,'product-price-list')]", $a));
            $name = $this->nodeText($xpath, ".//*[contains(@class,'search-suggest-product-name')]", $a);
            $region = $this->nodeText($xpath, ".//*[contains(@class,'badge-region')]", $a);
            $delivery = $this->nodeText($xpath, ".//*[contains(@class,'badge-platform')]", $a);

            $hits[] = [
                'slug'     => $this->slugFromUrl($url),
                'url'      => $url,
                'title'    => trim($a->getAttribute('title')) ?: $name,
                'name'     => $name,
                'region'   => $region,
                'delivery' => $delivery,
                'price'    => $price,
                'retail'   => $retail,
            ];
        }

        return $hits;
    }

    /** Trimmed text content of the first node matching $expr under $context. */
    private function nodeText(\DOMXPath $xpath, string $expr, \DOMElement $context): string
    {
        $nodes = $xpath->query($expr, $context);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', (string)$nodes->item(0)->textContent) ?? '');
    }

    /** Parses "26.94 €" / "296,58 zł" into a float amount (currency stripped). */
    private function parsePrice(string $text): float
    {
        if ($text === '' || !preg_match('/([0-9][0-9., ]*[0-9]|[0-9])/u', $text, $m)) {
            return 0.0;
        }

        $number = str_replace(' ', '', $m[1]);
        // Normalise decimal separator: drop thousands sep, keep the last . or , as decimal.
        $number = preg_replace('/[.,](?=\d{3}\b)/', '', $number) ?? $number;
        $number = str_replace(',', '.', $number);

        return (float)$number;
    }

    /** Last path segment of a product URL — its stable slug (refresh key). */
    private function slugFromUrl(string $url): string
    {
        $path = (string)parse_url($url, PHP_URL_PATH);

        return trim(basename($path));
    }

    /**
     * EUR-base exchange rates from the ECB daily reference feed (EUR = 1.0).
     * Fetched once per instance; on failure returns the EUR-only fallback and
     * sets lastError so the caller can detect the gap.
     *
     * @return array<string, float>
     */
    public function fetchRates(): array
    {
        if ($this->rates !== null) {
            return $this->rates;
        }

        $this->rates = ['EUR' => 1.0];

        $request = $this->httpClient()
            ->createRequest()
            ->setMethod('GET')
            ->setUrl($this->config('rates_url', self::ECB_RATES_URL))
            ->addHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'application/xml']);

        try {
            $response = $this->withTransportOptions($request)->send();
        } catch (\Throwable $e) {
            $this->lastError = 'rates request failed: ' . $e->getMessage();
            Yii::warning('GameSeal rates request failed: ' . $e->getMessage(), __METHOD__);
            return $this->rates;
        }

        if (!$response->isOk) {
            $this->lastError = "rates: HTTP {$response->statusCode}";
            Yii::warning('GameSeal ' . $this->lastError, __METHOD__);
            return $this->rates;
        }

        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string)$response->content);
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            $this->lastError = 'rates: malformed XML';
            return $this->rates;
        }

        foreach ($xml->xpath('//*[@currency]') ?: [] as $cube) {
            $code = strtoupper((string)$cube['currency']);
            $rate = (float)$cube['rate'];
            if ($code !== '' && $rate > 0) {
                $this->rates[$code] = $rate;
            }
        }

        return $this->rates;
    }

    /**
     * Absolute product URL for a hit, with the affiliate query appended when configured.
     *
     * @param array<string, mixed> $hit
     */
    public function buildProductUrl(array $hit): string
    {
        $url = (string)($hit['url'] ?? '');

        $affiliate = $this->config('affiliate_query');
        if ($affiliate !== '' && $url !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . ltrim($affiliate, '?&');
        }

        return $url;
    }

    /**
     * Low-level diagnostics for the suggest endpoint, used by `gameseal/diagnose`.
     * Surfaces exactly what this server got back so a blocked host (Cloudflare
     * challenge / 403 / redirect) is obvious.
     *
     * @return array{ok: bool, status: int, contentType: string, length: int, snippet: string, returned: int, error: ?string}
     */
    public function diagnoseSearch(string $query, int $limit = 12): array
    {
        $response = $this->sendSuggest($query, $limit);
        if ($response === null) {
            return [
                'ok' => false, 'status' => 0, 'contentType' => '', 'length' => 0,
                'snippet' => '', 'returned' => 0, 'error' => $this->lastError,
            ];
        }

        $body = (string)$response->content;
        $challenge = str_contains($body, 'challenge-platform') || str_contains($body, 'Just a moment');

        return [
            'ok'          => $response->isOk && !$challenge,
            'status'      => $response->statusCode,
            'contentType' => (string)$response->headers->get('content-type', ''),
            'length'      => strlen($body),
            'snippet'     => $this->snippet($response),
            'returned'    => count($this->parseSuggest($body)),
            'error'       => $challenge ? 'Cloudflare challenge' : ($response->isOk ? null : "HTTP {$response->statusCode}"),
        ];
    }

    /**
     * Always the cURL transport. GameSeal's Cloudflare flags PHP's stream
     * transport (its TLS fingerprint isn't browser-like) and answers with a
     * "Just a moment" challenge, whereas libcurl — the same engine as the `curl`
     * binary that passes from the server — is let through.
     */
    private function httpClient(): Client
    {
        return new Client(['transport' => CurlTransport::class]);
    }

    /**
     * Applies the per-request timeout, browser-like cURL options (HTTP/2, follow
     * redirects, transparent decompression) and the proxy options when configured.
     */
    private function withTransportOptions(Request $request): Request
    {
        $options = [
            'timeout'              => (int)($this->config('timeout', '20')),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
            CURLOPT_ENCODING       => '', // accept gzip/br like a browser
            // Shared cookie engine: the session cookie set on the homepage (and
            // during its redirects) is echoed back on the suggest call — exactly
            // what `curl -c jar … -b jar` does. Without it the redirect loops.
            CURLOPT_COOKIEFILE     => $this->cookieJar(),
            CURLOPT_COOKIEJAR      => $this->cookieJar(),
        ];

        $proxy = $this->config('proxy');
        if ($proxy !== '') {
            $options[CURLOPT_PROXY] = $proxy;
            $proxyAuth = $this->config('proxy_auth');
            if ($proxyAuth !== '') {
                $options[CURLOPT_PROXYUSERPWD] = $proxyAuth;
            }
        }

        return $request->addOptions($options);
    }

    /** First 300 chars of a response body, newlines flattened, for logs. */
    private function snippet(Response $response): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_substr((string)$response->content, 0, 300)) ?? '');
    }
}
