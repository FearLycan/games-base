<?php

use yii\db\Migration;

/**
 * Per-user achievement unlocks + per-user-game achievement aggregates.
 *
 * `user_achievement` stores one row per UNLOCKED achievement (presence = earned;
 * `unlocked_at` is the Steam unlock time). Locked achievements are derived from
 * the game's full schema ({@see common\models\GameAchievement}) minus the user's
 * unlocked set, so we don't store a row per locked achievement.
 *
 * The aggregate columns on `user_game` (filled straight from GetPlayerAchievements,
 * which counts achievements with no schema needed) drive the library completion %
 * and let us page the per-user-game achievement sync by `ach_synced_at`.
 */
class m260602_160000_create_user_achievement_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%user_achievement}}', [
            'id'          => $this->primaryKey(),
            'user_id'     => $this->integer()->notNull(),
            'game_id'     => $this->integer()->notNull(),
            'api_name'    => $this->string()->notNull(),
            'unlocked_at' => $this->timestamp()->null(),
            'created_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex('{{%user_achievement_unique}}', '{{%user_achievement}}', ['user_id', 'game_id', 'api_name'], true);
        $this->createIndex('{{%user_achievement_user_game_index}}', '{{%user_achievement}}', ['user_id', 'game_id']);

        $this->addForeignKey('{{%fk_user_achievement_user}}', '{{%user_achievement}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%fk_user_achievement_game}}', '{{%user_achievement}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');

        // Per-user-game aggregates. NULL ach_synced_at = not yet probed (sync
        // queue picks these first); ach_total 0 = game has no achievements.
        $this->addColumn('{{%user_game}}', 'ach_total', $this->integer()->null());
        $this->addColumn('{{%user_game}}', 'ach_unlocked', $this->integer()->null());
        $this->addColumn('{{%user_game}}', 'ach_synced_at', $this->timestamp()->null());

        $this->createIndex('{{%user_game_ach_synced_index}}', '{{%user_game}}', 'ach_synced_at');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%user_game_ach_synced_index}}', '{{%user_game}}');
        $this->dropColumn('{{%user_game}}', 'ach_synced_at');
        $this->dropColumn('{{%user_game}}', 'ach_unlocked');
        $this->dropColumn('{{%user_game}}', 'ach_total');

        $this->dropForeignKey('{{%fk_user_achievement_game}}', '{{%user_achievement}}');
        $this->dropForeignKey('{{%fk_user_achievement_user}}', '{{%user_achievement}}');
        $this->dropTable('{{%user_achievement}}');
    }
}
