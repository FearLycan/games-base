<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_category}}`.
 */
class m210806_202138_create_game_category_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_category}}', [
            'game_id' => $this->integer()->notNull(),
            'category_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('{{%game_category_pk}}', '{{%game_category}}', ['game_id', 'category_id']);
        $this->addForeignKey('{{%game_category_game_id_fk}}', '{{%game_category}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_category_category_id_fk}}', '{{%game_category}}', 'category_id', '{{%category}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_category}}');
    }
}
