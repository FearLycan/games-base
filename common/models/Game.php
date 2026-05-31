<?php

namespace common\models;

use common\components\CurrencyResolver;
use common\components\DisplayPrice;
use common\components\GameQuery;
use common\components\Helper;
use DateTime;
use Symfony\Component\DomCrawler\Crawler;
use Yii;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\httpclient\Client;

/**
 * This is the model class for table "{{%game}}".
 *
 * @property int            $id
 * @property string|null    $title
 * @property string|null    $slug
 * @property int|null       $steam_appid
 * @property int|null       $fullgame_appid
 * @property int|null       $required_age
 * @property int|null       $is_free
 * @property string|null    $type
 * @property int|null       $status
 * @property string|null    $detailed_description
 * @property string|null    $about_the_game
 * @property string|null    $short_description
 * @property string|null    $release_date
 * @property string|null    $website
 * @property int|null       $steam_deck
 * @property int|null       $steam_price_initial
 * @property int|null       $steam_price_final
 * @property boolean        $force_sync
 * @property string         $created_at
 * @property string|null    $updated_at
 * @property string|null    $synchronized_at
 *
 * @property Category[]     $categories
 * @property Developer[]    $developers
 * @property GameImage[]    $images
 * @property Genre[]        $genres
 * @property Tag[]          $tags
 * @property Platform[]     $platforms
 * @property Publisher[]    $publishers
 * @property Metacritic     $metacritic
 * @property Review         $review
 * @property GameTag[]      $gameTags
 * @property GameCategory[] $gameCategories
 * @property GameGenre[]    $gameGenres
 * @property GameSale[]     $gameSales
 * @property GameOffer[]    $gameOffers
 * @property Game|null      $fullGame
 * @property Game[]         $dlc
 */
class Game extends ActiveRecord
{
    public const int STATUS_ACTIVE        = 1;
    public const int STATUS_WAIT_TO_SYNC  = 0;
    public const int STATUS_INACTIVE      = 3;
    public const int STATUS_SUCCESS_FALSE = 10;

    public const int STEAM_DECK_VERIFIED    = 4;
    public const int STEAM_DECK_PLAYABLE    = 3;
    public const int STEAM_DECK_UNSUPPORTED = 2;

    public const string TYPE_GAME  = 'game';
    public const string TYPE_DLC   = 'dlc';
    public const string TYPE_MUSIC = 'music';
    public const string TYPE_DEMO  = 'demo';

    private const int SALES_CACHE_TTL = 3600;

