<?php

namespace common\models;

use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%publisher}}".
 *
 * @property int             $id
 * @property string|null     $name
 * @property string|null     $slug
 * @property int|null        $status
 * @property string          $created_at
 * @property string|null     $updated_at
 *
 * @property GamePublisher[] $gamePublishers
 * @property Game[]          $games
 */
class Publisher extends ActiveRecord
{
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
        return '{{%publisher}}';
    }

    public function rules(): array
    {
        return [
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'name'       => 'Name',
            'status'     => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getGamePublishers(): ActiveQuery
    {
        return $this->hasMany(GamePublisher::class, ['publisher_id' => 'id']);
    }

    public function getGames(): ActiveQuery
    {
        return $this->hasMany(Game::class, ['id' => 'game_id'])->viaTable('{{%game_publisher}}', ['publisher_id' => 'id']);
    }
}
