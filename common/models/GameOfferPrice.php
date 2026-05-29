<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_offer_price}}".
 *
 * A price for an offer in one currency. Amounts are stored in minor units
 * (cents), mirroring `game.steam_price_*`.
 *
 * @property int         $id
 * @property int         $offer_id
 * @property string      $currency
 * @property int|null    $price_initial
 * @property int|null    $price_final
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property GameOffer   $offer
 */
class GameOfferPrice extends ActiveRecord
{
    /** Currencies whose symbol is written after the amount (e.g. "81.59 zł"). */
    private const array SUFFIX_CURRENCIES = ['PLN'];

    private const array CURRENCY_SYMBOLS = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'PLN' => 'zł',
    ];

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
        return '{{%game_offer_price}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['offer_id', 'currency'], 'required'],
            [['offer_id', 'price_initial', 'price_final'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['currency'], 'string', 'max' => 3],
            [['offer_id', 'currency'], 'unique', 'targetAttribute' => ['offer_id', 'currency']],
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

    public function getCurrencySymbol(): string
    {
        $code = strtoupper((string)$this->currency);

        if (isset(self::CURRENCY_SYMBOLS[$code])) {
            return self::CURRENCY_SYMBOLS[$code];
        }

        return $code !== '' ? $code . ' ' : '$';
    }

    /**
     * Human label for the current (final) price, or null when there is none.
     */
    public function getFinalPriceLabel(): ?string
    {
        if ((int)$this->price_final <= 0) {
            return null;
        }

        return $this->formatPrice((int)$this->price_final);
    }

    /**
     * Human label for the pre-discount (initial) price, or null when there is none.
     */
    public function getInitialPriceLabel(): ?string
    {
        if ((int)$this->price_initial <= 0) {
            return null;
        }

        return $this->formatPrice((int)$this->price_initial);
    }

    /**
     * Discount percentage off the initial price, or 0 when not on sale.
     */
    public function getDiscountPercent(): int
    {
        $initial = (int)$this->price_initial;
        $final = (int)$this->price_final;

        if ($initial <= 0 || $final <= 0 || $final >= $initial) {
            return 0;
        }

        return (int)round((($initial - $final) / $initial) * 100);
    }

    private function formatPrice(int $cents): string
    {
        $amount = number_format($cents / 100, 2);
        $symbol = $this->getCurrencySymbol();

        if (in_array(strtoupper((string)$this->currency), self::SUFFIX_CURRENCIES, true)) {
            return $amount . ' ' . $symbol;
        }

        return $symbol . $amount;
    }
}
