<?php

use yii\db\Migration;

/**
 * User's Steam wishlist. Same shape/handling as {@see %user_game} — matched to
 * the catalogue by steam_appid (game_id null until that appid is synced),
 * pulled on the paced cron from the public Web API. `priority` is Steam's
 * wishlist ordering (0 = unranked); `added_at` is when it was wishlisted.
 */
class m260602_170000_create_user_wishlist_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%user_wishlist}}', [
            'id'          => $this->primaryKey(),
            'user_id'     => $this->integer()->notNull(),
            'game_id'     => $this->integer()->null(),
            'steam_appid' => $this->integer()->notNull(),
            'name'        => $this->string()->null(),
            'priority'    => $this->integer()->notNull()->defaultValue(0),
            'added_at'    => $this->timestamp()->null(),
            'created_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'  => $this->timestamp()->null(),
        ], $tableOptions);

        $this->createIndex('{{%user_wishlist_user_appid_unique}}', '{{%user_wishlist}}', ['user_id', 'steam_appid'], true);
        $this->createIndex('{{%user_wishlist_game_id_index}}', '{{%user_wishlist}}', 'game_id');

        $this->addForeignKey('{{%fk_user_wishlist_user}}', '{{%user_wishlist}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%fk_user_wishlist_game}}', '{{%user_wishlist}}', 'game_id', '{{%game}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('{{%fk_user_wishlist_game}}', '{{%user_wishlist}}');
        $this->dropForeignKey('{{%fk_user_wishlist_user}}', '{{%user_wishlist}}');
        $this->dropTable('{{%user_wishlist}}');
    }
}
