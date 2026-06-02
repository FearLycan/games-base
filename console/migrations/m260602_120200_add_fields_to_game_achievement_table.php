<?php

use yii\db\Migration;

/**
 * Enriches {{%game_achievement}} for the full achievement list pulled from
 * Steam's ISteamUserStats/GetSchemaForGame (which, unlike appdetails, returns
 * every achievement — name, description, locked/unlocked icons, hidden flag).
 * Global completion rate (rarity) is merged in from
 * GetGlobalAchievementPercentagesForApp and stored in `percent`.
 *
 * See common\models\Game::setAchievements(). When the Steam web API key is
 * missing the sync falls back to the appdetails `highlighted` subset, which
 * only fills `name` + `icon` — every column added here stays null/default.
 */
class m260602_120200_add_fields_to_game_achievement_table extends Migration
{
    public function safeUp()
    {
        // apiname — stable key Steam uses across schema + percentages; also lets
        // a re-sync match rows. Unique per game.
        $this->addColumn('{{%game_achievement}}', 'api_name', $this->string()->null()->after('game_id'));
        $this->addColumn('{{%game_achievement}}', 'description', $this->text()->null()->after('name'));
        $this->addColumn('{{%game_achievement}}', 'icon_locked', $this->string()->null()->after('icon'));
        $this->addColumn('{{%game_achievement}}', 'hidden', $this->boolean()->notNull()->defaultValue(false)->after('icon_locked'));
        // percent of owners that have unlocked it (0–100); null when unknown.
        $this->addColumn('{{%game_achievement}}', 'percent', $this->decimal(5, 2)->null()->after('hidden'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%game_achievement}}', 'percent');
        $this->dropColumn('{{%game_achievement}}', 'hidden');
        $this->dropColumn('{{%game_achievement}}', 'icon_locked');
        $this->dropColumn('{{%game_achievement}}', 'description');
        $this->dropColumn('{{%game_achievement}}', 'api_name');
    }
}
