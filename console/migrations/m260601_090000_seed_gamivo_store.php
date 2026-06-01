<?php

use common\models\Store;
use yii\db\Migration;

/**
 * Seeds the Gamivo store row (second supported store, after Instant Gaming).
 */
class m260601_090000_seed_gamivo_store extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');

        $this->insert('{{%store}}', [
            'name'       => 'Gamivo',
            'slug'       => 'gamivo',
            'logo'       => '/img/stores/gamivo.png',
            'website'    => 'https://www.gamivo.com/',
            'status'     => Store::STATUS_ACTIVE,
            'order'      => 1,
            'created_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%store}}', ['slug' => 'gamivo']);
    }
}
