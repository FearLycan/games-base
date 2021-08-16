<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%review}}".
 *
 * @property int $id
 * @property int|null $total_positive
 * @property int|null $total_negative
 * @property int|null $total_reviews
 * @property int|null $game_id
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Game $game
 */
class Review extends \yii\db\ActiveRecord
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
        return '{{%review}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['total_positive', 'total_negative', 'total_reviews', 'game_id'], 'integer'],
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
            'total_positive' => 'Total Positive',
            'total_negative' => 'Total Negative',
            'total_reviews' => 'Total Reviews',
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

    public function getPercents()
    {
        return round(($this->total_positive / $this->total_reviews), 2) * 100;
    }
}