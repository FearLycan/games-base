<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_publisher}}`.
 */
class m210806_202753_create_game_publisher_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_publisher}}', [
            'game_id' => $this->integer()->notNull(),
            'publisher_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('{{%game_publisher_pk}}', '{{%game_publisher}}', ['game_id', 'publisher_id']);
        $this->addForeignKey('{{%game_publisher_game_id_fk}}', '{{%game_publisher}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_publisher_publisher_id_fk}}', '{{%game_publisher}}', 'publisher_id', '{{%publisher}}', 'id', 'CASCADE', 'CASCADE');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_publisher}}');
    }
}
