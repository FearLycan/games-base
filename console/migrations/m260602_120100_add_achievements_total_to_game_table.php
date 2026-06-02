<?php

use yii\db\Migration;

/**
 * Adds the total achievement count to `{{%game}}`.
 *
 * Steam's appdetails reports `achievements.total` (the full number a game has)
 * alongside a `highlighted` subset it actually serves. We store the rows of the
 * subset in {{%game_achievement}} but keep the authoritative total here so the
 * achievements page can say "showing N of TOTAL" and the sitemap can skip games
 * that have none. See common\models\Game::setAchievements().
 */
class m260602_120100_add_achievements_total_to_game_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'achievements_total', $this->integer()->notNull()->defaultValue(0)->after('steam_price_final'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%game}}', 'achievements_total');
    }
}
