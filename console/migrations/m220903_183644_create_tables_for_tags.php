<?php

use yii\db\Migration;

/**
 * Class m220903_183644_create_tables_for_tags
 */
class m220903_183644_create_tables_for_tags extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tag}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'slug' => $this->string(),
            'description' => $this->text()->null(),
            'image' => $this->string()->null(),
            'status' => $this->smallInteger()->defaultValue(1),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%tag_name_index}}', '{{%tag}}', 'name');
        $this->createIndex('{{%tag_slug_index}}', '{{%tag}}', 'slug');
        $this->createIndex('{{%tag_status_index}}', '{{%tag}}', 'status');

        $this->createIndex('{{%tag_created_at_index}}', '{{%tag}}', 'created_at');
        $this->createIndex('{{%tag_updated_at_index}}', '{{%tag}}', 'updated_at');

        //------------------------------------------------------------------------------------------------

        $this->createTable('{{%game_tag}}', [
            'game_id' => $this->integer(),
            'tag_id' => $this->integer(),
            'order' => $this->smallInteger()->defaultValue(0),
        ]);

        $this->addPrimaryKey('{{%game_tag_pk}}', '{{%game_tag}}', ['game_id', 'tag_id']);
        $this->addForeignKey('{{%game_tag_game_id_fk}}', '{{%game_tag}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_tag_tag_id_fk}}', '{{%game_tag}}', 'tag_id', '{{%tag}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%game_tag_order_index}}', '{{%game_tag}}', 'order');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_tag}}');
        $this->dropTable('{{%tag}}');
    }
}
