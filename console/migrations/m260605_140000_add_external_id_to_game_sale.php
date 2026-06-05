<?php

use yii\db\Migration;

/**
 * Records the source product id on imported list entries (the Instant Gaming
 * prod_id) that seeded the ranking row — handy when tracing which IG listing a
 * {{%game_sale}} entry came from. Null for Steam charts, which carry no external
 * product. Visibility is governed entirely by the matched IG offer's status, not
 * by this column.
 */
class m260605_140000_add_external_id_to_game_sale extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%game_sale}}', 'external_id', $this->string(64)->null()->after('order'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%game_sale}}', 'external_id');
    }
}
