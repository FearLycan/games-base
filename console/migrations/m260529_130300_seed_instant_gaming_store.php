<?php

use common\models\Store;
use yii\db\Migration;

/**
 * Seeds the Instant Gaming store row (first supported store).
 */
class m260529_130300_seed_instant_gaming_store extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');

        $this->insert('{{%store}}', [
            'name'       => 'Instant Gaming',
            'slug'       => 'instant-gaming',
            'logo'       => '/img/stores/instant-gaming.png',
            'website'    => 'https://www.instant-gaming.com/',
            'status'     => Store::STATUS_ACTIVE,
            'order'      => 0,
            'created_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%store}}', ['slug' => 'instant-gaming']);
    }
}
