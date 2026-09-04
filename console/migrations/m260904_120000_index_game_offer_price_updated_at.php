<?php

use yii\db\Migration;

/**
 * Makes `{{%game_offer_price}}.updated_at` usable as a change cursor.
 *
 * The internal price feed (`/api/prices`, see frontend\modules\api) streams
 * "what changed since X" off this column, so it needs an index — and it needs
 * every row to have a value. Rows written before {@see \common\models\GameOfferPrice}
 * started stamping updated_at on insert still carry NULL; they are backfilled
 * from created_at so an incremental import never silently skips them.
 */
class m260904_120000_index_game_offer_price_updated_at extends Migration
{
    private const string INDEX_NAME = '{{%game_offer_price_updated_at_index}}';

    public function safeUp()
    {
        $this->execute('UPDATE {{%game_offer_price}} SET updated_at = created_at WHERE updated_at IS NULL');

        $this->createIndex(self::INDEX_NAME, '{{%game_offer_price}}', 'updated_at');
    }

    public function safeDown()
    {
        $this->dropIndex(self::INDEX_NAME, '{{%game_offer_price}}');
    }
}
