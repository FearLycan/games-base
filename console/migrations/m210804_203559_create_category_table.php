<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%category}}`.
 */
class m210804_203559_create_category_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'slug' => $this->string(),
            'description' => $this->text()->null(),
            'image' => $this->string()->null(),
            'status' => $this->smallInteger()->defaultValue(1),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%category_slug_index}}', '{{%category}}', 'slug');
        $this->createIndex('{{%category_name_index}}', '{{%category}}', 'name');
        $this->createIndex('{{%category_status_index}}', '{{%category}}', 'status');

        $this->createIndex('{{%category_created_at_index}}', '{{%category}}', 'created_at');
        $this->createIndex('{{%category_updated_at_index}}', '{{%category}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%category}}');
    }
}
