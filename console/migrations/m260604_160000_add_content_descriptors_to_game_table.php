<?php

use yii\db\Migration;

/**
 * Adds Steam content-descriptor tracking to the game table, the reliable signal
 * for genuinely adult (pornographic) titles.
 *
 * `required_age` is unusable for this — explicit adult games routinely ship with
 * required_age = 0, while plenty of mainstream M-rated titles carry a non-zero
 * age. Steam's own store instead gates on the per-game content descriptor ids:
 *
 *   1 = Some Nudity or Sexual Content      (mild, common in AAA)
 *   2 = Frequent Violence or Gore
 *   3 = Adult Only Sexual Content          ← hard-gated by Steam
 *   4 = Frequent Nudity or Sexual Content  ← hard-gated by Steam
 *   5 = General Mature Content
 *
 * Only descriptors 3 and 4 put a game behind Steam's mandatory adult opt-in, so
 * those drive our `is_adult` flag. The raw id list is kept in
 * `content_descriptors` for transparency and future tuning.
 *
 * `is_adult` defaults to 0: games are treated as non-adult until a sync writes
 * the descriptors, so nothing is over-hidden — the flag fills in as the catalogue
 * re-syncs (see Game::setBaseInformation()).
 */
class m260604_160000_add_content_descriptors_to_game_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'content_descriptors', $this->string(64)->null()->after('required_age'));
        $this->addColumn('{{%game}}', 'is_adult', $this->boolean()->notNull()->defaultValue(false)->after('content_descriptors'));
        $this->createIndex('idx-game-is_adult', '{{%game}}', 'is_adult');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-game-is_adult', '{{%game}}');
        $this->dropColumn('{{%game}}', 'is_adult');
        $this->dropColumn('{{%game}}', 'content_descriptors');
    }
}
