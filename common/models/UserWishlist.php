<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * One game on a user's Steam wishlist.
 *
 * Like {@see UserGame}, `game_id` links to the catalogue when we have that appid
 * and stays null otherwise (the row keeps `steam_appid` + the Steam `name`).
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $game_id
 * @property int         $steam_appid
 * @property string|null $name
 * @property int         $priority
 * @property string|null $added_at
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game|null   $game
 * @property User        $user
 */
class UserWishlist extends ActiveRecord
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
                'value'      => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%user_wishlist}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'steam_appid'], 'required'],
            [['user_id', 'game_id', 'steam_appid', 'priority'], 'integer'],
            [['added_at', 'created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getDisplayTitle(): string
    {
        if ($this->game && $this->game->title) {
            return $this->game->title;
        }

        return $this->name ?? ('App ' . $this->steam_appid);
    }

    public function getHeaderImageUrl(): string
    {
        return 'https://cdn.cloudflare.steamstatic.com/steam/apps/' . $this->steam_appid . '/header.jpg';
    }

    public function getSteamStoreUrl(): string
    {
        return 'https://store.steampowered.com/app/' . $this->steam_appid;
    }

    public function isInCatalog(): bool
    {
        return $this->game !== null
            && (int)$this->game->status === Game::STATUS_ACTIVE
            && $this->game->title !== null;
    }
}
