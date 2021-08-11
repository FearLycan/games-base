<?php


namespace common\models;


use Yii;
use yii\base\BaseObject;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

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
 */
class Game extends \yii\db\ActiveRecord
{
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
     * Gets query for [[Publishers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPublishers()
    {
        return $this->hasMany(Publisher::className(), ['id' => 'publisher_id'])->viaTable('{{%game_publisher}}', ['game_id' => 'id']);
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
        //die(var_dump($information['is_free']));
        $this->title = $information['name'];
        $this->required_age = (boolean)$information['required_age'];
        $this->is_free = $information['is_free'];
        $this->type = $information['type'];
        $this->detailed_description = $information['detailed_description'];
        $this->about_the_game = $information['about_the_game'];
        $this->short_description = $information['short_description'];
        $this->release_date = (new \DateTime($information['release_date']['date']))->format('Y-m-d 00:00:00');
        $this->website = $information['website'];
        $this->save();

        $this->setCategories($information['categories']);
        $this->setDevelopers($information['developers']);
        $this->setPublisher($information['publishers']);
        $this->setGenres($information['genres']);
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


}