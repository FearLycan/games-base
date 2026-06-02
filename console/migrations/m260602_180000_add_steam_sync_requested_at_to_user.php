<?php

use yii\db\Migration;

/**
 * Tracks when the user last asked for a manual full sync, so we can rate-limit
 * the "Sync now" button to once per 24h. (steam_synced_at can't serve this — the
 * cron overwrites it when it actually runs.)
 */
class m260602_180000_add_steam_sync_requested_at_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'steam_sync_requested_at', $this->timestamp()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'steam_sync_requested_at');
    }
}
