<?php

namespace common\models;

use common\components\GameQuery;
use common\components\Helper;
use Yii;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\httpclient\Client;

/**
 * This is the model class for table "{{%game}}".
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $slug
 * @property int|null $steam_appid
 * @property int|null $required_age
 * @property int|null $is_free
 * @property string|null $type
 * @property int|null $status
 * @property string|null $detailed_description
 * @property string|null $about_the_game
 * @property string|null $short_description
 * @property string|null $release_date
 * @property string|null $website
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Category[] $categories
 * @property Developer[] $developers
 * @property GameImage[] $images
 * @property Genre[] $genres
 * @property Platform[] $platforms
 * @property Publisher[] $publishers
 * @property Metacritic $metacritic
 * @property Review $review
 */
class Game extends \yii\db\ActiveRecord
{
    const  STATUS_ACTIVE = 1;
    const  STATUS_INACTIVE = 0;

    private $_screenshots;
    private $_icon;
    private $_background;

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => date("Y-m-d H:i:s"),
            ],
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => ['title'],
                'slugAttribute' => 'slug',
                'ensureUnique' => false,
                'immutable' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['steam_appid', 'status'], 'integer'],
            [['required_age', 'is_free'], 'boolean'],
            [['detailed_description', 'about_the_game', 'short_description'], 'string'],
            [['release_date', 'created_at', 'updated_at'], 'safe'],
            [['title', 'type', 'website'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'steam_appid' => 'Steam Appid',
            'required_age' => 'Required Age',
            'is_free' => 'Is Free',
            'type' => 'Type',
            'status' => 'Status',
            'detailed_description' => 'Detailed Description',
            'about_the_game' => 'About The Game',
            'short_description' => 'Short Description',
            'release_date' => 'Release Date',
            'website' => 'Website',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     *
     * @return GameQuery
     */
    public static function find()
    {
        return new GameQuery(get_called_class());
    }

    /**
     * Gets query for [[Categories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['id' => 'category_id'])->viaTable('{{%game_category}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Developers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDevelopers()
    {
        return $this->hasMany(Developer::className(), ['id' => 'developer_id'])->viaTable('{{%game_developer}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[GameImages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(GameImage::className(), ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Genres]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGenres()
    {
        return $this->hasMany(Genre::className(), ['id' => 'genre_id'])->viaTable('{{%game_genre}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Platforms]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlatforms()
    {
        return $this->hasMany(Platform::className(), ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Metacritic]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMetacritic()
    {
        return $this->hasOne(Metacritic::className(), ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Publishers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPublishers()
    {
        return $this->hasMany(Publisher::className(), ['id' => 'publisher_id'])->viaTable('{{%game_publisher}}', ['game_id' => 'id']);
    }

    /**
     * Gets query for [[Review]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReview()
    {
        return $this->hasOne(Review::className(), ['game_id' => 'id']);
    }

    public function setReview()
    {
        $client = new Client(['baseUrl' => 'https://store.steampowered.com/appreviews/' . $this->steam_appid]);

        $request = $client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET')
            ->setData([
                'json' => 1,
                'start_offset' => 1,
                'num_per_page' => 1
            ]);

        $response = $request->send();

        if ($response->isOk && $response->data['success']) {
            $review = Review::findOne(['game_id' => $this->id]);

            if (!$review) {
                $review = new Review();
                $review->game_id = $this->id;
            }

            $review->total_negative = $response->data['query_summary']['total_negative'];
            $review->total_positive = $response->data['query_summary']['total_positive'];
            $review->total_reviews = $response->data['query_summary']['total_reviews'];

            $review->save();
        }
    }

    public function createApp($app)
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

    public function setBaseInformation($information)
    {
        $this->title = $information['name'];
        $this->required_age = (boolean)$information['required_age'];
        $this->is_free = $information['is_free'];
        $this->type = $information['type'];
        $this->detailed_description = Helper::clearHtml($information['detailed_description']);
        $this->about_the_game = Helper::clearHtml($information['about_the_game']);
        $this->short_description = Helper::clearHtml($information['short_description']);
        $this->release_date = (new \DateTime($information['release_date']['date']))->format('Y-m-d 00:00:00');
        $this->website = $information['website'];
        $this->save();

        $this->setCategories($information['categories']);
        $this->setDevelopers($information['developers']);
        $this->setPublisher($information['publishers']);
        $this->setGenres($information['genres']);
        $this->setScreenshots($information['screenshots']);
        $this->setBackground($information['background']);
        $this->setIcons();
        $this->setPlatforms($information);
        $this->setReview();

        if (isset($information['metacritic'])) {
            $this->setMetacritic($information['metacritic']);
        }

        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    public function setCategories($categories)
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

    public function setScreenshots($screenshots)
    {
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

    public function setBackground($background)
    {
        $image = GameImage::findOne([
            'url' => $background,
            'type' => GameImage::TYPE_BACKGROUND,
            'game_id' => $this->id
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
                'type' => GameImage::TYPE_BACKGROUND,
            ])->one();
        }

        if (!$this->_background) {
            return '/img/background-default.jpg';
        }

        return $this->_background->url;
    }

    public function getIcon()
    {
        if (empty($this->_icon)) {
            $this->_icon = $this->getImages()->where([
                'status' => GameImage::STATUS_ACTIVE,
                'type' => GameImage::TYPE_ICON,
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
                    'type' => GameImage::TYPE_ICON,
                    'url' => $icon['url']
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
                'type' => GameImage::TYPE_SCREENSHOT,
            ])->all();
        }

        return $this->_screenshots;
    }

    public function setPlatforms($information)
    {
        foreach ($information['platforms'] as $platform => $available) {

            $requirements = Platform::findOne([
                'game_id' => $this->id,
                'name' => $platform,
            ]);

            if (!$requirements) {
                $requirements = new Platform();
                $requirements->game_id = $this->id;
            }

            $requirements->name = $platform;
            $requirements->available = $available;

            $required = $platform == 'windows' ? 'pc_requirements' : $platform . '_requirements';

            if ($available) {
                $requirements->requirements_minimum = isset($information[$required]['minimum']) ? $information[$required]['minimum'] : '';
                $requirements->requirements_recommended = isset($information[$required]['recommended']) ? $information[$required]['recommended'] : '';
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
        $meta->url = $metacritic['url'];
        $meta->save();

    }

    public function getSteamUrl()
    {
        return 'https://store.steampowered.com/app/' . $this->steam_appid;
    }

    public function getAvailablePlatforms()
    {
        return $this->getPlatforms()->where(['available' => true])->all();
    }
}