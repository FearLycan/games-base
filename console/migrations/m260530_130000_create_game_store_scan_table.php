<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_store_scan}}`.
 *
 * Records every attempt to match one of our games against a store — including
 * misses. Without this, games that never match would be re-queried on every
 * `match` run forever; with it, the matcher skips games checked within a
 * cooldown and only retries stale ones (a miss today may resolve once the store
 * adds the title later).
 */
class m260530_130000_create_game_store_scan_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_store_scan}}', [
            'id' => $this->primaryKey(),
            'game_id' => $this->integer()->notNull(),
            'store_id' => $this->integer()->notNull(),
            'result' => $this->smallInteger()->notNull(), // 0 no-match, 1 matched, 2 review
            'checked_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey('{{%game_store_scan_game_id_fk}}', '{{%game_store_scan}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_store_scan_store_id_fk}}', '{{%game_store_scan}}', 'store_id', '{{%store}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%game_store_scan_game_store_unique}}', '{{%game_store_scan}}', ['game_id', 'store_id'], true);
        $this->createIndex('{{%game_store_scan_checked_at_index}}', '{{%game_store_scan}}', 'checked_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_store_scan}}');
    }
}
