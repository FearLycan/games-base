<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_offer}}".
 *
 * A purchase offer for a game on a specific store. Per-currency prices live in
 * the related {{%game_offer_price}} table.
 *
 * @property int               $id
 * @property int               $game_id
 * @property int               $store_id
 * @property string|null       $external_id   store's own product id (for refresh)
 * @property string|null       $region        e.g. Worldwide / Europe
 * @property string            $url
 * @property string|null       $edition
 * @property int|null          $status
 * @property int|null          $order
 * @property string            $created_at
 * @property string|null       $updated_at
 *
 * @property Game              $game
 * @property Store             $store
 * @property GameOfferPrice[]  $prices
 */
class GameOffer extends ActiveRecord
{
    public const int STATUS_ACTIVE   = 1;
    /** Matched but not yet confirmed — hidden from the site until reviewed. */
    public const int STATUS_REVIEW   = 0;
    public const int STATUS_INACTIVE = 2;

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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%game_offer}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['game_id', 'store_id', 'url'], 'required'],
            [['game_id', 'store_id', 'status', 'order'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['url'], 'string', 'max' => 500],
            [['url'], 'url', 'defaultScheme' => 'https'],
            [['external_id', 'region'], 'string', 'max' => 64],
            [['edition'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'game_id'     => 'Game ID',
            'store_id'    => 'Store ID',
            'external_id' => 'External ID',
            'region'      => 'Region',
            'url'         => 'URL',
            'edition'     => 'Edition',
            'status'      => 'Status',
            'order'       => 'Order',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Game]].
     *
     * @return ActiveQuery
     */
    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    /**
     * Gets query for [[Store]].
     *
     * @return ActiveQuery
     */
    public function getStore(): ActiveQuery
    {
        return $this->hasOne(Store::class, ['id' => 'store_id']);
    }

    /**
     * Gets query for [[Prices]].
     *
     * @return ActiveQuery
     */
    public function getPrices(): ActiveQuery
    {
        return $this->hasMany(GameOfferPrice::class, ['offer_id' => 'id']);
    }

    /**
     * The price for a given currency, or null when this offer has none.
     * Reads from the already-loaded `prices` relation so it stays cheap when
     * offers are eager-loaded for display.
     */
    public function getPrice(string $currency): ?GameOfferPrice
    {
        $currency = strtoupper($currency);

        foreach ($this->prices as $price) {
            if (strtoupper((string)$price->currency) === $currency) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Creates or updates this offer's price in the given currency. Amounts are
     * in minor units (cents). The offer must already be saved (have an id).
     */
    public function setPrice(string $currency, ?int $priceFinal, ?int $priceInitial): GameOfferPrice
    {
        $currency = strtoupper($currency);

        $price = GameOfferPrice::findOne(['offer_id' => $this->id, 'currency' => $currency])
            ?? new GameOfferPrice(['offer_id' => $this->id, 'currency' => $currency]);

        $price->price_final = $priceFinal;
        $price->price_initial = $priceInitial;
        $price->save();

        return $price;
    }
}
