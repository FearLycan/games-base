<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_offer}}`.
 *
 * A purchase offer for a game on a specific store. Prices are stored in minor
 * units (cents) like `game.steam_price_*`, with an explicit ISO currency code
 * since offers can come from stores in different currencies.
 */
class m260529_130100_create_game_offer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_offer}}', [
            'id' => $this->primaryKey(),
            'game_id' => $this->integer()->notNull(),
            'store_id' => $this->integer()->notNull(),
            'url' => $this->string(500)->notNull(),
            'price_initial' => $this->integer()->null(),
            'price_final' => $this->integer()->null(),
            'currency' => $this->string(3)->null(),
            'edition' => $this->string()->null(),
            'status' => $this->smallInteger()->defaultValue(1),
            'order' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%game_offer_game_id_fk}}', '{{%game_offer}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_offer_store_id_fk}}', '{{%game_offer}}', 'store_id', '{{%store}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%game_offer_status_index}}', '{{%game_offer}}', 'status');
        $this->createIndex('{{%game_offer_order_index}}', '{{%game_offer}}', 'order');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_offer}}');
    }
}
