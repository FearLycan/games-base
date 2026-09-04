<?php

namespace frontend\modules\api\components;

use common\models\Game;
use common\models\GameImage;
use common\models\GameOffer;
use common\models\GameOfferPrice;
use common\models\GameVideo;
use common\models\Store;
use yii\helpers\Url;

/**
 * Turns catalogue models into the internal API's JSON shape.
 *
 * Lives here rather than in the controller so the field mapping (and the
 * eager-loading it implies) stays in one place — the controllers only decide
 * *which* games to serialise.
 *
 * Money is always in **minor units** (cents/grosze), matching how it is stored
 * (`game.steam_price_*`, `game_offer_price.price_*`). Consumers divide by 100.
 */
class GameSerializer
{
    /** Optional sections, requested via `?include=a,b,c`. */
    public const string INC_DESCRIPTIONS = 'descriptions';
    public const string INC_IMAGES       = 'images';
    public const string INC_SCREENSHOTS  = 'screenshots';
    public const string INC_VIDEOS       = 'videos';
    public const string INC_GENRES       = 'genres';
    public const string INC_TAGS         = 'tags';
    public const string INC_CATEGORIES   = 'categories';
    public const string INC_COMPANIES    = 'companies';
    public const string INC_PLATFORMS    = 'platforms';
    public const string INC_REQUIREMENTS = 'requirements';
    public const string INC_RATINGS      = 'ratings';
    public const string INC_OFFERS       = 'offers';

    /** Everything a caller may ask for. */
    public const array ALL_INCLUDES = [
        self::INC_DESCRIPTIONS,
        self::INC_IMAGES,
        self::INC_SCREENSHOTS,
        self::INC_VIDEOS,
        self::INC_GENRES,
        self::INC_TAGS,
        self::INC_CATEGORIES,
        self::INC_COMPANIES,
        self::INC_PLATFORMS,
        self::INC_REQUIREMENTS,
        self::INC_RATINGS,
        self::INC_OFFERS,
    ];

    /**
     * What a caller gets without asking. Screenshots, tags, categories, the long
     * descriptions and the OS requirement blobs are left out: they multiply the
     * payload several times over and importers rarely need them.
     */
    public const array DEFAULT_INCLUDES = [
        self::INC_IMAGES,
        self::INC_VIDEOS,
        self::INC_GENRES,
        self::INC_COMPANIES,
        self::INC_PLATFORMS,
        self::INC_RATINGS,
        self::INC_OFFERS,
    ];

    /** @var array<string, true> requested sections, as a lookup */
    private array $includes;

    /** @var string[]|null currency filter for offer prices; null = all */
    private ?array $currencies;

    /**
     * @param string[]      $includes
     * @param string[]|null $currencies
     */
    public function __construct(array $includes, ?array $currencies = null)
    {
        $this->includes = array_fill_keys($includes, true);
        $this->currencies = $currencies;
    }

