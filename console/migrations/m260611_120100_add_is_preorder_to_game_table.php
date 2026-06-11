<?php

use yii\db\Migration;

/**
 * Adds `is_preorder` to `{{%game}}` — set from Kinguin's `isPreorder` flag.
 *
 * A game-level property (not a per-video one), so it lives on the game rather
 * than in {{%game_video}}. Indexed so an "upcoming / pre-order" filter stays
 * cheap if we lean on it later; for now it drives a small badge on cards.
 */
class m260611_120100_add_is_preorder_to_game_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'is_preorder', $this->boolean()->notNull()->defaultValue(false));
        $this->createIndex('{{%game_is_preorder_index}}', '{{%game}}', 'is_preorder');
    }

    public function safeDown()
    {
        $this->dropIndex('{{%game_is_preorder_index}}', '{{%game}}');
        $this->dropColumn('{{%game}}', 'is_preorder');
    }
}
