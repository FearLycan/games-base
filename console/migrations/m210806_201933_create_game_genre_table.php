<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%game_genre}}`.
 */
class m210806_201933_create_game_genre_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%game_genre}}', [
            'game_id' => $this->integer(),
            'genre_id' => $this->integer(),
        ]);

        $this->addPrimaryKey('{{%game_genre_pk}}', '{{%game_genre}}', ['game_id', 'genre_id']);
        $this->addForeignKey('{{%game_genre_game_id_fk}}', '{{%game_genre}}', 'game_id', '{{%game}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('{{%game_genre_genre_id_fk}}', '{{%game_genre}}', 'genre_id', '{{%genre}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%game_genre}}');
    }
}