    /**
     * Parses the `include` query parameter. `all` expands to everything; unknown
     * names are ignored; an absent parameter yields {@see DEFAULT_INCLUDES}.
     *
     * @return string[]
     */
    public static function parseIncludes(?string $raw): array
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return self::DEFAULT_INCLUDES;
        }

        if (strtolower($raw) === 'all') {
            return self::ALL_INCLUDES;
        }

        $requested = array_map(
            static fn(string $name): string => strtolower(trim($name)),
            explode(',', $raw),
        );

        return array_values(array_intersect(self::ALL_INCLUDES, $requested));
    }

    /**
     * Relations to eager-load for the requested sections. Feed this straight to
     * `Game::find()->with(...)` so a page of games costs a fixed number of
     * queries instead of one per game per section.
     *
     * @return array<int|string, mixed>
     */
    public function eagerLoad(): array
    {
        $with = [];

        if ($this->has(self::INC_IMAGES) || $this->has(self::INC_SCREENSHOTS)) {
            $with[] = 'images';
        }
        if ($this->has(self::INC_VIDEOS)) {
            $with[] = 'videos';
        }
        if ($this->has(self::INC_GENRES)) {
            $with[] = 'genres';
        }
        if ($this->has(self::INC_TAGS)) {
            // getGameTags() already orders by vote count; the chained relation
            // only needs the Tag rows loaded.
            $with[] = 'gameTags.tag';
        }
        if ($this->has(self::INC_CATEGORIES)) {
            $with[] = 'categories';
        }
        if ($this->has(self::INC_COMPANIES)) {
            $with[] = 'developers';
            $with[] = 'publishers';
        }
        if ($this->has(self::INC_PLATFORMS) || $this->has(self::INC_REQUIREMENTS)) {
            $with[] = 'platforms';
        }
        if ($this->has(self::INC_RATINGS)) {
            $with[] = 'metacritic';
            $with[] = 'review';
        }
        if ($this->has(self::INC_OFFERS)) {
            $with['gameOffers'] = static function ($query): void {
                $query->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])
                    ->orderBy(['game_offer.order' => SORT_ASC])
                    ->with(['store', 'prices']);
            };
        }

        return $with;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Game $game): array
    {
        $releaseDate = $this->date($game->release_date);

        $payload = [
            'id'                  => (int)$game->id,
            'steam_appid'         => $game->steam_appid !== null ? (int)$game->steam_appid : null,
            'title'               => $game->title,
            'slug'                => $game->slug,
            'type'                => $game->type,
            'is_free'             => (bool)$game->is_free,
            'is_preorder'         => (bool)$game->is_preorder,
            'is_adult'            => (bool)$game->is_adult,
            'required_age'        => (int)$game->required_age,
            'content_descriptors' => $this->contentDescriptors($game),
            // NULL for games Steam only dates vaguely ("Q1 2027", "Coming soon"):
            // we store a date or nothing, never an approximation. Consumers that
            // need those should fall back to another source (e.g. IGDB).
            'release_date'        => $releaseDate,
            'is_released'         => $releaseDate !== null && $releaseDate <= date('Y-m-d'),
            // DLC/add-on rows point at their base game's appid.
            'fullgame_appid'      => $game->fullgame_appid !== null ? (int)$game->fullgame_appid : null,
            'achievements_total'  => (int)$game->achievements_total,
            'steam_deck'          => $game->steam_deck !== null ? (int)$game->steam_deck : null,
            'website'             => $game->website ?: null,
            'steam_url'           => $game->steam_appid ? $game->getSteamUrl() : null,
            'url'                 => $this->gameUrl($game),
            'short_description'   => $game->short_description,
            'steam_price'         => $this->steamPrice($game),
            'created_at'          => $this->datetime($game->created_at),
            'updated_at'          => $this->datetime($game->updated_at),
            'synchronized_at'     => $this->datetime($game->synchronized_at),
            // Single field to drive incremental imports: the latest of the three
            // timestamps above. Matches what `?updated_since=` filters on.
            'changed_at'          => $this->changedAt($game),
        ];

        if ($this->has(self::INC_DESCRIPTIONS)) {
            $payload['about_the_game'] = $game->about_the_game;
            $payload['detailed_description'] = $game->detailed_description;
        }
        if ($this->has(self::INC_IMAGES)) {
            $payload['images'] = $this->images($game);
        }
        if ($this->has(self::INC_SCREENSHOTS)) {
            $payload['screenshots'] = $this->imageUrls($game, GameImage::TYPE_SCREENSHOT);
        }
        if ($this->has(self::INC_VIDEOS)) {
            $payload['videos'] = $this->videos($game);
        }
        if ($this->has(self::INC_GENRES)) {
            $payload['genres'] = $this->named($game->genres);
        }
        if ($this->has(self::INC_TAGS)) {
            $payload['tags'] = $this->tags($game);
        }
        if ($this->has(self::INC_CATEGORIES)) {
            $payload['categories'] = $this->named($game->categories);
        }
        if ($this->has(self::INC_COMPANIES)) {
            $payload['developers'] = $this->named($game->developers);
            $payload['publishers'] = $this->named($game->publishers);
        }
        if ($this->has(self::INC_PLATFORMS)) {
            $payload['platforms'] = $this->platforms($game);
        }
        if ($this->has(self::INC_REQUIREMENTS)) {
            $payload['requirements'] = $this->requirements($game);
        }
        if ($this->has(self::INC_RATINGS)) {
            $payload['metacritic'] = $this->metacritic($game);
            $payload['steam_reviews'] = $this->reviews($game);
        }
        if ($this->has(self::INC_OFFERS)) {
            $payload['offers'] = $this->offers($game);
        }

        return $payload;
    }

    /**
     * One store offer, flattened for API consumers.
     *
     * @return array<string, mixed>
     */
    public function offer(GameOffer $offer): array
    {
        $store = $offer->store;

        return [
            'offer_id'    => (int)$offer->id,
            // The affiliate query/deeplink is already baked into the stored URL
            // by the store client, so this is the link to send buyers to.
            'url'         => $offer->url,
            'external_id' => $offer->external_id,
            'region'      => $offer->region,
            'edition'     => $offer->edition,
            'store'       => $this->store($store),
            'prices'      => $this->prices($offer),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function store(?Store $store): ?array
    {
        if ($store === null) {
            return null;
        }

        return [
            'id'      => (int)$store->id,
            'name'    => $store->name,
            'slug'    => $store->slug,
            'type'    => $store->getType()->value,
            'kind'    => strtolower($store->getType()->name), // official | keyshop
            'website' => $store->website,
            // Logos are stored as site-relative paths; consumers live on another
            // host, so hand them something they can actually load.
            'logo'    => $this->absolute($store->getLogo()),
        ];
    }

    /**
     * Per-currency prices for one offer, keyed by currency code.
     *
     * @return array<string, array<string, mixed>>
     */
    public function prices(GameOffer $offer): array
    {
        $prices = [];

        foreach ($offer->prices as $price) {
            $currency = strtoupper((string)$price->currency);
            if ($this->currencies !== null && !in_array($currency, $this->currencies, true)) {
                continue;
            }

            $prices[$currency] = $this->price($price);
        }

        return $prices;
    }

    /**
     * @return array<string, mixed>
     */
    public function price(GameOfferPrice $price): array
    {
        $initial = $price->price_initial !== null ? (int)$price->price_initial : null;
        $final = $price->price_final !== null ? (int)$price->price_final : null;

        return [
            'price_initial'    => $initial,
            'price_final'      => $final,
            'discount_percent' => $this->discountPercent($initial, $final),
            // Running water marks for this offer, i.e. per store — not across
            // stores. `price_final <= lowest_final && highest_final > price_final`
            // is what the site calls a historical low.
            'lowest_final'     => $price->lowest_final !== null ? (int)$price->lowest_final : null,
            'highest_final'    => $price->highest_final !== null ? (int)$price->highest_final : null,
            'updated_at'       => $this->datetime($price->updated_at ?: $price->created_at),
        ];
    }

    public function discountPercent(?int $initial, ?int $final): int
    {
        if (!$initial || !$final || $final >= $initial) {
            return 0;
        }

        return (int)round((($initial - $final) / $initial) * 100);
    }

    /** Turns a site-relative asset path into an absolute URL; leaves URLs alone. */
    private function absolute(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        return str_starts_with($url, 'http') ? $url : Url::to($url, true);
    }

    /** Absolute canonical URL of the game's page here, for cross-linking back. */
    public function gameUrl(Game $game): ?string
    {
        if (!$game->steam_appid || !$game->slug) {
            return null;
        }

        return Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug], true);
    }

    private function has(string $include): bool
    {
        return isset($this->includes[$include]);
    }

    /**
     * Steam's own price. Always USD: appdetails is queried without a country
     * override, so this is the US storefront price — unlike offer prices, which
     * carry their real currency.
     *
     * @return array<string, mixed>|null
     */
    private function steamPrice(Game $game): ?array
    {
        $initial = (int)$game->steam_price_initial;
        $final = (int)$game->steam_price_final;

        if ($initial <= 0 && $final <= 0) {
            return null;
        }

        return [
            'currency'         => 'USD',
            'price_initial'    => $initial ?: null,
            'price_final'      => $final ?: null,
            'discount_percent' => $this->discountPercent($initial ?: null, $final ?: null),
        ];
    }

    /**
     * @return int[]
     */
    private function contentDescriptors(Game $game): array
    {
        if (!$game->content_descriptors) {
            return [];
        }

        return array_values(array_filter(array_map(
            'intval',
            explode(',', (string)$game->content_descriptors),
        )));
    }

    /**
     * Header/background/icon URLs. Unlike the site helpers these return null
     * when nothing is stored — a consumer must not inherit our local
     * placeholder paths.
     *
     * @return array<string, string|null>
     */
    private function images(Game $game): array
    {
        return [
            'header'     => $this->imageUrls($game, GameImage::TYPE_HEADER)[0] ?? null,
            'background' => $this->imageUrls($game, GameImage::TYPE_BACKGROUND)[0] ?? null,
            'icon'       => $this->imageUrls($game, GameImage::TYPE_ICON)[0] ?? null,
        ];
    }

    /**
     * @return string[]
     */
    private function imageUrls(Game $game, string $type): array
    {
        $urls = [];

        foreach ($game->images as $image) {
            if ($image->type !== $type || (int)$image->status !== GameImage::STATUS_ACTIVE) {
                continue;
            }
            if ($image->url) {
                $urls[] = $image->url;
            }
        }

        return $urls;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function videos(Game $game): array
    {
        return array_map(static fn(GameVideo $video): array => [
            'provider'      => $video->provider,
            'video_id'      => $video->video_id,
            'url'           => $video->url,
            'title'         => $video->title,
            'thumbnail_url' => $video->getThumbnailUrl(),
        ], $game->videos);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tags(Game $game): array
    {
        $tags = [];

        foreach ($game->gameTags as $gameTag) {
            $tag = $gameTag->tag ?? null;
            if ($tag === null) {
                continue;
            }

            $tags[] = [
                'id'    => (int)$tag->id,
                'name'  => $tag->name,
                'slug'  => $tag->slug ?? null,
                'votes' => (int)$gameTag->order,
            ];
        }

        return $tags;
    }

    /**
     * Genre/category/developer/publisher rows share the id+name+slug shape.
     *
     * @param object[] $models
     * @return array<int, array<string, mixed>>
     */
    private function named(array $models): array
    {
        return array_map(static fn(object $model): array => [
            'id'   => (int)$model->id,
            'name' => $model->name,
            'slug' => $model->slug ?? null,
        ], $models);
    }

    /**
     * Operating systems, NOT console families: this catalogue is Steam-sourced,
     * so `platform` here means windows/mac/linux. Anything about PlayStation,
     * Xbox or Nintendo has to come from elsewhere.
     *
     * @return array<string, bool>
     */
    private function platforms(Game $game): array
    {
        $platforms = ['windows' => false, 'mac' => false, 'linux' => false];

        foreach ($game->platforms as $platform) {
            $name = strtolower((string)$platform->name);
            if (array_key_exists($name, $platforms)) {
                $platforms[$name] = (bool)$platform->available;
            }
        }

        return $platforms;
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function requirements(Game $game): array
    {
        $requirements = [];

        foreach ($game->platforms as $platform) {
            if (!$platform->available) {
                continue;
            }

            $requirements[strtolower((string)$platform->name)] = [
                'minimum'     => $platform->requirements_minimum ?: null,
                'recommended' => $platform->requirements_recommended ?: null,
            ];
        }

        return $requirements;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function metacritic(Game $game): ?array
    {
        $metacritic = $game->metacritic;
        if ($metacritic === null) {
            return null;
        }

        return [
            'score'      => $metacritic->score !== null ? (int)$metacritic->score : null,
            'user_score' => $metacritic->user_score !== null ? (int)$metacritic->user_score : null,
            'url'        => $metacritic->url,
        ];
    }

    /**
     * Steam review counts as of the last sync.
     *
     * @return array<string, mixed>|null
     */
    private function reviews(Game $game): ?array
    {
        $review = $game->review;
        if ($review === null) {
            return null;
        }

        $total = (int)$review->total_reviews;
        $positive = (int)$review->total_positive;

        return [
            'total'            => $total,
            'positive'         => $positive,
            'negative'         => (int)$review->total_negative,
            'positive_percent' => $total > 0 ? (int)round($positive / $total * 100) : null,
            'description'      => $review->description,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function offers(Game $game): array
    {
        $offers = [];

        foreach ($game->getActiveOffers() as $offer) {
            $offers[] = $this->offer($offer);
        }

        return $offers;
    }

    /** Latest of created/updated/synchronized — see the `changed_at` field. */
    private function changedAt(Game $game): ?string
    {
        $stamps = array_filter([
            $game->created_at,
            $game->updated_at,
            $game->synchronized_at,
        ]);

        return $stamps === [] ? null : $this->datetime(max($stamps));
    }

    private function date(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function datetime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('c', $timestamp);
    }
}
