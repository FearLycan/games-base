<?php

use yii\db\Migration;

/**
 * Creates the IP registry used by the admin "IP Addresses" screen.
 *
 * `{{%ip_address}}` is one row per source IP we've seen generate an error,
 * carrying a free-text `note` ("who it is"), an aggregate `error_count`, a
 * snapshot of the last error and a manual `is_blocked` flag. `{{%ip_error}}`
 * is the per-event log behind the count, so the admin can see *which* errors a
 * given IP produced (status, path, user-agent, time) rather than just a total.
 */
class m260613_120000_create_ip_address_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ip_address}}', [
            'id'              => $this->primaryKey(),
            'ip'              => $this->string(45)->notNull(),
            'note'            => $this->string(255)->null(),
            'country'         => $this->char(2)->null(),
            'is_blocked'      => $this->smallInteger()->notNull()->defaultValue(0),
            'block_reason'    => $this->string(255)->null(),
            'blocked_at'      => $this->timestamp()->null(),
            'error_count'     => $this->integer()->notNull()->defaultValue(0),
            'last_status'     => $this->smallInteger()->null(),
            'last_path'       => $this->string(1024)->null(),
            'last_user_agent' => $this->string(512)->null(),
            'is_bot'          => $this->smallInteger()->notNull()->defaultValue(0),
            'first_seen_at'   => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'last_seen_at'    => $this->timestamp()->null(),
            'created_at'      => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'      => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%ip_address_ip_unique}}', '{{%ip_address}}', 'ip', true);
        $this->createIndex('{{%ip_address_is_blocked_index}}', '{{%ip_address}}', 'is_blocked');
        $this->createIndex('{{%ip_address_error_count_index}}', '{{%ip_address}}', 'error_count');

        $this->createTable('{{%ip_error}}', [
            'id'             => $this->primaryKey(),
            'ip_address_id'  => $this->integer()->notNull(),
            'status'         => $this->smallInteger()->notNull(),
            'method'         => $this->string(10)->null(),
            'path'           => $this->string(1024)->notNull(),
            'referrer'       => $this->string(1024)->null(),
            'user_agent'     => $this->string(512)->null(),
            'is_bot'         => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at'     => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            '{{%ip_error_ip_address_id_fk}}',
            '{{%ip_error}}',
            'ip_address_id',
            '{{%ip_address}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->createIndex('{{%ip_error_ip_address_id_index}}', '{{%ip_error}}', 'ip_address_id');
        $this->createIndex('{{%ip_error_status_index}}', '{{%ip_error}}', 'status');
        $this->createIndex('{{%ip_error_created_at_index}}', '{{%ip_error}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%ip_error}}');
        $this->dropTable('{{%ip_address}}');
    }
}
