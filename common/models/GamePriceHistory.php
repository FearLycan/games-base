<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * One recorded price point for an offer in one currency. Appended by
 * {@see GameOffer::setPrice()} only when the price changes, forming a step
 * time-series for price charts and analysis. Amounts are in minor units (cents),
 * mirroring {@see GameOfferPrice}.
 *
 * @property int         $id
 * @property int         $offer_id
 * @property string      $currency
 * @property int         $price_final
 * @property int|null    $price_initial
 * @property string      $recorded_at
 *
 * @property GameOffer   $offer
 */
class GamePriceHistory extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%game_price_history}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['offer_id', 'currency', 'price_final', 'recorded_at'], 'required'],
            [['offer_id', 'price_final', 'price_initial'], 'integer'],
            [['recorded_at'], 'safe'],
            [['currency'], 'string', 'max' => 3],
            [['offer_id'], 'exist', 'skipOnError' => true, 'targetClass' => GameOffer::class, 'targetAttribute' => ['offer_id' => 'id']],
        ];
    }

    /**
     * Gets query for [[Offer]].
     *
     * @return ActiveQuery
     */
    public function getOffer(): ActiveQuery
    {
        return $this->hasOne(GameOffer::class, ['id' => 'offer_id']);
    }
}
