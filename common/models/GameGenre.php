<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_genre}}".
 *
 * @property int   $game_id
 * @property int   $genre_id
 *
 * @property Game  $game
 * @property Genre $genre
 */
class GameGenre extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%game_genre}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'genre_id'], 'required'],
            [['game_id', 'genre_id'], 'integer'],
            [['game_id', 'genre_id'], 'unique', 'targetAttribute' => ['game_id', 'genre_id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
            [['genre_id'], 'exist', 'skipOnError' => true, 'targetClass' => Genre::class, 'targetAttribute' => ['genre_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'game_id'  => 'Game ID',
            'genre_id' => 'Genre ID',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public function getGenre(): ActiveQuery
    {
        return $this->hasOne(Genre::class, ['id' => 'genre_id']);
    }

    public static function removeConnectionsByGameID(int $game_id): void
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function removeConnectionsByGenreID(int $genre_id): void
    {
        self::deleteAll(['genre_id' => $genre_id]);
    }

    public static function createConnection(int $game_id, int $genre_id): void
    {
        $connection = self::findOne(['game_id' => $game_id, 'genre_id' => $genre_id]);

        if (!$connection) {
            $connection = new self();
            $connection->genre_id = $genre_id;
            $connection->game_id = $game_id;
            $connection->save();
        }
    }
}
