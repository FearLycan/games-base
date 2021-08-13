<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%metacritic}}`.
 */
class m210813_002817_create_metacritic_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%metacritic}}', [
            'id' => $this->primaryKey(),
            'score' => $this->integer()->defaultValue(0),
            'url' => $this->string(),
            'game_id' => $this->integer(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%metacritic_game_id_fk}}', '{{%metacritic}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%metacritic_score_index}}', '{{%metacritic}}', 'score');
        $this->createIndex('{{%metacritic_created_at_index}}', '{{%metacritic}}', 'created_at');
        $this->createIndex('{{%metacritic_updated_at_index}}', '{{%metacritic}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%metacritic}}');
    }
}
