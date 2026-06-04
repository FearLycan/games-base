<?php

use yii\db\Migration;

/**
 * Per-offer price time-series — the source of truth for price charts and
 * analysis. {@see \common\models\GameOffer::setPrice()} appends a row only when
 * the price changes (a step series), so the table stays compact. The denormalised
 * `game_offer_price.lowest_final/highest_final` remain the cheap O(1) path for the
 * "historical low" badge; this table is the rich history behind it.
 */
class m260604_130000_create_game_price_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_price_history}}', [
            'id'            => $this->primaryKey(),
            'offer_id'      => $this->integer()->notNull(),
            'currency'      => $this->string(3)->notNull(),
            'price_final'   => $this->integer()->notNull(),
            'price_initial' => $this->integer()->null(),
            'recorded_at'   => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(
            '{{%idx-game_price_history-offer_currency_time}}',
            '{{%game_price_history}}',
            ['offer_id', 'currency', 'recorded_at']
        );

        $this->addForeignKey(
            '{{%fk-game_price_history-offer_id}}',
            '{{%game_price_history}}',
            'offer_id',
            '{{%game_offer}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Anchor the series with the current price of every offer, so charts have
        // a starting point even before the next sync records a change.
        $this->execute(
            'INSERT INTO {{%game_price_history}} (offer_id, currency, price_final, price_initial, recorded_at)
             SELECT offer_id, currency, price_final, price_initial, NOW()
             FROM {{%game_offer_price}}
             WHERE price_final > 0'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_price_history}}');
    }
}
