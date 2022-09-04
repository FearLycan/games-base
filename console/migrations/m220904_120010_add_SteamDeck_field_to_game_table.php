<?php

use common\models\Game;
use yii\db\Migration;

/**
 * Class m220904_120010_add_SteamDeck_field_to_game_table
 */
class m220904_120010_add_SteamDeck_field_to_game_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'steam_deck', $this->tinyInteger()->after('status'));
        $this->createIndex('{{%game_steam_deck_index}}', '{{%game}}', 'steam_deck');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%game}}', 'steam_deck');
    }
}
