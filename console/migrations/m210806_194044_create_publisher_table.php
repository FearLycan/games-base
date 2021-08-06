<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%publisher}}`.
 */
class m210806_194044_create_publisher_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%publisher}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'slug' => $this->string(),
            'status' => $this->smallInteger()->defaultValue(1),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%publisher_name_index}}', '{{%publisher}}', 'name');
        $this->createIndex('{{%publisher_slug_index}}', '{{%publisher}}', 'slug');
        $this->createIndex('{{%publisher_status_index}}', '{{%publisher}}', 'status');

        $this->createIndex('{{%publisher_created_at_index}}', '{{%publisher}}', 'created_at');
        $this->createIndex('{{%publisher_updated_at_index}}', '{{%publisher}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%publisher}}');
    }
}