    private ?array     $_screenshots = null;
    private ?GameImage $_icon = null;
    private ?GameImage $_background = null;
    private ?GameImage $_header = null;
    private ?array     $_availablePlatforms = null;
    private ?string    $_mainGenre = null;
    /** @var array<int, true>|null map of sale type IDs this game belongs to */
    private ?array     $_saleTypes = null;
    /** @var GameOffer[]|null active store offers, store eager-loaded */
    private ?array     $_activeOffers = null;
    /** @var array<string, GameOffer[]> sorted offers, keyed by display currency */
    private array      $_sortedOffers = [];

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => date("Y-m-d H:i:s"),
            ],
            'sluggable' => [
                'class'         => SluggableBehavior::class,
                'attribute'     => ['title'],
                'slugAttribute' => 'slug',
                'ensureUnique'  => false,
                'immutable'     => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%game}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['steam_appid', 'fullgame_appid', 'status', 'steam_price_final', 'steam_price_initial'], 'integer'],
            [['required_age', 'is_free', 'force_sync'], 'boolean'],
            [['detailed_description', 'about_the_game', 'short_description'], 'string'],
            [['release_date', 'created_at', 'updated_at', 'synchronized_at'], 'safe'],
            [['title', 'type', 'website'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id'                   => 'ID',
            'title'                => 'Title',
            'steam_appid'          => 'Steam Appid',
            'required_age'         => 'Required Age',
            'is_free'              => 'Is Free',
            'type'                 => 'Type',
            'status'               => 'Status',
            'detailed_description' => 'Detailed Description',
            'about_the_game'       => 'About The Game',
            'short_description'    => 'Short Description',
            'release_date'         => 'Release Date',
            'website'              => 'Website',
            'created_at'           => 'Created At',
            'updated_at'           => 'Updated At',
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($this->isNewRecord) {
            $this->synchronized_at = '2022-12-20 22:08:14';
        }

        return parent::beforeSave($insert);
    }

    /**
     * Flags the game for re-sync if it hasn't been synchronized in over a day.
     * Call this from the detail-page load (on cache miss) so that games users
     * actually view get refreshed more often than the rest of the catalogue.
     * Must NOT live in afterFind(): that writes to the DB on every read
     * (list pages, console iteration) and mutates result sets mid-iteration.
     */
    public function checkSyncDate(): void
    {
        if (!$this->synchronized_at || (int)$this->force_sync === 1) {
            return;
        }

        $synchronizedAt = new DateTime($this->synchronized_at);
        $threshold = (new DateTime('now'))->modify('-1 day');

        if ($synchronizedAt < $threshold) {
            $this->updateAttributes(['force_sync' => true]);
        }
    }

    /**
     *
     * @return GameQuery
     */
    public static function find(): GameQuery
    {
        return new GameQuery(static::class);
    }

    /**
     * Gets query for [[Categories]].
     *
     * @return ActiveQuery
     */
    public function getCategories(): ActiveQuery
    {
        return $this->hasMany(Category::class, ['id' => 'category_id'])->viaTable('{{%game_category}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Developers]].
     *
     * @return ActiveQuery
     */
    public function getDevelopers(): ActiveQuery
    {
        return $this->hasMany(Developer::class, ['id' => 'developer_id'])->viaTable('{{%game_developer}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[GameImages]].
     *
     * @return ActiveQuery
     */
    public function getImages(): ActiveQuery
    {
        return $this->hasMany(GameImage::class, ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Genres]].
     *
     * @return ActiveQuery
     */
    public function getGenres(): ActiveQuery
    {
        return $this->hasMany(Genre::class, ['id' => 'genre_id'])->viaTable('{{%game_genre}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[GameCategories]].
     *
     * @return ActiveQuery
     */
    public function getGameCategories(): ActiveQuery
    {
        return $this->hasMany(GameCategory::class, ['game_id' => 'id']);
    }

    /**
     * Gets query for [[GameGenres]].
     *
     * @return ActiveQuery
     */
    public function getGameGenres(): ActiveQuery
    {
        return $this->hasMany(GameGenre::class, ['game_id' => 'id']);
    }

    /**
     * Gets query for [[GameTags]].
     *
     * @return ActiveQuery
     */
    public function getGameTags(): ActiveQuery
    {
        return $this->hasMany(GameTag::class, ['game_id' => 'id'])->orderBy(['order' => SORT_DESC]);
    }

    /**
     * Gets query for [[Tags]].
     *
     * @return ActiveQuery
     */
    public function getTags(): ActiveQuery
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->via('gameTags');
    }

    /**
     * Gets query for [[Platforms]].
     *
     * @return ActiveQuery
     */
    public function getPlatforms(): ActiveQuery
    {
        return $this->hasMany(Platform::class, ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Metacritic]].
     *
     * @return ActiveQuery
     */
    public function getMetacritic(): ActiveQuery
    {
        return $this->hasOne(Metacritic::class, ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Publishers]].
     *
     * @return ActiveQuery
     */
    public function getPublishers(): ActiveQuery
    {
        return $this->hasMany(Publisher::class, ['id' => 'publisher_id'])->viaTable('{{%game_publisher}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Review]].
     *
     * @return ActiveQuery
     */
    public function getReview(): ActiveQuery
    {
        return $this->hasOne(Review::class, ['game_id' => 'id']);
    }

    /**
     * @return Game[]
     */
    public static function getSales(int $type, int $limit = 30): array
    {
        $key = ['game.sales', $type, $limit];

        return Yii::$app->cache->getOrSet($key, static fn(): array => self::find()
            ->joinWith(['gameSales'])
            ->where([
                'game_sale.type' => $type,
                'game.status'    => self::STATUS_ACTIVE,
                'game.type'      => self::TYPE_GAME,
            ])
            // Eager-load the data the cards render (genre + cheapest offer with
            // prices/store) so the cached list carries it too — homepage and
            // sale pages then render prices without a query per game.
            ->with([
                'genres',
                'gameOffers' => static function ($q): void {
                    $q->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])
                        ->orderBy(['game_offer.order' => SORT_ASC])
                        ->with(['store', 'prices']);
                },
            ])
            ->orderBy(['game_sale.order' => SORT_ASC])
            ->limit($limit)
            ->all(), self::SALES_CACHE_TTL);
    }

    /**
     * Resolves which sale types this game belongs to in a single pass.
     * Subsequent is*() calls are O(1) lookups against the cached map.
     *
     * @return array<int, true>
     */
    private function saleTypes(): array
    {
        if ($this->_saleTypes === null) {
            $this->_saleTypes = [];
            foreach ($this->gameSales as $sale) {
                $this->_saleTypes[(int)$sale->type] = true;
            }
        }

        return $this->_saleTypes;
    }

    public function isBestseller(): bool
    {
        return isset($this->saleTypes()[GameSale::TYPE_BESTSELLERS]);
    }

    public function isPopularUpcoming(): bool
    {
        return isset($this->saleTypes()[GameSale::TYPE_POPULAR_UPCOMING]);
    }

    public function isNewAndNoteworthy(): bool
    {
        return isset($this->saleTypes()[GameSale::TYPE_NEW_AND_NOTEWORTHY]);
    }

    /**
     * Gets query for [[GameSales]].
     *
     * @return ActiveQuery
     */
    public function getGameSales(): ActiveQuery
    {
        return $this->hasMany(GameSale::class, ['game_id' => 'id']);
    }

    /**
     * Gets query for [[GameOffers]].
     *
     * @return ActiveQuery
     */
    public function getGameOffers(): ActiveQuery
    {
        return $this->hasMany(GameOffer::class, ['game_id' => 'id']);
    }

    /**
     * The base game this row is a DLC of, or null when it isn't a DLC.
     * Linked by Steam appid (see {@see $fullgame_appid}), so it resolves even
     * when the two rows were synced in either order.
     *
     * @return ActiveQuery
     */
    public function getFullGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['steam_appid' => 'fullgame_appid']);
    }

    /**
     * Active DLC / add-ons that belong to this game, newest first.
     *
     * @return ActiveQuery
     */
    public function getDlc(): ActiveQuery
    {
        return $this->hasMany(Game::class, ['fullgame_appid' => 'steam_appid'])
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->orderBy(['release_date' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function isDlc(): bool
    {
        return $this->type === self::TYPE_DLC;
    }

    /**
     * Active store offers for this game, ordered for display, with the related
     * store eager-loaded. Cached per request.
     *
     * @return GameOffer[]
     */
    public function getActiveOffers(): array
    {
        // Free-to-play games are never sold elsewhere — no offers shown.
        if ((int)$this->is_free === 1) {
            return $this->_activeOffers ??= [];
        }

        if ($this->_activeOffers !== null) {
            return $this->_activeOffers;
        }

        // On list/homepage pages the offers are eager-loaded for the whole page
        // (see GameSearch::search() and self::getSales()), so reuse the loaded
        // relation instead of firing one query per card. The active-status
        // filter is reapplied here so we stay correct regardless of how the
        // relation was populated; ordering only matters as a tie-break since
        // getSortedOffers() re-sorts by price.
        if ($this->isRelationPopulated('gameOffers')) {
            return $this->_activeOffers = array_values(array_filter(
                $this->gameOffers,
                static fn(GameOffer $offer): bool => (int)$offer->status === GameOffer::STATUS_ACTIVE,
            ));
        }

        return $this->_activeOffers = $this->getGameOffers()
            ->with(['store', 'prices'])
            ->where(['game_offer.status' => GameOffer::STATUS_ACTIVE])
            ->orderBy(['game_offer.order' => SORT_ASC])
            ->all();
    }

    /**
     * Active offers ordered cheapest-first for the given display currency.
     * Offers that have no price in that currency sort last (keeping their
     * relative `order`). The first element is therefore the best deal when it
     * has a price — see {@see getBestOffer()}.
     *
     * @return GameOffer[]
     */
    public function getSortedOffers(string $currency): array
    {
        $currency = strtoupper($currency);
        if (isset($this->_sortedOffers[$currency])) {
            return $this->_sortedOffers[$currency];
        }

        $offers = $this->getActiveOffers();

        usort($offers, static function (GameOffer $a, GameOffer $b) use ($currency): int {
            $pa = $a->getPrice($currency);
            $pb = $b->getPrice($currency);
            $fa = $pa && (int)$pa->price_final > 0 ? (int)$pa->price_final : PHP_INT_MAX;
            $fb = $pb && (int)$pb->price_final > 0 ? (int)$pb->price_final : PHP_INT_MAX;

            return $fa <=> $fb;
        });

        return $this->_sortedOffers[$currency] = $offers;
    }

    /**
     * The cheapest active offer that has a price in the given currency, or null.
     * Offers are sorted cheapest-first with priced ones ahead of price-less ones,
     * so the best deal is simply the first element when it has a price.
     *
     * @return GameOffer|null
     */
    public function getBestOffer(string $currency): ?GameOffer
    {
        $best = $this->getSortedOffers($currency)[0] ?? null;

        return $best && $best->getPrice($currency) ? $best : null;
    }

    /**
     * The price to surface on cards and lists: the cheapest store offer in the
     * visitor's currency when one exists, otherwise the Steam price. Free games
     * report a "Free" label. Returns null when there is nothing to show (e.g. an
     * unreleased title with no Steam price and no offers).
     *
     * Offers are read from the (eager-loaded, per-request memoized) relation, so
     * rendering this for a whole grid of cards costs no extra queries.
     *
     * @param string|null $currency display currency; defaults to the visitor's.
     */
    public function getDisplayPrice(?string $currency = null): ?DisplayPrice
    {
        if ((int)$this->is_free === 1) {
            return new DisplayPrice('Free', null, 0, true, DisplayPrice::SOURCE_FREE);
        }

        $currency ??= CurrencyResolver::forVisitor();

        $best = $this->getBestOffer($currency);
        if ($best !== null) {
            $price = $best->getPrice($currency);
            $finalLabel = $price?->getFinalPriceLabel();
            if ($finalLabel !== null) {
                $discount = $price->getDiscountPercent();

                return new DisplayPrice(
                    $finalLabel,
                    $discount > 0 ? $price->getInitialPriceLabel() : null,
                    $discount,
                    false,
                    DisplayPrice::SOURCE_OFFER,
                );
            }
        }

        if ((int)$this->steam_price_final > 0) {
            $discount = $this->getDiscountPercent();

            return new DisplayPrice(
                $this->getFinalPrice(),
                $discount > 0 ? $this->getInitialPrice() : null,
                $discount,
                false,
                DisplayPrice::SOURCE_STEAM,
            );
        }

        return null;
    }

    public function setReview()
    {
        $client = new Client(['baseUrl' => 'https://store.steampowered.com/appreviews/' . $this->steam_appid]);

        $request = $client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET')
            ->setData([
                'json'         => 1,
                'start_offset' => 1,
                'num_per_page' => 1,
                'language'     => 'all',
            ]);

        $response = $request->send();

        if ($response->isOk && $response->data['success']) {
            $review = Review::findOne(['game_id' => $this->id]);

            if (!$review) {
                $review = new Review();
                $review->game_id = $this->id;
            }

            $review->total_negative = $response->data['query_summary']['total_negative'] ?? 0;
            $review->total_positive = $response->data['query_summary']['total_positive'] ?? 0;
            $review->total_reviews = $response->data['query_summary']['total_reviews'] ?? 0;
            $review->description = $response->data['query_summary']['review_score_desc'] ?? null;

            $review->save();
        }
    }

    public function setBaseInformation($information): self
    {
        $this->title = $information['name'];
        $this->required_age = (boolean)$information['required_age'];
        $this->is_free = $information['is_free'];
        $this->type = $information['type'];
        $this->detailed_description = Helper::clearHtml($information['detailed_description']);
        $this->about_the_game = Helper::clearHtml($information['about_the_game']);
        $this->short_description = Helper::clearHtml($information['short_description']);
        $this->trySetReleaseDate($information['release_date']['date']);
        $this->website = $information['website'];
        $this->steam_price_initial = $information['price_overview']['initial'] ?? 0;
        $this->steam_price_final = $information['price_overview']['final'] ?? 0;
        // DLC entries carry `fullgame.appid` — the base game they belong to.
        // Captured from the DLC side (authoritative, one per DLC) rather than
        // walking the parent's `dlc` array; resolved later via getFullGame().
        $this->fullgame_appid = isset($information['fullgame']['appid'])
            ? (int)$information['fullgame']['appid']
            : null;
        $this->save();

        $this->setCategories($information['categories'] ?? []);
        $this->setDevelopers($information['developers'] ?? []);
        $this->setPublisher($information['publishers'] ?? []);
        $this->setGenres($information['genres'] ?? []);
        $this->setScreenshots($information['screenshots'] ?? []);
        $this->setBackground($information['background'] ?? '');
        $this->setHeader($information['header_image'] ?? '');
        $this->setIcons();
        $this->setPlatforms($information);
        $this->setReview();

        if (isset($information['metacritic'])) {
            $this->setMetacritic($information['metacritic']);
        }

        $informationFromWeb = $this->setInformationFromWeb();
        if ($informationFromWeb) {
            $tags = $this->extraTags($informationFromWeb);
            $this->setTags($tags);

            $steamDeck = $this->extractSteamDeckInformation($informationFromWeb);
            $this->setSteamDeck($steamDeck);
        }

        if ((int)$this->status === self::STATUS_WAIT_TO_SYNC) {
            $this->status = self::STATUS_ACTIVE;
        }

        $this->force_sync = false;
        $this->synchronized_at = date("Y-m-d H:i:s");
        $this->save();

        return $this;
    }

    public function trySetReleaseDate($date)
    {
        $date = DateTime::createFromFormat('M d, Y', $date);

        if ($date) {
            $this->release_date = $date->format('Y-m-d 00:00:00');
        }
    }

    public function setSteamDeck(array $steamDeck): self
    {
        if (isset($steamDeck['resolved_items'])) {
            foreach ($steamDeck['resolved_items'] as $options) {
                if ((int)$options['display_type'] === self::STEAM_DECK_UNSUPPORTED) {
                    $this->steam_deck = self::STEAM_DECK_UNSUPPORTED;
                    return $this;
                }

                if ((int)$options['display_type'] === self::STEAM_DECK_PLAYABLE) {
                    $this->steam_deck = self::STEAM_DECK_PLAYABLE;
                    return $this;
                }
            }

            $this->steam_deck = self::STEAM_DECK_VERIFIED;
        }

        return $this;
    }

    public function setCategories($categories): void
    {
        GameCategory::removeConnectionsByGameID($this->id);

        foreach ($categories as $item) {

            $category = Category::findOne($item['id']);

            if (!$category) {
                $category = new Category();
                $category->id = $item['id'];
            }

            $category->name = $item['description'];
            $category->save();

            GameCategory::createConnection($this->id, $category->id);
        }
    }

    public function setDevelopers($developers)
    {
        GameDeveloper::removeConnectionsByGameID($this->id);

        foreach ($developers as $name) {

            $name = trim($name);
            $developer = Developer::findOne(['name' => $name]);

            if (!$developer) {
                $developer = new Developer();
                $developer->name = $name;
                $developer->save();
            }

            GameDeveloper::createConnection($this->id, $developer->id);
        }
    }

    public function setPublisher($publishers)
    {
        GamePublisher::removeConnectionsByGameID($this->id);

        foreach ($publishers as $name) {

            $name = trim($name);
            $developer = Publisher::findOne(['name' => $name]);

            if (!$developer) {
                $developer = new Publisher();
                $developer->name = $name;
                $developer->save();
            }

            GamePublisher::createConnection($this->id, $developer->id);
        }
    }

    public function setGenres($genres)
    {
        GameGenre::removeConnectionsByGameID($this->id);

        foreach ($genres as $item) {

            $genre = Genre::findOne($item['id']);

            if (!$genre) {
                $genre = new Genre();
                $genre->id = $item['id'];
            }

            $genre->name = $item['description'];
            $genre->save();

            GameGenre::createConnection($this->id, $genre->id);
        }
    }

    public function setTags(?array $tags)
    {
        if (!$tags) {
            return;
        }


        GameTag::removeConnectionsByGameID($this->id);

        foreach ($tags as $key => $item) {
            $tag = Tag::findOne(['id' => $item['tagid']]);

            if (!$tag) {
                $tag = new Tag();
                $tag->id = $item['tagid'];
            }

            $tag->name = $item['name'];
            $tag->save();

            GameTag::createConnection($this->id, $tag->id, $item['count']);
        }
    }

    public function setScreenshots($screenshots)
    {
        if ($screenshots) {
            GameImage::deleteAll(['game_id' => $this->id, 'type' => GameImage::TYPE_SCREENSHOT]);

            foreach ($screenshots as $screenshot) {
                $image = new GameImage();
                $image->type = GameImage::TYPE_SCREENSHOT;
                $image->url = $screenshot['path_full'];
                $image->game_id = $this->id;
                $image->status = GameImage::STATUS_ACTIVE;
                $image->save();
            }
        }
    }

    public function setBackground($background)
    {
        $image = GameImage::findOne([
            'type'    => GameImage::TYPE_BACKGROUND,
            'game_id' => $this->id,
        ]);

        if (!$image) {
            $image = new GameImage();
            $image->type = GameImage::TYPE_BACKGROUND;
            $image->url = $background;
            $image->game_id = $this->id;
            $image->status = GameImage::STATUS_ACTIVE;
            $image->save();
        }
    }

    public function getBackground()
    {
        if (empty($this->_background)) {
            $this->_background = $this->getImages()->where([
                'status' => GameImage::STATUS_ACTIVE,
                'type'   => GameImage::TYPE_BACKGROUND,
            ])->one();
        }

        if (!$this->_background) {
            return '/img/background-default.jpg';
        }

        return $this->_background->url;
    }

    public function setHeader($header)
    {
        $image = GameImage::findOne([
            'type'    => GameImage::TYPE_HEADER,
            'game_id' => $this->id,
        ]);

        if (!$image) {
            $image = new GameImage();
            $image->type = GameImage::TYPE_HEADER;
            $image->url = $header;
            $image->game_id = $this->id;
            $image->status = GameImage::STATUS_ACTIVE;
            $image->save();
        }
    }

    public function getHeader()
    {
        if (empty($this->_header)) {
            $this->_header = $this->getImages()->where([
                'status' => GameImage::STATUS_ACTIVE,
                'type'   => GameImage::TYPE_HEADER,
            ])->one();
        }

        if (!$this->_header) {
            return '/img/header-default.png';
        }

        return $this->_header->url;
    }

    public function getIcon()
    {
        if (empty($this->_icon)) {
            $this->_icon = $this->getImages()->where([
                'status' => GameImage::STATUS_ACTIVE,
                'type'   => GameImage::TYPE_ICON,
            ])->one();
        }

        if (!$this->_icon) {
            return '/img/icon-default.png';
        }

        return $this->_icon->url;
    }

    public function setIcons()
    {
        $client = new Client(['baseUrl' => 'https://www.steamgriddb.com/api/v2/icons/steam/' . $this->steam_appid]);

        $request = $client->createRequest()
            ->setMethod('GET')
            ->setHeaders(['Authorization' => 'Bearer ' . Yii::$app->params['steamgriddb_api_key']]);

        $response = $request->send();

        if ($response->isOk && $response->data['success']) {
            foreach ($response->data['data'] as $icon) {
                $image = GameImage::findOne([
                    'game_id' => $this->id,
                    'type'    => GameImage::TYPE_ICON,
                    'url'     => $icon['url'] ?? null,
                ]);

                if (!$image) {
                    $image = new GameImage();
                    $image->type = GameImage::TYPE_ICON;
                    $image->url = $icon['url'] ?? null;
                    $image->game_id = $this->id;
                    $image->status = GameImage::STATUS_ACTIVE;
                    $image->save();
                }
            }
        }
    }

    public function getScreenshots()
    {
        if (empty($this->_screenshots)) {
            $this->_screenshots = $this->getImages()->where([
                'status' => GameImage::STATUS_ACTIVE,
                'type'   => GameImage::TYPE_SCREENSHOT,
            ])->all();
        }

        return $this->_screenshots;
    }

    public function setPlatforms($information)
    {
        foreach ($information['platforms'] as $platform => $available) {

            $requirements = Platform::findOne([
                'game_id' => $this->id,
                'name'    => $platform,
            ]);

            if (!$requirements) {
                $requirements = new Platform();
                $requirements->game_id = $this->id;
            }

            $requirements->name = $platform;
            $requirements->available = $available;

            $required = $platform == 'windows' ? 'pc_requirements' : $platform . '_requirements';

            if ($available) {
                $requirements->requirements_minimum = $information[$required]['minimum'] ?? '';
                $requirements->requirements_recommended = $information[$required]['recommended'] ?? '';
            }

            $requirements->save();
        }
    }

    public function setMetacritic($metacritic)
    {
        $meta = Metacritic::findOne(['game_id' => $this->id]);

        if (!$meta) {
            $meta = new Metacritic();
            $meta->game_id = $this->id;
        }

        $meta->score = $metacritic['score'];
        $meta->user_score = 0;
        $meta->url = $metacritic['url'] ?? null;
        $meta->save();

        $webInformation = $this->setInformationMetacriticWeb();
        if ($webInformation) {
            $meta->user_score = $this->extractMetacriticUserScore($webInformation);
            $meta->update(false, ['user_score']);
        }

    }

    public function getSteamUrl()
    {
        return 'https://store.steampowered.com/app/' . $this->steam_appid;
    }

    /**
     * @return Platform[]
     */
    public function getAvailablePlatforms(): array
    {
        return $this->_availablePlatforms ??= $this->getPlatforms()
            ->where(['available' => true])
            ->all();
    }

    public function setInformationFromWeb()
    {
        $client = new Client(['baseUrl' => $this->getSteamUrl()]);

        $request = $client->createRequest()
            ->setCookies([
                ['name' => 'birthtime', 'value' => 0],
                ['name' => 'path', 'value' => '/'],
            ])
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET');

        $response = $request->send();

        if ($response->isOk) {
            return new Crawler($response->content);
        }

        return null;
    }

    public function setInformationMetacriticWeb()
    {
        if (!$this->metacritic->url) {
            return null;
        }

        $client = new Client(['baseUrl' => $this->metacritic->url]);

        $request = $client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET');

        $response = $request->send();

        if ($response->isOk) {
            return new Crawler($response->content);
        }

        return null;
    }

    public function extractMetacriticUserScore(Crawler $html): int
    {
        $crawler = $html->filter("div.metascore_w.user");

        try {
            $score = $crawler->first()->text();
            $score = str_replace(".", "", $score);
        } catch (\Exception $e) {
            $score = 0;
        }

        return (int)$score;
    }

    public function extraTags(Crawler $html): ?array
    {
        $tags = substr(Helper::getText($html->html(), '[{"tagid"', '}],'), 0, -1);

        try {
            $tags = Json::decode($tags, true);
        } catch (\Exception $e) {
            $tags = [];
        }

        return $tags;
    }

    public function extractSteamDeckInformation(Crawler $html)
    {
        $crawler = $html->filter("#application_config");

        try {
            $steamDeck = Json::decode($crawler->attr('data-deckcompatibility'));
        } catch (\Exception $e) {
            $steamDeck = [];
        }

        return $steamDeck ?? [];
    }

    public static function getSteamDecksStatuses(): array
    {
        return [
            self::STEAM_DECK_VERIFIED    => 'Verified',
            self::STEAM_DECK_PLAYABLE    => 'Playable',
            self::STEAM_DECK_UNSUPPORTED => 'Unsupported',
        ];
    }

    public function getSteamDecksStatusName()
    {
        if (isset(self::getSteamDecksStatuses()[$this->steam_deck])) {
            return self::getSteamDecksStatuses()[$this->steam_deck];
        }

        return $this->steam_deck;
    }

    public function getInitialPrice(): string
    {
        return '$' . number_format($this->steam_price_initial / 100, 2);
    }

    public function getFinalPrice(): string
    {
        return '$' . number_format($this->steam_price_final / 100, 2);
    }

    /**
     * Human price for CTAs: "Free to Play", a formatted price, or null when
     * there is no price to show (e.g. unreleased).
     */
    public function getPriceLabel(): ?string
    {
        if ((int)$this->is_free === 1) {
            return 'Free to Play';
        }

        if ((int)$this->steam_price_final > 0) {
            return $this->getFinalPrice();
        }

        return null;
    }

    /**
     * Discount percentage off the initial price, or 0 when not on sale.
     */
    public function getDiscountPercent(): int
    {
        $initial = (int)$this->steam_price_initial;
        $final = (int)$this->steam_price_final;
        if ($initial <= 0 || $final <= 0 || $final >= $initial) {
            return 0;
        }

        return (int)round((($initial - $final) / $initial) * 100);
    }

    public function getMainGenre(): string
    {
        return $this->_mainGenre ??= ($this->genres[0]->name ?? '');
    }

    public function getSaleLabel(): string
    {
        if ($this->isBestseller()) {
            return '<div class="label label-bestseller">Bestseller</div>';
        }

        if ($this->isNewAndNoteworthy()) {
            return '<div class="label label-new-and-noteworthy">New And Noteworthy</div>';
        }

        if ($this->isPopularUpcoming()) {
            return '<div class="label label-popular-upcoming">Popular Upcoming</div>';
        }

        return '';
    }

    /**
     * Companies that act as both developer AND publisher of this game.
     * Used to render a single "Studio" row instead of duplicating the name
     * across Developer and Publisher rows.
     *
     * @return Developer[]
     */
    public function getStudios(): array
    {
        $pubNames = array_map(static fn($p) => $p->name, $this->publishers);
        return array_values(array_filter(
            $this->developers,
            static fn($d) => in_array($d->name, $pubNames, true),
        ));
    }

    /**
     * Developers that don't also publish this game.
     *
     * @return Developer[]
     */
    public function getDevOnly(): array
    {
        $pubNames = array_map(static fn($p) => $p->name, $this->publishers);
        return array_values(array_filter(
            $this->developers,
            static fn($d) => !in_array($d->name, $pubNames, true),
        ));
    }

    /**
     * Publishers that don't also develop this game.
     *
     * @return Publisher[]
     */
    public function getPubOnly(): array
    {
        $devNames = array_map(static fn($d) => $d->name, $this->developers);
        return array_values(array_filter(
            $this->publishers,
            static fn($p) => !in_array($p->name, $devNames, true),
        ));
    }

    public static function count(): int
    {
        return (int)Yii::$app->cache->getOrSet('keyGamesCount', static fn(): int => (int)self::find()
            ->where([
                'status' => self::STATUS_ACTIVE,
                'type'   => 'game',
            ])->count(), 60 * 60 * 24);
    }
}