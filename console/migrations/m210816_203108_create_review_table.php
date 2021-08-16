<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%review}}`.
 */
class m210816_203108_create_review_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%review}}', [
            'id' => $this->primaryKey(),
            'total_positive' => $this->integer()->defaultValue(0),
            'total_negative' => $this->integer()->defaultValue(0),
            'total_reviews' => $this->integer()->defaultValue(0),
            'game_id' => $this->integer(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);

        $this->addForeignKey('{{%review_game_id_fk}}', '{{%review}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('{{%review_total_positive_index}}', '{{%review}}', 'total_positive');
        $this->createIndex('{{%review_total_negative_index}}', '{{%review}}', 'total_negative');
        $this->createIndex('{{%review_total_reviews_index}}', '{{%review}}', 'total_reviews');

        $this->createIndex('{{%review_created_at_index}}', '{{%review}}', 'created_at');
        $this->createIndex('{{%review_updated_at_index}}', '{{%review}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%review}}');
    }
}
