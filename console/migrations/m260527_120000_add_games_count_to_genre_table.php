<?php

use yii\db\Migration;

/**
 * Adds a precomputed games_count column to genre.
 *
 * Why: every page that orders genres by popularity (homepage chips, /genres
 * directory) was running an aggregate COUNT over game_genre × game. With ~150k
 * games, that's expensive at request time. Precomputing it + refreshing via
 * `genre/recount` cron lets reads be a simple ORDER BY on an indexed column.
 */
class m260527_120000_add_games_count_to_genre_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%genre}}', 'games_count', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('{{%genre_games_count_index}}', '{{%genre}}', 'games_count');

        $this->execute('
            UPDATE {{%genre}} g
            SET g.games_count = (
                SELECT COUNT(DISTINCT gg.game_id)
                FROM {{%game_genre}} gg
                INNER JOIN {{%game}} ga ON ga.id = gg.game_id
                WHERE gg.genre_id = g.id
                  AND ga.status = 1
                  AND ga.type = \'game\'
            )
        ');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%genre_games_count_index}}', '{{%genre}}');
        $this->dropColumn('{{%genre}}', 'games_count');
    }
}
