<?php

namespace common\components\InstantGaming;

use Yii;
use yii\helpers\Json;
use yii\httpclient\Client;

/**
 * Thin client for Instant Gaming.
 *
 * Their search is powered by Algolia (public, search-only credentials exposed
 * in the page source), so we query Algolia directly instead of scraping the
 * Vue-rendered HTML. Prices in the index are in EUR; other currencies are
 * derived by multiplying by the exchange rate IG publishes in `window.currencies`.
 *
 * Config (common/config/params.php, key `instant_gaming`):
 *   - algolia_app_id, algolia_search_key, algolia_index
 *   - affiliate_query: query string appended to product URLs, e.g. "igr=you"
 */
class IgClient
{
    private const string ALGOLIA_REFERER = 'https://www.instant-gaming.com/';
    private const string HOME_URL = 'https://www.instant-gaming.com/en/';
    private const string PRODUCT_URL_TEMPLATE = 'https://www.instant-gaming.com/en/%d-%s/';
    private const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /** Only Steam, full games (no DLC). */
    private const string SEARCH_FILTERS = 'type:Steam AND is_dlc=0';

    /** @var array<string, float>|null code => exchange rate (EUR base, EUR = 1.0) */
    private ?array $rates = null;

    private function config(string $key, string $default = ''): string
    {
        return (string)(Yii::$app->params['instant_gaming'][$key] ?? $default);
    }

    /**
     * Searches the IG catalogue by title and returns raw Algolia hits.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $hitsPerPage = 20): array
    {
        $appId = $this->config('algolia_app_id');
        $key = $this->config('algolia_search_key');
        $index = $this->config('algolia_index', 'produits_en');

        $client = new Client(['baseUrl' => "https://{$appId}-dsn.algolia.net/1/indexes/{$index}"]);

        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('query')
            ->addHeaders([
                'X-Algolia-API-Key'        => $key,
                'X-Algolia-Application-Id' => $appId,
                'Referer'                  => self::ALGOLIA_REFERER,
            ])
            ->setFormat(Client::FORMAT_JSON)
            ->setData([
                'params' => http_build_query([
                    'query'       => $query,
                    'hitsPerPage' => $hitsPerPage,
                    'filters'     => self::SEARCH_FILTERS,
                ]),
            ])
            ->send();

        if (!$response->isOk || !isset($response->data['hits'])) {
            return [];
        }

        return $response->data['hits'];
    }

    /**
     * Exchange rates IG uses to convert the EUR catalogue price into other
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
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl(self::HOME_URL)
            ->addHeaders([
                'User-Agent'      => self::USER_AGENT,
                'Accept-Language' => 'en',
            ])
            ->send();

        if ($response->isOk && preg_match('/window\.currencies\s*=\s*(\{.*?\})\s*;/s', $response->content, $m)) {
            try {
                $data = Json::decode($m[1]);
                foreach ($data as $code => $info) {
                    if (isset($info['tx'])) {
                        $this->rates[strtoupper($code)] = (float)$info['tx'];
                    }
                }
            } catch (\Throwable) {
                // Keep the EUR-only fallback; caller decides what to skip.
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
        $url = sprintf(self::PRODUCT_URL_TEMPLATE, (int)$hit['prod_id'], (string)($hit['seo_name'] ?? ''));

        $affiliate = $this->config('affiliate_query');
        if ($affiliate !== '') {
            $url .= '?' . ltrim($affiliate, '?&');
        }

        return $url;
    }
}
