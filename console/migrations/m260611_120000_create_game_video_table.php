<?php

use yii\db\Migration;

/**
 * Creates `{{%game_video}}` — trailers / preview videos for a game.
 *
 * First fed from Kinguin's product API (the one source that hands us a YouTube
 * trailer id per product), but provider-agnostic by design so a Steam-movie or
 * other source can land in the same table later. One game can have several
 * videos; `position` keeps Kinguin's order, `(game_id, provider, video_id)` is
 * unique so re-imports upsert instead of duplicating.
 */
class m260611_120000_create_game_video_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%game_video}}', [
            'id'         => $this->primaryKey(),
            'game_id'    => $this->integer()->notNull(),
            'provider'   => $this->string(32)->notNull()->defaultValue('youtube'),
            'video_id'   => $this->string(64)->notNull(),
            'url'        => $this->string()->notNull(),
            'title'      => $this->string()->null(),
            'position'   => $this->smallInteger()->notNull()->defaultValue(0),
            'status'     => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%game_video_game_id_fk}}', '{{%game_video}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('{{%game_video_unique}}', '{{%game_video}}', ['game_id', 'provider', 'video_id'], true);
        $this->createIndex('{{%game_video_status_index}}', '{{%game_video}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%game_video}}');
    }
}
