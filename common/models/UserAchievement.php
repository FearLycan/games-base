<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * One unlocked achievement for a user in a game. The row's existence means the
 * achievement is earned; `unlocked_at` is the Steam unlock time (null when Steam
 * didn't report one). Locked achievements are not stored — they're the game's
 * full schema ({@see GameAchievement}) minus these rows.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $game_id
 * @property string      $api_name
 * @property string|null $unlocked_at
 * @property string      $created_at
 *
 * @property Game            $game
 * @property User            $user
 * @property GameAchievement $achievement
 */
class UserAchievement extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [ActiveRecord::EVENT_BEFORE_INSERT => ['created_at']],
                'value'      => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%user_achievement}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'game_id', 'api_name'], 'required'],
            [['user_id', 'game_id'], 'integer'],
            [['unlocked_at', 'created_at'], 'safe'],
            [['api_name'], 'string', 'max' => 255],
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

    /** The matching schema row (display name, icon, rarity) for this unlock. */
    public function getAchievement(): ActiveQuery
    {
        return $this->hasOne(GameAchievement::class, ['game_id' => 'game_id', 'api_name' => 'api_name']);
    }
}
