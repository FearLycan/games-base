<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_store_scan}}".
 *
 * One row per (game, store): the last time we tried to match that game on that
 * store and how it went. Drives the matcher's cooldown so misses aren't
 * re-queried on every run.
 *
 * @property int    $id
 * @property int    $game_id
 * @property int    $store_id
 * @property int    $result
 * @property string $checked_at
 * @property string $created_at
 *
 * @property Game   $game
 * @property Store  $store
 */
class GameStoreScan extends ActiveRecord
{
    public const int RESULT_NO_MATCH = 0;
    public const int RESULT_MATCHED  = 1;
    public const int RESULT_REVIEW   = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%game_store_scan}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['game_id', 'store_id', 'result'], 'required'],
            [['game_id', 'store_id', 'result'], 'integer'],
            [['checked_at', 'created_at'], 'safe'],
            [['game_id', 'store_id'], 'unique', 'targetAttribute' => ['game_id', 'store_id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'id']],
        ];
    }

    /**
     * Gets query for [[Game]].
     *
     * @return ActiveQuery
     */
    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    /**
     * Gets query for [[Store]].
     *
     * @return ActiveQuery
     */
    public function getStore(): ActiveQuery
    {
        return $this->hasOne(Store::class, ['id' => 'store_id']);
    }

    /**
     * Records (or refreshes) the outcome of a match attempt for a game/store.
     */
    public static function record(int $gameId, int $storeId, int $result): void
    {
        $scan = self::findOne(['game_id' => $gameId, 'store_id' => $storeId])
            ?? new self(['game_id' => $gameId, 'store_id' => $storeId]);

        $scan->result = $result;
        $scan->checked_at = date('Y-m-d H:i:s');
        $scan->save(false);
    }
}
