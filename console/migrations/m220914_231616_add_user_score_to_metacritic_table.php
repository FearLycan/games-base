<?php

use yii\db\Migration;

/**
 * Class m220914_231616_add_user_score_to_metacritic_table
 */
class m220914_231616_add_user_score_to_metacritic_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%metacritic}}', 'user_score', $this->tinyInteger()->defaultValue(0)->after('score'));

        $this->createIndex('{{%metacritic_user_score_index}}', '{{%metacritic}}', 'user_score');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%metacritic}}', 'user_score');
    }
}
