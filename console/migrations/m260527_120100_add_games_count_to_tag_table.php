<?php

use yii\db\Migration;

/**
 * Adds a precomputed games_count column to tag.
 *
 * Same rationale as the genre version: tags drive the /tags directory and
 * popularity sorting on the game view page; precomputing avoids COUNT
 * aggregates at request time.
 */
class m260527_120100_add_games_count_to_tag_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%tag}}', 'games_count', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('{{%tag_games_count_index}}', '{{%tag}}', 'games_count');

        $this->execute('
            UPDATE {{%tag}} t
            SET t.games_count = (
                SELECT COUNT(DISTINCT gt.game_id)
                FROM {{%game_tag}} gt
                INNER JOIN {{%game}} ga ON ga.id = gt.game_id
                WHERE gt.tag_id = t.id
                  AND ga.status = 1
                  AND ga.type   = \'game\'
            )
        ');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%tag_games_count_index}}', '{{%tag}}');
        $this->dropColumn('{{%tag}}', 'games_count');
    }
}
