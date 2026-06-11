<?php

namespace common\components\Kinguin;

use Yii;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;
use yii\httpclient\Request;
use yii\httpclient\Response;

/**
 * Thin client for Kinguin (https://www.kinguin.net) via its official ESA API.
 *
 * Unlike Instant Gaming (Algolia), Gamivo (Elasticsearch) or GameSeal (scraped
 * suggest), Kinguin exposes a *first-party, authenticated* REST API — so there is
 * nothing to scrape and no Cloudflare to fight. Every call carries the API key in
 * the `X-Api-Key` header. Two endpoints are used:
 *
 *   - GET /esa/api/v1/products?name=&platform=Steam&limit=&page=
 *         Full-text product search. Each result is a product (one SKU/delivery
 *         type) with `name`, `originalName`, `platform`, `regionalLimitations`,
 *         `price` (EUR, cheapest active offer), `qty`, `kinguinId`, `productId`
 *         and — crucially — `steam` (the Steam appid), which lets us match by
 *         appid rather than fragile title text. See {@see KinguinMatcher}.
 *   - GET /esa/api/v1/products/{kinguinId}
 *         A single product by its stable kinguinId — used by the refresh phase to
 *         re-read one offer's price/stock precisely, no re-search needed.
 *
 * Prices are EUR; other currencies are derived with ECB daily reference rates
 * (same feed as the GameSeal client), EUR being the base (EUR = 1.0).
 *
 * Config: the API key lives in the top-level `kinguin_api_key` param (set in
 * params-local.php). Everything else lives under the `kinguin` key in
 * common/config/params.php:
 *   - api_url:        gateway base, e.g. https://gateway.kinguin.net/esa/api/v1
 *   - rates_url:      EUR-base FX feed (ECB daily reference rates)
 *   - currencies:     which currencies to store, e.g. ['EUR', 'USD', 'PLN']
 *   - affiliate_query: query string appended to product URLs (e.g. "ref=you")
 *   - proxy / proxy_auth / timeout: same semantics as the Gamivo client
 */
class KinguinClient
{
    private const string API_URL       = 'https://gateway.kinguin.net/esa/api/v1';
    private const string ECB_RATES_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
    private const string PRODUCT_URL_TEMPLATE = 'https://www.kinguin.net/category/%s';
    private const string USER_AGENT    = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /** We only build Steam offers, like the other stores. */
    private const string PLATFORM = 'Steam';

    /** @var array<string, float>|null code => exchange rate (EUR base, EUR = 1.0) */
    private ?array $rates = null;

    /**
     * Reason the last call returned nothing, or null on success. Lets the caller
     * (and `kinguin/diagnose`) tell "the key is wrong / we're rate-limited" apart
     * from "the title genuinely isn't on Kinguin".
     */
    public ?string $lastError = null;

    private function config(string $key, string $default = ''): string
    {
        return (string)(Yii::$app->params['kinguin'][$key] ?? $default);
    }

    private function apiKey(): string
    {
        return (string)(Yii::$app->params['kinguin_api_key'] ?? '');
    }

    /**
     * Searches the Kinguin catalogue by title (pre-filtered to Steam) and returns
     * the raw product list. Each element is a product array straight from the API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 20): array
    {
        $response = $this->sendRequest('/products', [
            'name'     => $query,
            'platform' => self::PLATFORM,
            'limit'    => $limit,
            'page'     => 1,
        ]);
        if ($response === null) {
            return []; // lastError already set
        }

        if (!$response->isOk) {
            $this->lastError = "HTTP {$response->statusCode}";
            Yii::warning("Kinguin search '{$query}' → HTTP {$response->statusCode}: " . $this->snippet($response), __METHOD__);
            return [];
        }

        if (!isset($response->data['results']) || !is_array($response->data['results'])) {
            $this->lastError = 'unexpected response body (no results)';
            Yii::warning("Kinguin search '{$query}' → unexpected body: " . $this->snippet($response), __METHOD__);
            return [];
        }

        $this->lastError = null;

        return $response->data['results'];
    }

    /**
     * Fetches a single product by its stable kinguinId, or null when it's gone or
     * the request failed. Used by the refresh phase to re-read one offer cheaply.
     *
     * @return array<string, mixed>|null
     */
    public function fetchProduct(string $kinguinId): ?array
    {
        if ($kinguinId === '') {
            return null;
        }

        $response = $this->sendRequest('/products/' . rawurlencode($kinguinId));
        if ($response === null) {
            return null; // lastError already set
        }

        if ($response->statusCode === 404) {
            $this->lastError = null; // a clean "gone", not an error
            return null;
        }

        if (!$response->isOk || !isset($response->data['kinguinId'])) {
            $this->lastError = $response->isOk ? 'unexpected product body' : "HTTP {$response->statusCode}";
            Yii::warning("Kinguin product '{$kinguinId}' → " . $this->lastError . ': ' . $this->snippet($response), __METHOD__);
            return null;
        }

        $this->lastError = null;

        return $response->data;
    }

