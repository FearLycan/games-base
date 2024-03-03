<?php

namespace common\models;

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
 */
class Game extends ActiveRecord
{
    const  STATUS_ACTIVE       = 1;
    const  STATUS_WAIT_TO_SYNC = 0;
    const  STATUS_INACTIVE     = 3;

    const STEAM_DECK_VERIFIED    = 4;
    const STEAM_DECK_PLAYABLE    = 3;
    const STEAM_DECK_UNSUPPORTED = 2;

    /** @var GameImage */
    private $_screenshots;

    /** @var GameImage */
    private $_icon;

    /** @var GameImage */
    private $_background;

    /** @var GameImage */
    private $_header;

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
            [['steam_appid', 'status', 'steam_price_final', 'steam_price_initial'], 'integer'],
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
     *
     * @return GameQuery
     */
    public static function find()
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
     * Gets query for [[GameSales]].
     *
     * @param int $type
     * @param int $limit
     * @return Game[]
     */
    public static function getSales(int $type, int $limit = 30): array
    {
        return Game::find()
            ->joinWith(['gameSales'])
            ->where([
                'game_sale.type' => $type,
                'game.status'    => self::STATUS_ACTIVE,
                'game.type'      => 'game',
            ])
            ->orderBy(['game_sale.order' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    public function isBestseller(): bool
    {
        foreach ($this->gameSales as $sale) {
            if ((int)$sale->type === GameSale::TYPE_BESTSELLERS) {
                return true;
            }
        }

        return false;
    }

    public function isPopularUpcoming(): bool
    {
        foreach ($this->gameSales as $sale) {
            if ((int)$sale->type === GameSale::TYPE_POPULAR_UPCOMING) {
                return true;
            }
        }

        return false;
    }

    public function isNewAndNoteworthy(): bool
    {
        foreach ($this->gameSales as $sale) {
            if ((int)$sale->type === GameSale::TYPE_NEW_AND_NOTEWORTHY) {
                return true;
            }
        }

        return false;
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

    public function createApp($app): self
    {
        $game = self::findOne(['steam_appid' => $app['appid']]);

        if (!$game) {
            $game = new self();
            $game->steam_appid = $app['appid'];
            $game->title = $app['name'];
            $game->save();
        }

        return $game;
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
        $this->save();

        $this->setCategories($information['categories'] ?? []);
        $this->setDevelopers($information['developers'] ?? []);
        $this->setPublisher($information['publishers'] ?? []);
        $this->setGenres($information['genres'] ?? []);
        $this->setScreenshots($information['screenshots']);
        $this->setBackground($information['background']);
        $this->setHeader($information['header_image']);
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
                    'url'     => $icon['url'],
                ]);

                if (!$image) {
                    $image = new GameImage();
                    $image->type = GameImage::TYPE_ICON;
                    $image->url = $icon['url'];
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
        $meta->url = $metacritic['url'];
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

    public function getAvailablePlatforms(): array
    {
        return $this->getPlatforms()->where(['available' => true])->all();
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

    public function getMainGenre(): string
    {
        if (isset($this->genres[0])) {
            return $this->genres[0]->name;
        }

        return '';
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

    public static function count(): string
    {
        $cache = Yii::$app->cache;
        $key = "keyGamesCount";
        $games = $cache->getOrSet($key, function () {
            return self::find()
                ->where([
                    'status' => self::STATUS_ACTIVE,
                    'type'   => 'game',
                ])->count();
        }, 60 * 60 * 24);

        return number_format($games);
    }
}