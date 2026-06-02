<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * One owned Steam game in a user's library.
 *
 * `game_id` links to the catalogue {@see Game} when we have that appid; it stays
 * null for owned games not yet synced into the catalogue (the row still carries
 * `steam_appid` and the Steam-reported `name`, and gets linked later).
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $game_id
 * @property int         $steam_appid
 * @property string|null $name
 * @property int         $playtime_minutes
 * @property string|null $last_played_at
 * @property int|null    $ach_total
 * @property int|null    $ach_unlocked
 * @property string|null $ach_synced_at
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game|null   $game
 * @property User        $user
 */
class UserGame extends ActiveRecord
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
        return '{{%user_game}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'steam_appid'], 'required'],
            [['user_id', 'game_id', 'steam_appid', 'playtime_minutes'], 'integer'],
            [['last_played_at', 'created_at', 'updated_at'], 'safe'],
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

    /** Title to show: the catalogue title when linked, else the Steam name. */
    public function getDisplayTitle(): string
    {
        if ($this->game && $this->game->title) {
            return $this->game->title;
        }

        return $this->name ?? ('App ' . $this->steam_appid);
    }

    /** Steam header capsule for this appid — works whether or not it's catalogued. */
    public function getHeaderImageUrl(): string
    {
        return 'https://cdn.cloudflare.steamstatic.com/steam/apps/' . $this->steam_appid . '/header.jpg';
    }

    /** Public Steam store page for this appid. */
    public function getSteamStoreUrl(): string
    {
        return 'https://store.steampowered.com/app/' . $this->steam_appid;
    }

    /** True when the linked catalogue game is live and has a detail page worth linking to. */
    public function isInCatalog(): bool
    {
        return $this->game !== null
            && (int)$this->game->status === Game::STATUS_ACTIVE
            && $this->game->title !== null;
    }

    /** True once this game's achievements have been synced for the user. */
    public function isAchievementsSynced(): bool
    {
        return $this->ach_synced_at !== null;
    }

    /** True when the game has achievements (synced and total > 0). */
    public function hasAchievements(): bool
    {
        return (int)$this->ach_total > 0;
    }

    /**
     * Completion as a 0–100 int, or null when there's nothing to show
     * (not synced yet, or the game has no achievements).
     */
    public function getCompletionPercent(): ?int
    {
        if (!$this->hasAchievements()) {
            return null;
        }

        return (int)round((int)$this->ach_unlocked / (int)$this->ach_total * 100);
    }

    /** True when every achievement is unlocked (a "perfect"/100% game). */
    public function isPerfect(): bool
    {
        return $this->hasAchievements() && (int)$this->ach_unlocked >= (int)$this->ach_total;
    }

    /** Playtime as whole hours (rounded), e.g. 42, for display. */
    public function getPlaytimeHours(): int
    {
        return (int)round($this->playtime_minutes / 60);
    }

    /** Human playtime label, e.g. "42 h" / "37 min" / "never played". */
    public function getPlaytimeLabel(): string
    {
        if ($this->playtime_minutes <= 0) {
            return 'never played';
        }
        if ($this->playtime_minutes < 60) {
            return $this->playtime_minutes . ' min';
        }

        return $this->getPlaytimeHours() . ' h';
    }
}
