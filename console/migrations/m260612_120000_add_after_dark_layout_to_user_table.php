<?php

use common\enums\AfterDarkLayout;
use yii\db\Migration;

/**
 * Stores the visual theme a member chose for the "After Dark" (18+) area.
 *
 * String-backed to mirror {@see AfterDarkLayout} (neon|rose|gold). Defaults to
 * the enum's default so every existing account renders the page without a
 * backfill. The column is meaningless until the account opts into 18+ content
 * (show_adult), but it's cheap to carry and keeps the choice sticky.
 */
class m260612_120000_add_after_dark_layout_to_user_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%user}}',
            'after_dark_layout',
            $this->string(16)->notNull()->defaultValue(AfterDarkLayout::default()->value),
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'after_dark_layout');
    }
}
