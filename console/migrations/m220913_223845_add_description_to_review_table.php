<?php

use yii\db\Migration;

/**
 * Class m220913_223845_add_description_to_review_table
 */
class m220913_223845_add_description_to_review_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%review}}', 'description', $this->string()->after('game_id'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%review}}', 'description');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220913_223845_add_description_to_review_table cannot be reverted.\n";

        return false;
    }
    */
}
