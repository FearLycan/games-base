<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_sale}}".
 *
 * @property int         $id
 * @property int|null    $game_id
 * @property int|null    $type
 * @property string|null $external_id  source product id (e.g. IG prod_id); null for Steam charts
 * @property int|null    $order
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game        $game
 */
class GameSale extends ActiveRecord
{
    public const int TYPE_BESTSELLERS        = 1;
    public const int TYPE_NEW_AND_NOTEWORTHY = 2;
    public const int TYPE_POPULAR_UPCOMING   = 3;

    // Instant Gaming listing imports (see InstantGamingController::actionList).
    // Numbered apart from the Steam charts so both can coexist in one table.
    public const int TYPE_IG_TRENDING    = 10;
    public const int TYPE_IG_PREORDERS   = 11;
    public const int TYPE_IG_BESTSELLERS = 12;

    public const string STEAM_FILTER_BESTSELLERS        = 'topsellers';
    public const string STEAM_FILTER_NEW_AND_NOTEWORTHY = 'popularnew';
    public const string STEAM_FILTER_POPULAR_UPCOMING   = 'popularcomingsoon';

    /**
     * Human labels for the list types — for admin grids and homepage headings.
     *
     * @return array<int, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_BESTSELLERS        => 'Steam bestsellers',
            self::TYPE_NEW_AND_NOTEWORTHY => 'Steam new & noteworthy',
            self::TYPE_POPULAR_UPCOMING   => 'Steam popular upcoming',
            self::TYPE_IG_TRENDING        => 'Instant Gaming trending',
            self::TYPE_IG_PREORDERS       => 'Instant Gaming pre-orders',
            self::TYPE_IG_BESTSELLERS     => 'Instant Gaming bestsellers',
        ];
    }

    public static function typeLabel(?int $type): string
    {
        return self::typeLabels()[(int)$type] ?? ('#' . (int)$type);
    }

    /** Whether a type is an Instant Gaming listing (published off its IG offer). */
    public static function isInstantGamingType(int $type): bool
    {
        return in_array($type, [self::TYPE_IG_TRENDING, self::TYPE_IG_PREORDERS, self::TYPE_IG_BESTSELLERS], true);
    }

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

    public static function tableName(): string
    {
        return '{{%game_sale}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'type', 'order'], 'integer'],
            [['external_id'], 'string', 'max' => 64],
            [['created_at', 'updated_at'], 'safe'],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'game_id'     => 'Game ID',
            'type'        => 'Type',
            'external_id' => 'External ID',
            'order'       => 'Order',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public static function getSteamFilters(): array
    {
        return [
            self::TYPE_BESTSELLERS        => self::STEAM_FILTER_BESTSELLERS,
            self::TYPE_NEW_AND_NOTEWORTHY => self::STEAM_FILTER_NEW_AND_NOTEWORTHY,
            self::TYPE_POPULAR_UPCOMING   => self::STEAM_FILTER_POPULAR_UPCOMING,
        ];
    }
}
