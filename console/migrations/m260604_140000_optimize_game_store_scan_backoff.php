<?php

use yii\db\Migration;

/**
 * Reworks `{{%game_store_scan}}` from a flat 90-day "checked recently" log into a
 * lean miss-cache with exponential backoff.
 *
 * Rationale (with a 200k+ game catalogue against ~15-30k-title keyshops, most
 * games are permanent misses):
 *   - Matched/review rows were never read by the cooldown — those games are
 *     already excluded via {{%game_offer}} — so they were pure dead weight. Drop
 *     them; only misses need remembering.
 *   - A single flat cooldown re-searches every permanent miss every 90 days
 *     forever. `miss_count` + `next_check_at` let each consecutive miss push the
 *     next attempt further out (30 → 90 → 180 → 365 → 730 days).
 *   - The cooldown subquery is `WHERE store_id = ? AND next_check_at > NOW()`,
 *     so a covering `(store_id, next_check_at)` index replaces the lone
 *     `checked_at` index. The offer anti-join gets a matching `(store_id, game_id)`.
 *   - `created_at` was never read or updated — dropped.
 */
class m260604_140000_optimize_game_store_scan_backoff extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Backoff bookkeeping: consecutive misses, and the earliest a game is
        // eligible to be re-searched on this store again.
        $this->addColumn('{{%game_store_scan}}', 'miss_count', $this->smallInteger()->notNull()->defaultValue(0)->after('result'));
        $this->addColumn('{{%game_store_scan}}', 'next_check_at', $this->timestamp()->null()->after('checked_at'));

        // Matched/review rows are redundant with {{%game_offer}} and never drove
        // the cooldown — remove them so only misses remain.
        $this->execute('DELETE FROM {{%game_store_scan}} WHERE result <> 0');

        // Seed the backoff for surviving misses from their last check, so the
        // first run after deploy doesn't re-scan the whole long tail at once.
        $this->execute('UPDATE {{%game_store_scan}} SET miss_count = 1, next_check_at = DATE_ADD(checked_at, INTERVAL 90 DAY)');

        // Dead column — never read, never updated.
        $this->dropColumn('{{%game_store_scan}}', 'created_at');

        // Serve the cooldown subquery with a covering composite; drop the now
        // useless single-column index.
        $this->dropIndex('{{%game_store_scan_checked_at_index}}', '{{%game_store_scan}}');
        $this->createIndex('{{%game_store_scan_store_next_check_index}}', '{{%game_store_scan}}', ['store_id', 'next_check_at']);

        // The "games we already have an offer for" anti-join filters by store_id
        // and reads game_id — give it a covering composite.
        $this->createIndex('{{%game_offer_store_game_index}}', '{{%game_offer}}', ['store_id', 'game_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('{{%game_offer_store_game_index}}', '{{%game_offer}}');
        $this->dropIndex('{{%game_store_scan_store_next_check_index}}', '{{%game_store_scan}}');
        $this->createIndex('{{%game_store_scan_checked_at_index}}', '{{%game_store_scan}}', 'checked_at');

        $this->addColumn('{{%game_store_scan}}', 'created_at', $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'));
        $this->dropColumn('{{%game_store_scan}}', 'next_check_at');
        $this->dropColumn('{{%game_store_scan}}', 'miss_count');
    }
}
