<?php

namespace common\components\Gamivo;

use Yii;
use yii\httpclient\Client;
use yii\httpclient\Response;

/**
 * Thin client for Gamivo (key-marketplace).
 *
 * Gamivo's store is a Cloudflare-protected SSR app, so the HTML can't be
 * scraped server-side. Its product search, however, is a *public* Elasticsearch
 * endpoint (the same one the storefront posts to during SSR) which sits outside
 * Cloudflare — so we query it directly, exactly like we query Algolia for
 * Instant Gaming. Prices in the index are in EUR; other currencies are derived
 * by multiplying by the rates Gamivo publishes at /api/currency/list (EUR base).
 *
 * Config (common/config/params.php, key `gamivo`):
 *   - elastic_url:   Elasticsearch base, e.g. https://search.gamivo.com/
 *   - currency_url:  public EUR-base currency feed
 *   - currencies:    which currencies to store, e.g. ['EUR', 'USD', 'PLN']
 *   - affiliate_query: query string appended to product URLs (e.g. "glv=you")
 */
class GamivoClient
{
    private const string ELASTIC_URL  = 'https://search.gamivo.com/';
    private const string CURRENCY_URL = 'https://www.gamivo.com/api/currency/list';
    private const string PRODUCT_URL_TEMPLATE = 'https://www.gamivo.com/product/%s';
    private const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /** Only plain Steam keys, full games (mirrors IG's "type:Steam AND is_dlc=0"). */
    private const string PLATFORM_SLUG     = 'steam';
    private const string PRODUCT_TYPE_SLUG = 'games';

    /** _source fields we actually use — keep the payload small. */
    private const array SOURCE_FIELDS = [
        'name', 'displayName', 'slug', 'region', 'platform', 'productType',
        'lowestPrice', 'officialPrice', 'smartPrice', 'inStock', 'preorderOrPrepurchase',
    ];

    /** @var array<string, float>|null code => exchange rate (EUR base, EUR = 1.0) */
    private ?array $rates = null;

    /**
     * Reason the last search/rates call returned nothing, or null on success.
     * Lets the caller (and `gamivo/diagnose`) tell "the server is blocked / the
     * endpoint moved" apart from "the title genuinely isn't on Gamivo".
     */
    public ?string $lastError = null;

    private function config(string $key, string $default = ''): string
    {
        return (string)(Yii::$app->params['gamivo'][$key] ?? $default);
    }

    /**
     * Searches the Gamivo catalogue by title and returns flattened hits.
     *
     * Each hit is the Elasticsearch `_source` with the document id folded in as
     * `id`, so callers see one flat array per product (like IG's Algolia hits).
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $size = 40): array
    {
        $response = $this->sendSearch($query, $size);
        if ($response === null) {
            return []; // lastError already set by sendSearch()
        }

        if (!$response->isOk) {
            $this->lastError = "HTTP {$response->statusCode}";
            Yii::warning(
                "Gamivo search '{$query}' → HTTP {$response->statusCode}: " . $this->snippet($response),
                __METHOD__
            );
            return [];
        }

        if (!isset($response->data['hits']['hits'])) {
            // 200 but not the JSON we expect — e.g. a Cloudflare/interstitial page.
            $this->lastError = 'unexpected response body (no hits)';
            Yii::warning(
                "Gamivo search '{$query}' → unexpected body: " . $this->snippet($response),
                __METHOD__
            );
            return [];
        }

        $this->lastError = null;

        $hits = [];
        foreach ($response->data['hits']['hits'] as $doc) {
            $source = $doc['_source'] ?? [];
            $source['id'] = (string)($doc['_id'] ?? '');
            $hits[] = $source;
        }

        return $hits;
    }

    /**
     * Low-level diagnostics for the search endpoint, used by `gamivo/diagnose`.
     * Surfaces exactly what the server got back so a blocked production host
     * (403 / Cloudflare HTML / connection refused) is obvious.
     *
     * @return array{ok: bool, status: int, contentType: string, length: int, snippet: string, hitsTotal: ?int, returned: int, error: ?string}
     */
    public function diagnoseSearch(string $query, int $size = 40): array
    {
        $response = $this->sendSearch($query, $size);
        if ($response === null) {
            return [
                'ok' => false, 'status' => 0, 'contentType' => '', 'length' => 0,
                'snippet' => '', 'hitsTotal' => null, 'returned' => 0, 'error' => $this->lastError,
            ];
        }

        $body = (string)$response->content;

        return [
            'ok'          => $response->isOk,
            'status'      => $response->statusCode,
            'contentType' => (string)$response->headers->get('content-type', ''),
            'length'      => strlen($body),
            'snippet'     => $this->snippet($response),
            'hitsTotal'   => $response->data['hits']['total']['value'] ?? null,
            'returned'    => isset($response->data['hits']['hits']) ? count($response->data['hits']['hits']) : 0,
            'error'       => $response->isOk ? null : "HTTP {$response->statusCode}",
        ];
    }

