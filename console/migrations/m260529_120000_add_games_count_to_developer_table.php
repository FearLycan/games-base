<?php

use yii\db\Migration;

/**
 * Adds a precomputed games_count column to developer.
 *
 * Same rationale as the genre/tag/category versions: the /developers directory
 * lists studios and sorts them by output. Precomputing the count avoids a
 * correlated COUNT subquery per row at request time. Refreshed by
 * console/controllers/DeveloperController::actionRecount (cron, daily).
 */
class m260529_120000_add_games_count_to_developer_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%developer}}', 'games_count', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('{{%developer_games_count_index}}', '{{%developer}}', 'games_count');

        $this->execute('
            UPDATE {{%developer}} d
            SET d.games_count = (
                SELECT COUNT(DISTINCT gd.game_id)
                FROM {{%game_developer}} gd
                INNER JOIN {{%game}} ga ON ga.id = gd.game_id
                WHERE gd.developer_id = d.id
                  AND ga.status = 1
                  AND ga.type   = \'game\'
            )
        ');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%developer_games_count_index}}', '{{%developer}}');
        $this->dropColumn('{{%developer}}', 'games_count');
    }
}
