<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_developer}}`.
 */
class m210806_202628_create_game_developer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_developer}}', [
            'game_id' => $this->integer()->notNull(),
            'developer_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('{{%game_developer_pk}}', '{{%game_developer}}', ['game_id', 'developer_id']);
        $this->addForeignKey('{{%game_developer_game_id_fk}}', '{{%game_developer}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_developer_developer_id_fk}}', '{{%game_developer}}', 'developer_id', '{{%developer}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_developer}}');
    }
}
