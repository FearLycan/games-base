<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_sale}}".
 *
 * @property int $id
 * @property int|null $game_id
 * @property int|null $type
 * @property int|null $order
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Game $game
 */
class GameSale extends ActiveRecord
{
    const TYPE_BESTSELLERS = 1;
    const TYPE_NEW_AND_NOTEWORTHY = 2;
    const TYPE_POPULAR_UPCOMING = 3;

    const STEAM_FILTER_BESTSELLERS = 'topsellers';
    const STEAM_FILTER_NEW_AND_NOTEWORTHY = 'popularnew';
    const STEAM_FILTER_POPULAR_UPCOMING = 'popularcomingsoon';

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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game_sale}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['game_id', 'type', 'order'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::className(), 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'game_id' => 'Game ID',
            'type' => 'Type',
            'order' => 'Order',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Game]].
     *
     * @return ActiveQuery
     */
    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::className(), ['id' => 'game_id']);
    }

    public static function getSteamFilters(): array
    {
        return [
            self::TYPE_BESTSELLERS => self::STEAM_FILTER_BESTSELLERS,
            self::TYPE_NEW_AND_NOTEWORTHY => self::STEAM_FILTER_NEW_AND_NOTEWORTHY,
            self::TYPE_POPULAR_UPCOMING => self::STEAM_FILTER_POPULAR_UPCOMING
        ];
    }
}
