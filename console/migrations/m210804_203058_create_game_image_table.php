<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_image}}`.
 */
class m210804_203058_create_game_image_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_image}}', [
            'id' => $this->primaryKey(),
            'url' => $this->string()->notNull(),
            'game_id' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'status' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%game_image_game_id_fk}}', '{{%game_image}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%game_image_type_index}}', '{{%game_image}}', 'type');
        $this->createIndex('{{%game_image_status_index}}', '{{%game_image}}', 'status');

        $this->createIndex('{{%game_image_created_at_index}}', '{{%game_image}}', 'created_at');
        $this->createIndex('{{%game_image_updated_at_index}}', '{{%game_image}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_image}}');
    }
}
