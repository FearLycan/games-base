<?php

use common\models\Store;
use yii\db\Migration;

/**
 * Seeds the GameSeal store row (third supported store, after Instant Gaming and
 * Gamivo).
 */
class m260603_090000_seed_gameseal_store extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');

        $this->insert('{{%store}}', [
            'name'       => 'GameSeal',
            'slug'       => 'gameseal',
            'logo'       => '/img/stores/gameseal.png',
            'website'    => 'https://gameseal.com/',
            'status'     => Store::STATUS_ACTIVE,
            'order'      => 2,
            'created_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%store}}', ['slug' => 'gameseal']);
    }
}
