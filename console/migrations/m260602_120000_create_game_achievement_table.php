<?php

use yii\db\Migration;

/**
 * Stores the achievements Steam's appdetails returns for a game.
 *
 * appdetails only exposes a *highlighted* subset (name + icon) of a title's
 * achievements plus a `total` count (see the add-column migration). We persist
 * the highlighted rows here so the dedicated achievements page can render them
 * without re-hitting Steam on every request; the full count lives on the game
 * row. Re-synced on each game sync — see common\models\Game::setAchievements().
 */
class m260602_120000_create_game_achievement_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%game_achievement}}', [
            'id'         => $this->primaryKey(),
            'game_id'    => $this->integer()->notNull(),
            'name'       => $this->string()->notNull(),
            'icon'       => $this->string()->null(),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('{{%game_achievement_game_id_index}}', '{{%game_achievement}}', 'game_id');
        $this->addForeignKey(
            '{{%fk_game_achievement_game}}',
            '{{%game_achievement}}',
            'game_id',
            '{{%game}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('{{%fk_game_achievement_game}}', '{{%game_achievement}}');
        $this->dropTable('{{%game_achievement}}');
    }
}
