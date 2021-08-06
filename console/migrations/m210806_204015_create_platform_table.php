<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%platform}}`.
 */
class m210806_204015_create_platform_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%platform}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'slug' => $this->string(),
            'available' => $this->boolean()->defaultValue(false),
            'game_id' => $this->integer()->notNull(),
            'requirements_minimum' => $this->text()->null(),
            'requirements_recommended' => $this->text()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%platform_game_id_fk}}', '{{%platform}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%platform_name_index}}', '{{%platform}}', 'name');
        $this->createIndex('{{%platform_slug_index}}', '{{%platform}}', 'slug');

        $this->createIndex('{{%platform_created_at_index}}', '{{%platform}}', 'created_at');
        $this->createIndex('{{%platform_updated_at_index}}', '{{%platform}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%platform}}');
    }
}
