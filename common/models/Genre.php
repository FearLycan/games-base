<?php

namespace common\models;

use Yii;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%genre}}".
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $image
 * @property int|null    $status
 * @property int         $games_count   precomputed by console `genre/recount`
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game[]      $games
 */
class Genre extends ActiveRecord
{
    private const int COUNT_CACHE_TTL = 86400;
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
                'attribute'     => ['name'],
                'slugAttribute' => 'slug',
                'ensureUnique'  => false,
                'immutable'     => true,
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%genre}}';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'image'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'name'        => 'Name',
            'description' => 'Description',
            'image'       => 'Image',
            'status'      => 'Status',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function getGames(): ActiveQuery
    {
        return $this->hasMany(Game::class, ['id' => 'game_id'])->viaTable('{{%game_genre}}', ['genre_id' => 'id']);
    }
}
