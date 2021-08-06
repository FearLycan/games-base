<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game}}`.
 */
class m210803_201459_create_game_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(),
            'slug' => $this->string(),
            'steam_appid' => $this->integer(),
            'required_age' => $this->boolean(),
            'is_free' => $this->boolean(),
            'type' => $this->string(),
            'status' => $this->smallInteger()->defaultValue(0),
            'detailed_description' => $this->text(),
            'about_the_game' => $this->text(),
            'short_description' => $this->text(),
            'release_date' => $this->timestamp()->null(),
            'website' => $this->string(),

            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->createIndex('{{%game_title_index}}', '{{%game}}', 'title');
        $this->createIndex('{{%game_slug_index}}', '{{%game}}', 'slug');

        $this->createIndex('{{%game_status_index}}', '{{%game}}', 'status');
        $this->createIndex('{{%game_type_index}}', '{{%game}}', 'type');

        $this->createIndex('{{%game_release_date_index}}', '{{%game}}', 'release_date');
        $this->createIndex('{{%game_created_at_index}}', '{{%game}}', 'created_at');
        $this->createIndex('{{%game_updated_at_index}}', '{{%game}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game}}');
    }
}
