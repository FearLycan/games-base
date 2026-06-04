<?php

use yii\db\Migration;

/**
 * Adds the two adult-content (18+) visibility preferences to the user table.
 *
 * Both default to FALSE: 18+ games are hidden until the user opts in. Guests
 * can't opt in at all (no account), so they always see the hidden set — the
 * preference only ever loosens visibility for a signed-in account.
 *
 *  - show_adult        → 18+ titles in the public catalogue (browse, search,
 *                        deal board, game pages, company/genre/tag pages).
 *  - show_adult_owned  → 18+ titles in the account's own library, wishlist and
 *                        achievements (content the user already owns on Steam).
 */
class m260604_150000_add_adult_prefs_to_user_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'show_adult', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('{{%user}}', 'show_adult_owned', $this->boolean()->notNull()->defaultValue(false));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'show_adult_owned');
        $this->dropColumn('{{%user}}', 'show_adult');
    }
}
