<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_tag}}".
 *
 * @property int      $game_id
 * @property int      $tag_id
 * @property int|null $order
 *
 * @property Game     $game
 * @property Tag      $tag
 */
class GameTag extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%game_tag}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'tag_id'], 'required'],
            [['game_id', 'tag_id', 'order'], 'integer'],
            [['game_id', 'tag_id'], 'unique', 'targetAttribute' => ['game_id', 'tag_id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
            [['tag_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tag::class, 'targetAttribute' => ['tag_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'game_id' => 'Game ID',
            'tag_id'  => 'Tag ID',
            'order'   => 'Order',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public function getTag(): ActiveQuery
    {
        return $this->hasOne(Tag::class, ['id' => 'tag_id']);
    }

    public static function removeConnectionsByGameID(int $game_id): void
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function createConnection(int $game_id, int $tag_id, int $order): void
    {
        $connection = self::findOne(['tag_id' => $tag_id, 'game_id' => $game_id]);
        if (!$connection) {
            $connection = new self();
            $connection->tag_id = $tag_id;
            $connection->game_id = $game_id;
            $connection->order = $order;
            $connection->save();
        }
    }
}
