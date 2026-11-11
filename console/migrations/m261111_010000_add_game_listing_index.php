<?php

use yii\db\Migration;

/**
 * Composite index for catalogue listings.
 *
 * Every public list filters `status = 1 AND type = 'game'` (+ `is_adult = 0`
 * for most viewers) and pages by release date. With single-column indexes only,
 * MySQL picked the low-selectivity `status` index and filesorted ~100k rows to
 * return 24 (/games: ~1 s per request in slow-sql.log). This index lets it read
 * the first page straight from the index; it also serves tag/genre/company
 * lists ordered by release date.
 */
class m261111_010000_add_game_listing_index extends Migration
{
    private const NAME = 'idx-game-listing';

    public function safeUp(): void
    {
        $this->createIndex(self::NAME, '{{%game}}', ['status', 'type', 'is_adult', 'release_date']);
    }

    public function safeDown(): void
    {
        $this->dropIndex(self::NAME, '{{%game}}');
    }
}
