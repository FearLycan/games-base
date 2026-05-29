<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%store}}`.
 *
 * Lookup of digital stores (Steam, GOG, Epic, ...) that a game can be offered on.
 */
class m260529_130000_create_store_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%store}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'slug' => $this->string(),
            'logo' => $this->string()->null(),
            'website' => $this->string()->null(),
            'status' => $this->smallInteger()->defaultValue(1),
            'order' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%store_slug_index}}', '{{%store}}', 'slug');
        $this->createIndex('{{%store_name_index}}', '{{%store}}', 'name');
        $this->createIndex('{{%store_status_index}}', '{{%store}}', 'status');
        $this->createIndex('{{%store_order_index}}', '{{%store}}', 'order');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%store}}');
    }
}
