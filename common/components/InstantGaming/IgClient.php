<?php

namespace common\components\InstantGaming;

use Symfony\Component\DomCrawler\Crawler;
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
     * Fetches one of IG's listing pages (trending, pre-orders, bestsellers, …) and
     * returns its games in display order.
     *
     * Two markups exist, tried in turn:
     *   - Most pages (trending, pre-orders) are Vue-rendered but ship the first
     *     Algolia result set inlined as the page's initial state, with full EUR
     *     prices — {@see parseListPayload()} reads that.
     *   - Others (e.g. /bestsellers/) render the grid as real HTML cards with no
     *     inlined state and only a localized price — {@see parseListHtml()} reads
     *     the ranking + ids from the DOM (EUR prices are then filled by the normal
     *     refresh-prices pass).
     *
     * @return array<int, array{prod_id:int, name:string, seo_name:string, region:?string, discount:?int, price_eur:float, retail_eur:float}>
     */
    public function fetchList(string $url): array
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->addHeaders([
                'User-Agent'      => self::USER_AGENT,
                'Accept-Language' => 'en',
            ])
            ->send();

        if (!$response->isOk) {
            return [];
        }

        $hits = $this->parseListPayload($response->content);

        // No inlined Algolia state (e.g. /bestsellers/) — read the HTML card grid.
        if (!$hits) {
            $hits = $this->parseListHtml($response->content);
        }

        return $hits;
    }

    /**
     * Fallback parser for listing pages that render their products as real HTML
     * cards instead of inlining an Algolia state (e.g. /bestsellers/). Reads the
     * ranked product grid straight from the DOM: prod_id + slug from each cover
     * link, and the display title.
     *
     * Unlike {@see parseListPayload()}, this markup carries no EUR base prices, so
     * `price_eur`/`retail_eur` come back 0 — a matched game's offer is priced by
     * the normal refresh-prices pass (confident matches already have a priced
     * offer; uncertain ones stay hidden in REVIEW until then). The display title
     * also carries IG's " - PC (Steam)" platform suffix, stripped here so it lines
     * up with the clean Algolia `name` the matcher expects.
     *
     * @return array<int, array{prod_id:int, name:string, seo_name:string, region:?string, discount:?int, price_eur:float, retail_eur:float}>
     */
    private function parseListHtml(string $html): array
    {
        $crawler = new Crawler($html);
        // The bestsellers grid; other strips on the page (search, recommended)
        // live under different containers, so scoping to .top-sales keeps them out.
        $cards = $crawler->filter('.top-sales .item');

        $hits = [];
        $seen = [];

        foreach ($cards as $node) {
            $card = new Crawler($node);

            $link = $card->filter('a.cover');
            if (!$link->count()) {
                continue;
            }

            if (!preg_match('~/en/(\d+)-([^/]+)/~', (string)$link->attr('href'), $m)) {
                continue;
            }
            $prodId = (int)$m[1];
            if ($prodId <= 0 || isset($seen[$prodId])) {
                continue;
            }

            $titleNode = $card->filter('.name .title');
            $raw = $titleNode->count() ? $titleNode->text() : (string)$link->attr('title');
            // The cover link's title attr is prefixed with "buy "; the span isn't.
            $name = $this->stripPlatformSuffix((string)preg_replace('/^\s*buy\s+/i', '', trim($raw)));
            if ($name === '') {
                continue;
            }

            $discountNode = $card->filter('.discount');
            $discount = $discountNode->count() && preg_match('/(\d+)/', $discountNode->text(), $d)
                ? (int)$d[1]
                : null;

            $seen[$prodId] = true;
            $hits[] = [
                'prod_id'    => $prodId,
                'name'       => $name,
                'seo_name'   => $m[2],
                'region'     => null,
                'discount'   => $discount,
                'price_eur'  => 0.0,
                'retail_eur' => 0.0,
            ];
        }

        return $hits;
    }

    /**
     * Drops IG's trailing " - <platforms> (<store>)" suffix from a listing title
     * (e.g. "Gothic 1 Remake - PC (Steam)" → "Gothic 1 Remake"), so HTML-card
     * titles match the clean Algolia `name`. The dash must be space-padded, so a
     * hyphen inside the game's own name (e.g. "Half-Life") is left untouched.
     */
    private function stripPlatformSuffix(string $title): string
    {
        $stripped = preg_replace('/\s+-\s+[^-]*\([^)]*\)\s*$/u', '', $title);

        return trim($stripped ?? $title);
    }

    /**
     * Extracts ordered, de-duplicated hits from a listing page's inlined Algolia
     * state. See {@see fetchList()} for why we read the inlined state rather than
     * driving a browser.
     *
     * We first narrow to the `"hits":[…]` array (so trailing "recommended" blocks
     * don't leak in), then split it on each `prod_id`. Within a hit object the id
     * comes before its `name`/`seo_name`/`region`, so reading each field from the
     * id up to the next id pins it to the right product — the earlier window scan
     * could grab the *previous* object's name and shift every title by one.
     *
     * @return array<int, array{prod_id:int, name:string, seo_name:string, region:?string, discount:?int, price_eur:float, retail_eur:float}>
     */
    private function parseListPayload(string $html): array
    {
        $start = strpos($html, '"hits":[');
        if ($start === false) {
            return [];
        }
        $end = strpos($html, '],"nbHits"', $start);
        $slice = $end !== false ? substr($html, $start, $end - $start) : substr($html, $start);

        if (!preg_match_all('/"prod_id":(\d+)/', $slice, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $hits = [];
        $seen = [];
        $count = count($matches[0]);

        foreach ($matches[1] as $k => $match) {
            $prodId = (int)$match[0];
            if ($prodId <= 0 || isset($seen[$prodId])) {
                continue;
            }

            $from = (int)$matches[0][$k][1];
            $to = $k + 1 < $count ? (int)$matches[0][$k + 1][1] : strlen($slice);
            $object = substr($slice, $from, $to - $from);

            $name = $this->extractJsonString($object, 'name');
            if ($name === null || $name === '') {
                continue;
            }

            $seen[$prodId] = true;
            $hits[] = [
                'prod_id'  => $prodId,
                'name'     => $name,
                'seo_name' => $this->extractJsonString($object, 'seo_name') ?? '',
                'region'   => $this->extractJsonString($object, 'region'),
                'discount' => preg_match('/"discount":(\d+)/', $object, $d) ? (int)$d[1] : null,
                // EUR base figures (the localized `price`/`retail` swing with the
                // viewer's geo, but these always stay EUR) — enough to build an
                // offer without a second Algolia call.
                'price_eur'  => (float)($this->extractJsonString($object, 'price_eur') ?? 0),
                'retail_eur' => (float)($this->extractJsonString($object, 'default_retail') ?? 0),
            ];
        }

        return $hits;
    }

    /**
     * Reads a JSON string value (`"key":"value"`) out of a raw fragment and
     * decodes its escapes (e.g. ō), returning null when absent.
     */
    private function extractJsonString(string $fragment, string $key): ?string
    {
        if (!preg_match('/"' . preg_quote($key, '/') . '":"((?:[^"\\\\]|\\\\.)*)"/', $fragment, $m)) {
            return null;
        }

        $decoded = json_decode('"' . $m[1] . '"');

        return is_string($decoded) ? $decoded : $m[1];
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
        return $this->buildProductUrlById((int)$hit['prod_id'], (string)($hit['seo_name'] ?? ''));
    }

    /**
     * Product URL for a prod_id, with the affiliate query appended when configured.
     * The slug is cosmetic — IG redirects `/{id}-…/` to the canonical page by id —
     * so an empty $seoName still resolves (used when we only stored the id).
     */
    public function buildProductUrlById(int $prodId, string $seoName = ''): string
    {
        $url = sprintf(self::PRODUCT_URL_TEMPLATE, $prodId, $seoName);

        $affiliate = $this->config('affiliate_query');
        if ($affiliate !== '') {
            $url .= '?' . ltrim($affiliate, '?&');
        }

        return $url;
    }
}
