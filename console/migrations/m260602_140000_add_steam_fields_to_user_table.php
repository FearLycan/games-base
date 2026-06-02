<?php

use yii\db\Migration;

/**
 * Adds Steam sign-in support to the user table.
 *
 * A Steam-authenticated account is created from the SteamID64 alone, so it has
 * neither a password nor an email at first — both columns are loosened to allow
 * NULL. The user may later add an email and confirm it (see
 * `email_verified` + the profile/confirm-email flow); existing password
 * accounts that are already active are backfilled as verified.
 */
class m260602_140000_add_steam_fields_to_user_table extends Migration
{
    public function safeUp()
    {
        // Steam-only accounts have no local credentials at creation time.
        $this->alterColumn('{{%user}}', 'password_hash', $this->string()->null());
        $this->alterColumn('{{%user}}', 'email', $this->string()->null());

        $this->addColumn('{{%user}}', 'steam_id', $this->bigInteger()->unsigned()->null()->after('id'));
        $this->addColumn('{{%user}}', 'steam_avatar', $this->string()->null());
        $this->addColumn('{{%user}}', 'steam_profile_url', $this->string()->null());
        $this->addColumn('{{%user}}', 'email_verified', $this->boolean()->notNull()->defaultValue(false));

        $this->createIndex('{{%user_steam_id_unique}}', '{{%user}}', 'steam_id', true);

        // Existing active accounts confirmed their email during signup.
        $this->update('{{%user}}', ['email_verified' => true], ['status' => 10]);
    }

    public function safeDown()
    {
        $this->dropIndex('{{%user_steam_id_unique}}', '{{%user}}');
        $this->dropColumn('{{%user}}', 'email_verified');
        $this->dropColumn('{{%user}}', 'steam_profile_url');
        $this->dropColumn('{{%user}}', 'steam_avatar');
        $this->dropColumn('{{%user}}', 'steam_id');

        // Revert nullability (best effort — fails if NULL rows exist).
        $this->alterColumn('{{%user}}', 'email', $this->string()->notNull());
        $this->alterColumn('{{%user}}', 'password_hash', $this->string()->notNull());
    }
}
