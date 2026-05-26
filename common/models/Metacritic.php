<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%metacritic}}".
 *
 * @property int         $id
 * @property int|null    $score
 * @property int|null    $user_score
 * @property string|null $url
 * @property int|null    $game_id
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game        $game
 */
class Metacritic extends ActiveRecord
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
                'value'      => date("Y-m-d H:i:s"),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%metacritic}}';
    }

    public function rules(): array
    {
        return [
            [['score', 'game_id', 'user_score'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['url'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'score'      => 'Score',
            'url'        => 'Url',
            'game_id'    => 'Game ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public function getFormattedUserScore(): string
    {
        return number_format(($this->user_score / 10), 1, '.', '');
    }

    public function getScoreColor(int $value): string
    {
        return match (true) {
            $value < 50 => '#f00',
            $value < 75 => '#fc3',
            default     => '#6c3',
        };
    }
}
