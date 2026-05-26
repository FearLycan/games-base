<?php

namespace common\models;

use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%tag}}".
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $image
 * @property int|null    $status
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property GameTag[]   $gameTags
 * @property Game[]      $games
 */
class Tag extends ActiveRecord
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
        return '{{%tag}}';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'slug', 'image'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'name'        => 'Name',
            'slug'        => 'Slug',
            'description' => 'Description',
            'image'       => 'Image',
            'status'      => 'Status',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function getGameTags(): ActiveQuery
    {
        return $this->hasMany(GameTag::class, ['tag_id' => 'id']);
    }

    public function getGames(): ActiveQuery
    {
        return $this->hasMany(Game::class, ['id' => 'game_id'])->viaTable('{{%game_tag}}', ['tag_id' => 'id']);
    }
}
