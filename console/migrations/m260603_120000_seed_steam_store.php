<?php

use common\enums\StoreType;
use common\models\Store;
use yii\db\Migration;

/**
 * Seeds the Steam store row so Steam can be modelled as a first-class offer
 * (its own {{%game_offer}} + {{%game_offer_price}}) alongside the keyshops,
 * instead of a display-only fallback. `order` is high so keyshops sort ahead of
 * Steam in order-based listings; price-based "cheapest offer" picks are unaffected.
 */
class m260603_120000_seed_steam_store extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');

        $this->insert('{{%store}}', [
            'name'       => 'Steam',
            'slug'       => 'steam',
            'logo'       => '/img/stores/steam.png',
            'website'    => 'https://store.steampowered.com/',
            'status'     => Store::STATUS_ACTIVE,
            'type'       => StoreType::Official->value,
            'order'      => 100,
            'created_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%store}}', ['slug' => 'steam']);
    }
}
