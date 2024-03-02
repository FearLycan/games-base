<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%game}}`.
 */
class m240302_172619_add_synchronized_at_column_to_game_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'synchronized_at', $this->timestamp()->null());
        $this->addColumn('{{%game}}', 'force_sync', $this->boolean()->defaultValue(0)->after('website'));

        $this->createIndex('index_game_synchronized_at', '{{%game}}', 'synchronized_at');
        $this->createIndex('index_game_force_sync', '{{%game}}', 'force_sync');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('index_game_synchronized_at', '{{%game}}');
        $this->dropIndex('index_game_force_sync', '{{%game}}');

        $this->dropColumn('{{%game}}', 'synchronized_at');
        $this->dropColumn('{{%game}}', 'force_sync');
    }
}
