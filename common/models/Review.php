<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%review}}".
 *
 * @property int         $id
 * @property int|null    $total_positive
 * @property int|null    $total_negative
 * @property int|null    $total_reviews
 * @property int|null    $game_id
 * @property string      $created_at
 * @property string|null $updated_at
 * @property string|null $description
 *
 * @property Game        $game
 */
class Review extends ActiveRecord
{
    private ?int $_percent_positive = null;
    private ?int $_percent_negative = null;

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
        return '{{%review}}';
    }

    public function rules(): array
    {
        return [
            [['total_positive', 'total_negative', 'total_reviews', 'game_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'total_positive' => 'Total Positive',
            'total_negative' => 'Total Negative',
            'total_reviews'  => 'Total Reviews',
            'description'    => 'Description',
            'game_id'        => 'Game ID',
            'created_at'     => 'Created At',
            'updated_at'     => 'Updated At',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public function getPercentsOfPositive(): int
    {
        if (empty($this->total_reviews)) {
            return 0;
        }

        return $this->_percent_positive ??= (int)round(($this->total_positive / $this->total_reviews) * 100);
    }

    public function getPercentsOfNegative(): int
    {
        if (empty($this->total_reviews)) {
            return 0;
        }

        return $this->_percent_negative ??= (int)round(($this->total_negative / $this->total_reviews) * 100);
    }

    public function getShortPositiveDescription(): string
    {
        $positive = number_format((int)$this->total_positive);
        $reviews = number_format((int)$this->total_reviews);

        return "{$positive} of the {$reviews} user reviews for this game are positive.";
    }

    public function getShortNegativeDescription(): string
    {
        $negative = number_format((int)$this->total_negative);
        $reviews = number_format((int)$this->total_reviews);

        return "{$negative} of the {$reviews} user reviews for this game are negative.";
    }
}
