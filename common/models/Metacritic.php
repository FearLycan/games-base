<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%metacritic}}".
 *
 * @property int $id
 * @property int|null $score
 * @property int|null $user_score
 * @property string|null $url
 * @property int|null $game_id
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Game $game
 */
class Metacritic extends \yii\db\ActiveRecord
{
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
        return '{{%metacritic}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['score', 'game_id', 'user_score'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['url'], 'string', 'max' => 255],
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
            'score' => 'Score',
            'url' => 'Url',
            'game_id' => 'Game ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Game]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGame()
    {
        return $this->hasOne(Game::className(), ['id' => 'game_id']);
    }

    public function getFormattedUserScore(): string
    {
        return number_format(($this->user_score / 10), 1, '.', '');
    }

    public function getScoreColor(int $value): string
    {
        if ((0 <= $value) && ($value <= 19)) {
            return '#f00';
        }
        if ((20 <= $value) && ($value <= 49)) {
            return '#f00';
        }
        if ((50 <= $value) && ($value <= 74)) {
            return '#fc3';
        }
        if ((75 <= $value) && ($value <= 89)) {
            return '#6c3';
        }
        if ((90 <= $value) && ($value <= 100)) {
            return '#6c3';
        }

        return '#6c3';
    }
}