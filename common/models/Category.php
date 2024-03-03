<?php

namespace common\models;

use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $description
 * @property string|null $image
 * @property string|null $slug
 * @property int|null    $status
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game[]      $games
 */
class Category extends ActiveRecord
{
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
                'attribute'     => ['name'],
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
        return '{{%category}}';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * Gets query for [[Games]].
     *
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getGames()
    {
        return $this->hasMany(Game::className(), ['id' => 'game_id'])->viaTable('{{%game_category}}', ['category_id' => 'id']);
    }

    public function getImage()
    {
        if ($this->image) {
            return $this->image;
        }

        return '/img/category-default.png';
    }
}