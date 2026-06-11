<?php

use common\models\Store;
use yii\db\Migration;

/**
 * Seeds the Kinguin store row (fourth supported store, after Instant Gaming,
 * Gamivo and GameSeal). `type` defaults to TYPE_KEYSHOP (see the add-type
 * migration), which is what Kinguin is, so it isn't set explicitly.
 */
class m260611_100000_seed_kinguin_store extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('{{%store}}', [
            'name'       => 'Kinguin',
            'slug'       => 'kinguin',
            'logo'       => '/img/stores/kinguin.png',
            'website'    => 'https://www.kinguin.net/',
            'status'     => Store::STATUS_ACTIVE,
            'order'      => 3,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%store}}', ['slug' => 'kinguin']);
    }
}
