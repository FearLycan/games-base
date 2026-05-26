<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_developer}}".
 *
 * @property int       $game_id
 * @property int       $developer_id
 *
 * @property Developer $developer
 * @property Game      $game
 */
class GameDeveloper extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%game_developer}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'developer_id'], 'required'],
            [['game_id', 'developer_id'], 'integer'],
            [['game_id', 'developer_id'], 'unique', 'targetAttribute' => ['game_id', 'developer_id']],
            [['developer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Developer::class, 'targetAttribute' => ['developer_id' => 'id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'game_id'      => 'Game ID',
            'developer_id' => 'Developer ID',
        ];
    }

    public function getDeveloper(): ActiveQuery
    {
        return $this->hasOne(Developer::class, ['id' => 'developer_id']);
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public static function removeConnectionsByGameID(int $game_id): void
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function removeConnectionsByDeveloperID(int $developer_id): void
    {
        self::deleteAll(['developer_id' => $developer_id]);
    }

    public static function createConnection(int $game_id, int $developer_id): void
    {
        $connection = new self();
        $connection->developer_id = $developer_id;
        $connection->game_id = $game_id;
        $connection->save();
    }
}
