<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_offer_price}}` and moves per-currency
 * pricing off `{{%game_offer}}`.
 *
 * One offer (game + store) can be priced in several currencies (EUR/USD/PLN,
 * ...), so prices live in a child table keyed by currency rather than as a
 * single column on the offer. The offer also gains `external_id` (the store's
 * own product id, used to refresh prices without re-matching) and `region`
 * (e.g. Worldwide / Europe — region-locked keys matter for buyers).
 */
class m260529_130200_create_game_offer_price_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%game_offer}}', 'external_id', $this->string(64)->null()->after('store_id'));
        $this->addColumn('{{%game_offer}}', 'region', $this->string(64)->null()->after('external_id'));

        $this->createTable('{{%game_offer_price}}', [
            'id' => $this->primaryKey(),
            'offer_id' => $this->integer()->notNull(),
            'currency' => $this->string(3)->notNull(),
            'price_initial' => $this->integer()->null(),
            'price_final' => $this->integer()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%game_offer_price_offer_id_fk}}', '{{%game_offer_price}}', 'offer_id', '{{%game_offer}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('{{%game_offer_price_offer_currency_unique}}', '{{%game_offer_price}}', ['offer_id', 'currency'], true);

        // Pricing now lives in the child table; drop the single-price columns.
        $this->dropColumn('{{%game_offer}}', 'currency');
        $this->dropColumn('{{%game_offer}}', 'price_initial');
        $this->dropColumn('{{%game_offer}}', 'price_final');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%game_offer}}', 'price_final', $this->integer()->null());
        $this->addColumn('{{%game_offer}}', 'price_initial', $this->integer()->null());
        $this->addColumn('{{%game_offer}}', 'currency', $this->string(3)->null());

        $this->dropTable('{{%game_offer_price}}');

        $this->dropColumn('{{%game_offer}}', 'region');
        $this->dropColumn('{{%game_offer}}', 'external_id');
    }
}
