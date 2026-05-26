<?php

use yii\db\Migration;

/**
 * Creates a shared editorial profile table for developers and publishers,
 * and adds a nullable profile_id foreign key to both.
 *
 * Rationale: developer/publisher share the same editorial surface (logo,
 * description, history, country, social links). One table avoids schema drift
 * and lets a single profile be reused if a company acts as both dev and pub.
 */
class m260526_120000_create_company_profile_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%company_profile}}', [
            'id'           => $this->primaryKey(),
            'description'  => $this->text()->null(),
            'history'      => $this->text()->null(),
            'logo_url'     => $this->string(500)->null(),
            'country'      => $this->string(100)->null(),
            'city'         => $this->string(100)->null(),
            'founded_year' => $this->smallInteger()->null(),
            'closed_year'  => $this->smallInteger()->null(),
            'website'      => $this->string(500)->null(),
            'twitter'      => $this->string(200)->null(),
            'discord'      => $this->string(500)->null(),
            'created_at'   => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'   => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%company_profile_country_index}}', '{{%company_profile}}', 'country');
        $this->createIndex('{{%company_profile_founded_year_index}}', '{{%company_profile}}', 'founded_year');

        $this->addColumn('{{%developer}}', 'profile_id', $this->integer()->null());
        $this->addColumn('{{%publisher}}', 'profile_id', $this->integer()->null());

        $this->createIndex('{{%developer_profile_id_index}}', '{{%developer}}', 'profile_id');
        $this->createIndex('{{%publisher_profile_id_index}}', '{{%publisher}}', 'profile_id');

        $this->addForeignKey(
            '{{%developer_profile_id_fk}}',
            '{{%developer}}',
            'profile_id',
            '{{%company_profile}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%publisher_profile_id_fk}}',
            '{{%publisher}}',
            'profile_id',
            '{{%company_profile}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('{{%developer_profile_id_fk}}', '{{%developer}}');
        $this->dropForeignKey('{{%publisher_profile_id_fk}}', '{{%publisher}}');

        $this->dropIndex('{{%developer_profile_id_index}}', '{{%developer}}');
        $this->dropIndex('{{%publisher_profile_id_index}}', '{{%publisher}}');

        $this->dropColumn('{{%developer}}', 'profile_id');
        $this->dropColumn('{{%publisher}}', 'profile_id');

        $this->dropTable('{{%company_profile}}');
    }
}
