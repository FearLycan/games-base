<?php

use yii\db\Migration;

/**
 * Low/high water marks per offer-price, maintained by
 * {@see \common\models\GameOffer::setPrice()}. They power the "historical low"
 * signal cheaply: a game is at a historical low when its current cheapest price
 * equals the lowest ever recorded AND it was once more expensive (highest >
 * current) — so nothing is flagged on day one. A full price time-series (for a
 * chart) is intentionally deferred until that feature is built.
 */
class m260604_120000_add_price_range_to_game_offer_price extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%game_offer_price}}', 'lowest_final', $this->integer()->null()->after('price_final'));
        $this->addColumn('{{%game_offer_price}}', 'highest_final', $this->integer()->null()->after('lowest_final'));

        // Seed both from the current price so the columns are usable immediately;
        // they diverge as prices move on future syncs.
        $this->execute('UPDATE {{%game_offer_price}} SET lowest_final = price_final, highest_final = price_final WHERE price_final > 0');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%game_offer_price}}', 'highest_final');
        $this->dropColumn('{{%game_offer_price}}', 'lowest_final');
    }
}