    /**
     * Low-level diagnostics for the search endpoint, used by `kinguin/diagnose`.
     * Surfaces exactly what the server got back so a bad key (401/403) or a
     * rate-limit (429) is obvious.
     *
     * @return array{ok: bool, status: int, contentType: string, length: int, snippet: string, returned: int, error: ?string}
     */
    public function diagnoseSearch(string $query, int $limit = 20): array
    {
        $response = $this->sendRequest('/products', [
            'name'     => $query,
            'platform' => self::PLATFORM,
            'limit'    => $limit,
            'page'     => 1,
        ]);
        if ($response === null) {
            return [
                'ok' => false, 'status' => 0, 'contentType' => '', 'length' => 0,
                'snippet' => '', 'returned' => 0, 'error' => $this->lastError,
            ];
        }

        $body = (string)$response->content;

        return [
            'ok'          => $response->isOk,
            'status'      => $response->statusCode,
            'contentType' => (string)$response->headers->get('content-type', ''),
            'length'      => strlen($body),
            'snippet'     => $this->snippet($response),
            'returned'    => isset($response->data['results']) && is_array($response->data['results'])
                ? count($response->data['results'])
                : 0,
            'error'       => $response->isOk ? null : "HTTP {$response->statusCode}",
        ];
    }

    /**
     * Performs a GET against the API gateway, returning the raw Response, or null
     * when the request itself failed (DNS/connection/timeout/missing key) — in
     * which case lastError holds the reason.
     *
     * @param array<string, scalar> $params
     */
    private function sendRequest(string $path, array $params = []): ?Response
    {
        $key = $this->apiKey();
        if ($key === '') {
            $this->lastError = 'no API key configured (set kinguin_api_key in params-local.php)';
            Yii::warning('Kinguin: ' . $this->lastError, __METHOD__);
            return null;
        }

        $base = rtrim($this->config('api_url', self::API_URL), '/');
        $request = $this->httpClient($base)
            ->createRequest()
            ->setMethod('GET')
            ->setUrl(ltrim($path, '/'))
            ->setData($params)
            ->addHeaders([
                'X-Api-Key'  => $key,
                'User-Agent' => self::USER_AGENT,
                'Accept'     => 'application/json',
            ]);

        try {
            return $this->withTransportOptions($request)->send();
        } catch (\Throwable $e) {
            $this->lastError = 'request failed: ' . $e->getMessage();
            Yii::warning("Kinguin request {$path} failed: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Builds an HTTP client, switching to the cURL transport when a proxy is
     * configured (the default stream transport can't tunnel HTTPS via a proxy).
     */
    private function httpClient(string $baseUrl = ''): Client
    {
        $config = $baseUrl !== '' ? ['baseUrl' => $baseUrl] : [];
        if ($this->config('proxy') !== '') {
            $config['transport'] = CurlTransport::class;
        }

        return new Client($config);
    }

    /** Applies the per-request timeout and proxy options (when configured). */
    private function withTransportOptions(Request $request): Request
    {
        $options = ['timeout' => (int)($this->config('timeout', '20'))];

        $proxy = $this->config('proxy');
        if ($proxy !== '') {
            // cURL-specific options; only reached when httpClient() picked CurlTransport.
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
            Yii::warning('Kinguin rates request failed: ' . $e->getMessage(), __METHOD__);
            return $this->rates;
        }

        if (!$response->isOk) {
            $this->lastError = "rates: HTTP {$response->statusCode}";
            Yii::warning('Kinguin ' . $this->lastError, __METHOD__);
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
     * Monetized outbound URL for a hit. Kinguin resolves /category/{kinguinId} to
     * the product page; how that gets wrapped for affiliate tracking depends on
     * the program (config keys, in priority order):
     *
     *   1. `affiliate_deeplink` — a tracking-redirect template that *wraps* the
     *      product URL, the model Kinguin's own program and the networks (Awin,
     *      CJ, Admitad, MyLead) all use. Put a `{url}` placeholder where the
     *      (URL-encoded) destination goes, e.g.
     *        https://tracking.affiliateclub.cz/affc?offerid=1464&affid=ME&affsub5={url}
     *        https://www.awin1.com/cread.php?awinmid=XXXX&awinaffid=YYYY&ued={url}
     *        https://ad.admitad.com/g/XXXX/?ulp={url}
     *   2. `affiliate_query` — a query string merely *appended* to the product URL
     *      (the Instant-Gaming-style model), for the rare program that supports it.
     *   3. neither — the plain product URL.
     *
     * @param array<string, mixed> $hit
     */
    public function buildProductUrl(array $hit): string
    {
        $url = sprintf(self::PRODUCT_URL_TEMPLATE, (string)($hit['kinguinId'] ?? ''));

        $deeplink = $this->config('affiliate_deeplink');
        if ($deeplink !== '' && str_contains($deeplink, '{url}')) {
            return str_replace('{url}', rawurlencode($url), $deeplink);
        }

        $affiliate = $this->config('affiliate_query');
        if ($affiliate !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . ltrim($affiliate, '?&');
        }

        return $url;
    }
}
