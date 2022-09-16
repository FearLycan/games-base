<?php

use yii\db\Migration;

/**
 * Class m220916_205357_add_price_fields_to_game_table
 */
class m220916_205357_add_price_fields_to_game_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'steam_price_initial', $this->smallInteger()->defaultValue(0)->after('steam_appid'));
        $this->addColumn('{{%game}}', 'steam_price_final', $this->smallInteger()->defaultValue(0)->after('steam_price_initial'));

        $this->createIndex('{{%game_steam_price_initial_index}}', '{{%game}}', 'steam_price_initial');
        $this->createIndex('{{%game_steam_price_final_index}}', '{{%game}}', 'steam_price_final');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropColumn('{{%game}}', 'steam_price_initial');
       $this->dropColumn('{{%game}}', 'steam_price_final');
    }
}
