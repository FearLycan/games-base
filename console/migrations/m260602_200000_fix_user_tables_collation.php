<?php

use yii\db\Migration;

/**
 * The user_game / user_wishlist / user_achievement tables were created with
 * utf8mb4_unicode_ci, while the rest of the schema (game, game_achievement, …)
 * uses MySQL 8's default utf8mb4_0900_ai_ci. Joining string columns across that
 * boundary — e.g. user_achievement.api_name = game_achievement.api_name — throws
 * "Illegal mix of collations". Align our tables to the catalogue's collation.
 */
class m260602_200000_fix_user_tables_collation extends Migration
{
    private const array TABLES = ['user_game', 'user_wishlist', 'user_achievement'];

    public function safeUp()
    {
        if ($this->db->driverName !== 'mysql') {
            return;
        }

        foreach (self::TABLES as $table) {
            $this->execute("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        }
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'mysql') {
            return;
        }

        foreach (self::TABLES as $table) {
            $this->execute("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }
}
