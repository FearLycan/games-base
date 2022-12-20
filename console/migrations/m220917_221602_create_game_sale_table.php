<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_sale}}`.
 */
class m220917_221602_create_game_sale_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_sale}}', [
            'id' => $this->primaryKey(),
            'game_id' => $this->integer(),
            'type' => $this->tinyInteger(),
            'order' => $this->smallInteger(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%game_sale_game_id_fk}}', '{{%game_sale}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%game_sale_type_index}}', '{{%game_sale}}', 'type');
        $this->createIndex('{{%game_sale_order_index}}', '{{%game_sale}}', 'order');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_sale}}');
    }
}
