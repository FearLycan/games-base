<?php

use yii\db\Migration;

/**
 * Adds a precomputed games_count column to publisher.
 *
 * Mirrors the developer version: the /publishers directory lists companies and
 * sorts them by output. Precomputing the count avoids a correlated COUNT
 * subquery per row at request time. Refreshed by
 * console/controllers/PublisherController::actionRecount (cron, daily).
 */
class m260529_120100_add_games_count_to_publisher_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%publisher}}', 'games_count', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('{{%publisher_games_count_index}}', '{{%publisher}}', 'games_count');

        $this->execute('
            UPDATE {{%publisher}} p
            SET p.games_count = (
                SELECT COUNT(DISTINCT gp.game_id)
                FROM {{%game_publisher}} gp
                INNER JOIN {{%game}} ga ON ga.id = gp.game_id
                WHERE gp.publisher_id = p.id
                  AND ga.status = 1
                  AND ga.type   = \'game\'
            )
        ');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%publisher_games_count_index}}', '{{%publisher}}');
        $this->dropColumn('{{%publisher}}', 'games_count');
    }
}
