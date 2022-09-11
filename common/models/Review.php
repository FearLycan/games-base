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
    private $_percent_positive;
    private $_percent_negative;

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

    public function getPercentsOfPositive(): int
    {
        if ($this->total_reviews == 0) {
            return 0;
        }

        if (!$this->_percent_positive) {
            $this->_percent_positive = round(($this->total_positive / $this->total_reviews), 2) * 100;
        }

        return $this->_percent_positive;
    }

    public function getPercentsOfNegative(): int
    {
        if ($this->total_reviews == 0) {
            return 0;
        }

        if (!$this->_percent_negative) {
            $this->_percent_negative = round(($this->total_negative / $this->total_reviews), 2) * 100;
        }

        return $this->_percent_negative;
    }

    public function getShortPositiveDescription(): string
    {
        $positive = number_format($this->total_positive);
        $reviews = number_format($this->total_reviews);

        return "{$positive} of the {$reviews} user reviews for this game are positive.";
    }

    public function getShortNegativeDescription(): string
    {
        $negative = number_format($this->total_negative);
        $reviews = number_format($this->total_reviews);

        return "{$negative} of the {$reviews} user reviews for this game are negative.";
    }
}