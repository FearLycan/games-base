<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_publisher}}".
 *
 * @property int       $game_id
 * @property int       $publisher_id
 *
 * @property Game      $game
 * @property Publisher $publisher
 */
class GamePublisher extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%game_publisher}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'publisher_id'], 'required'],
            [['game_id', 'publisher_id'], 'integer'],
            [['game_id', 'publisher_id'], 'unique', 'targetAttribute' => ['game_id', 'publisher_id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
            [['publisher_id'], 'exist', 'skipOnError' => true, 'targetClass' => Publisher::class, 'targetAttribute' => ['publisher_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'game_id'      => 'Game ID',
            'publisher_id' => 'Publisher ID',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public function getPublisher(): ActiveQuery
    {
        return $this->hasOne(Publisher::class, ['id' => 'publisher_id']);
    }

    public static function removeConnectionsByGameID(int $game_id): void
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function removeConnectionsByDeveloperID(int $publisher_id): void
    {
        self::deleteAll(['publisher_id' => $publisher_id]);
    }

    public static function createConnection(int $game_id, int $publisher_id): void
    {
        $connection = new self();
        $connection->publisher_id = $publisher_id;
        $connection->game_id = $game_id;
        $connection->save();
    }
}
