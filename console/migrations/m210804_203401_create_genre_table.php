<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%genre}}`.
 */
class m210804_203401_create_genre_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%genre}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'slug' => $this->string(),
            'description' => $this->text()->null(),
            'image' => $this->string()->null(),
            'status' => $this->smallInteger()->defaultValue(1),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%genre_name_index}}', '{{%genre}}', 'name');
        $this->createIndex('{{%genre_slug_index}}', '{{%genre}}', 'slug');
        $this->createIndex('{{%genre_status_index}}', '{{%genre}}', 'status');

        $this->createIndex('{{%genre_created_at_index}}', '{{%genre}}', 'created_at');
        $this->createIndex('{{%genre_updated_at_index}}', '{{%genre}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%genre}}');
    }
}
