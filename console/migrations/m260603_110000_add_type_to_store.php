<?php

use common\enums\StoreType;
use yii\db\Migration;

/**
 * Distinguishes official storefronts from CD-key resellers. Existing stores
 * (Instant Gaming, Gamivo, GameSeal) are all keyshops, so the column defaults to
 * TYPE_KEYSHOP; the Steam seed that follows sets TYPE_OFFICIAL explicitly.
 */
class m260603_110000_add_type_to_store extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%store}}',
            'type',
            $this->tinyInteger()->notNull()->defaultValue(StoreType::Keyshop->value)->after('status')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%store}}', 'type');
    }
}
