<?php

namespace common\models;

use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%store}}".
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $slug
 * @property string|null $logo
 * @property string|null $website
 * @property int|null    $status
 * @property int|null    $order
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property GameOffer[] $offers
 */
class Store extends ActiveRecord
{
    public const int STATUS_ACTIVE   = 1;
    public const int STATUS_INACTIVE = 0;

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
        return '{{%store}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['status', 'order'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'slug', 'logo', 'website'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'name'       => 'Name',
            'slug'       => 'Slug',
            'logo'       => 'Logo',
            'website'    => 'Website',
            'status'     => 'Status',
            'order'      => 'Order',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Offers]].
     *
     * @return ActiveQuery
     */
    public function getOffers(): ActiveQuery
    {
        return $this->hasMany(GameOffer::class, ['store_id' => 'id']);
    }

    public function getLogo(): string
    {
        return $this->logo ?: '/img/store-default.png';
    }
}
