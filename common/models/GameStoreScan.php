<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_store_scan}}".
 *
 * One row per (game, store): the last time we tried to match that game on that
 * store and how it went. Only misses are recorded — a successful match lives in
 * {{%game_offer}}, which already excludes the game from the matcher's candidate
 * set, so storing matched/review rows here would be dead weight.
 *
 * Consecutive misses back off geometrically via {@see record()} so the long tail
 * of titles a store will never carry stops being re-searched every cooldown.
 *
 * @property int         $id
 * @property int         $game_id
 * @property int         $store_id
 * @property int         $result
 * @property int         $miss_count     consecutive misses; drives the backoff
 * @property string      $checked_at
 * @property string|null $next_check_at  earliest the game is eligible again
 *
 * @property Game        $game
 * @property Store       $store
 */
class GameStoreScan extends ActiveRecord
{
    public const int RESULT_NO_MATCH = 0;
    public const int RESULT_MATCHED  = 1;
    public const int RESULT_REVIEW   = 2;

    /**
     * Cooldown (days) by consecutive-miss count, escalating then capped. A game
     * missed once is retried in a month; a title missed repeatedly is all but
     * frozen, since it almost certainly will never appear on the store.
     */
    private const array MISS_COOLDOWN_DAYS = [1 => 30, 2 => 90, 3 => 180, 4 => 365];
    private const int MISS_COOLDOWN_MAX_DAYS = 730;

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
            [['game_id', 'store_id', 'result', 'miss_count'], 'integer'],
            [['checked_at', 'next_check_at'], 'safe'],
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
     *
     * A miss bumps {@see $miss_count} and pushes {@see $next_check_at} out per the
     * escalating schedule; any other result clears the backoff (used by manual
     * `--recheck` rescans). The unique (game_id, store_id) key keeps this to one
     * row per pair, so the table stays bounded to the games actually scanned.
     */
    public static function record(int $gameId, int $storeId, int $result): void
    {
        $scan = self::findOne(['game_id' => $gameId, 'store_id' => $storeId])
            ?? new self(['game_id' => $gameId, 'store_id' => $storeId]);

        $now = new \DateTimeImmutable();
        $scan->result = $result;
        $scan->checked_at = $now->format('Y-m-d H:i:s');

        if ($result === self::RESULT_NO_MATCH) {
            $scan->miss_count = (int)$scan->miss_count + 1;
            $days = self::MISS_COOLDOWN_DAYS[$scan->miss_count] ?? self::MISS_COOLDOWN_MAX_DAYS;
        } else {
            $scan->miss_count = 0;
            $days = 0;
        }

        $scan->next_check_at = $now->add(new \DateInterval('P' . $days . 'D'))->format('Y-m-d H:i:s');
        $scan->save(false);
    }
}
