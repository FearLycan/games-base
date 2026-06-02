<?php

use yii\db\Migration;

/**
 * User's Steam game library (owned games + playtime), plus the per-user sync
 * state on the user table.
 *
 * `game_id` is nullable: an owned appid that isn't in our catalogue yet is
 * stored by `steam_appid` and linked once the game gets synced. The library is
 * pulled from Steam's public Web API on a paced cron (see SteamUserController),
 * never in the request cycle.
 */
class m260602_150000_create_user_game_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%user_game}}', [
            'id'              => $this->primaryKey(),
            'user_id'         => $this->integer()->notNull(),
            'game_id'         => $this->integer()->null(),
            'steam_appid'     => $this->integer()->notNull(),
            'name'            => $this->string()->null(),
            'playtime_minutes' => $this->integer()->notNull()->defaultValue(0),
            'last_played_at'  => $this->timestamp()->null(),
            'created_at'      => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'      => $this->timestamp()->null(),
        ], $tableOptions);

        // One row per owned appid per user.
        $this->createIndex('{{%user_game_user_appid_unique}}', '{{%user_game}}', ['user_id', 'steam_appid'], true);
        $this->createIndex('{{%user_game_game_id_index}}', '{{%user_game}}', 'game_id');

        $this->addForeignKey('{{%fk_user_game_user}}', '{{%user_game}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%fk_user_game_game}}', '{{%user_game}}', 'game_id', '{{%game}}', 'id', 'SET NULL', 'CASCADE');

        // Per-user library sync state. steam_synced_at NULL = queued / never
        // synced (the cron picks these first). steam_visibility mirrors Steam's
        // communityvisibilitystate (3 = public) so the UI can explain an empty
        // library caused by a private profile.
        $this->addColumn('{{%user}}', 'steam_synced_at', $this->timestamp()->null());
        $this->addColumn('{{%user}}', 'steam_visibility', $this->smallInteger()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'steam_visibility');
        $this->dropColumn('{{%user}}', 'steam_synced_at');
        $this->dropForeignKey('{{%fk_user_game_game}}', '{{%user_game}}');
        $this->dropForeignKey('{{%fk_user_game_user}}', '{{%user_game}}');
        $this->dropTable('{{%user_game}}');
    }
}
