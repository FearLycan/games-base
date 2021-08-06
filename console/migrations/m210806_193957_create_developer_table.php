<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%developer}}`.
 */
class m210806_193957_create_developer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%developer}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'slug' => $this->string(),
            'status' => $this->smallInteger()->defaultValue(1),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%developer_name_index}}', '{{%developer}}', 'name');
        $this->createIndex('{{%developer_slug_index}}', '{{%developer}}', 'slug');
        $this->createIndex('{{%developer_status_index}}', '{{%developer}}', 'status');

        $this->createIndex('{{%developer_created_at_index}}', '{{%developer}}', 'created_at');
        $this->createIndex('{{%developer_updated_at_index}}', '{{%developer}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%developer}}');
    }
}
