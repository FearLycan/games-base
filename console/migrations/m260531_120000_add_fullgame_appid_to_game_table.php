<?php

use yii\db\Migration;

/**
 * Adds the self-referential DLC link to `{{%game}}`.
 *
 * Steam's appdetails returns, for a DLC, `data.fullgame.appid` — the appid of
 * the base game it belongs to. We store that raw Steam appid (not a local
 * game.id) because the parent row may not exist yet when the DLC is synced;
 * the appid is stable and lets the relation resolve lazily once both rows are
 * present. See common\models\Game::getFullGame() / getDlc().
 */
class m260531_120000_add_fullgame_appid_to_game_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'fullgame_appid', $this->integer()->null()->after('steam_appid'));
        $this->createIndex('{{%game_fullgame_appid_index}}', '{{%game}}', 'fullgame_appid');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%game_fullgame_appid_index}}', '{{%game}}');
        $this->dropColumn('{{%game}}', 'fullgame_appid');
    }
}
