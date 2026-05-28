<?php

use yii\db\Migration;

/**
 * Adds a precomputed games_count column to category.
 *
 * Same rationale as the genre/tag versions: categories (Steam "features")
 * drive the /categories directory and the per-feature landing pages;
 * precomputing avoids COUNT aggregates at request time. Refreshed by the
 * console command category/recount.
 */
class m260528_120000_add_games_count_to_category_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%category}}', 'games_count', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('{{%category_games_count_index}}', '{{%category}}', 'games_count');

        $this->execute('
            UPDATE {{%category}} c
            SET c.games_count = (
                SELECT COUNT(DISTINCT gc.game_id)
                FROM {{%game_category}} gc
                INNER JOIN {{%game}} ga ON ga.id = gc.game_id
                WHERE gc.category_id = c.id
                  AND ga.status = 1
                  AND ga.type   = \'game\'
            )
        ');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%category_games_count_index}}', '{{%category}}');
        $this->dropColumn('{{%category}}', 'games_count');
    }
}