    /**
     * Performs the search request, returning the raw Response, or null when the
     * request itself failed (DNS/connection/timeout) — in which case lastError
     * holds the reason.
     */
    private function sendSearch(string $query, int $size): ?Response
    {
        $base = $this->config('elastic_url', self::ELASTIC_URL);
        $client = new Client(['baseUrl' => rtrim($base, '/')]);

        try {
            return $client->createRequest()
                ->setMethod('POST')
                ->setUrl('_search/')
                ->addHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Origin'     => 'https://www.gamivo.com',
                    'Referer'    => 'https://www.gamivo.com/',
                ])
                ->setFormat(Client::FORMAT_JSON)
                ->setData([
                    'size'    => $size,
                    '_source' => self::SOURCE_FIELDS,
                    'query'   => [
                        'bool' => [
                            'must'   => [['match' => ['name' => $query]]],
                            'filter' => [
                                ['term' => ['platform.slug' => self::PLATFORM_SLUG]],
                                ['term' => ['productType.slug' => self::PRODUCT_TYPE_SLUG]],
                            ],
                        ],
                    ],
                ])
                ->send();
        } catch (\Throwable $e) {
            $this->lastError = 'request failed: ' . $e->getMessage();
            Yii::warning("Gamivo search '{$query}' request failed: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /** First 300 chars of a response body, newlines flattened, for logs. */
    private function snippet(Response $response): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_substr((string)$response->content, 0, 300)) ?? '');
    }

    /**
     * Exchange rates Gamivo uses to convert the EUR catalogue price into other
     * currencies (EUR is the base, so EUR = 1.0). Fetched once per instance.
     *
     * @return array<string, float>
     */
    public function fetchRates(): array
    {
        if ($this->rates !== null) {
            return $this->rates;
        }

        $this->rates = ['EUR' => 1.0];

        $client = new Client();
        try {
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl($this->config('currency_url', self::CURRENCY_URL))
                ->addHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept'     => 'application/json',
                ])
                ->send();
        } catch (\Throwable $e) {
            $this->lastError = 'rates request failed: ' . $e->getMessage();
            Yii::warning('Gamivo rates request failed: ' . $e->getMessage(), __METHOD__);
            return $this->rates;
        }

        if (!$response->isOk || !isset($response->data['data'])) {
            $this->lastError = $response->isOk
                ? 'rates: unexpected response body'
                : "rates: HTTP {$response->statusCode}";
            Yii::warning('Gamivo ' . $this->lastError . ': ' . $this->snippet($response), __METHOD__);
            return $this->rates; // EUR-only fallback; caller can detect the gap
        }

        foreach ($response->data['data'] as $currency) {
            $code = strtoupper((string)($currency['code'] ?? ''));
            if ($code !== '' && isset($currency['rate'])) {
                $this->rates[$code] = (float)$currency['rate'];
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
        $url = sprintf(self::PRODUCT_URL_TEMPLATE, (string)($hit['slug'] ?? ''));

        $affiliate = $this->config('affiliate_query');
        if ($affiliate !== '') {
            $url .= '?' . ltrim($affiliate, '?&');
        }

        return $url;
    }
}
