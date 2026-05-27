<?php

use yii\db\Migration;

/**
 * Daily aggregated counter of search-result clicks per game.
 *
 * Pre-aggregated rather than raw click log: one row per (game_id, day),
 * counter bumps via UPSERT. Drives the "Trending today" section in the
 * search modal. Table grows by ~N rows/day where N = distinct games clicked.
 */
class m260527_120200_create_game_search_stat_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%game_search_stat}}', [
            'game_id' => $this->integer()->notNull(),
            'day'     => $this->date()->notNull(),
            'count'   => $this->integer()->notNull()->defaultValue(1),
            'PRIMARY KEY (game_id, day)',
        ]);

        $this->createIndex('{{%game_search_stat_day_count_index}}', '{{%game_search_stat}}', ['day', 'count']);

        $this->addForeignKey(
            '{{%game_search_stat_game_id_fk}}',
            '{{%game_search_stat}}',
            'game_id',
            '{{%game}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('{{%game_search_stat_game_id_fk}}', '{{%game_search_stat}}');
        $this->dropIndex('{{%game_search_stat_day_count_index}}', '{{%game_search_stat}}');
        $this->dropTable('{{%game_search_stat}}');
    }
}
