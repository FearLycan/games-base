<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%game_publisher}}".
 *
 * @property int $game_id
 * @property int $publisher_id
 *
 * @property Game $game
 * @property Publisher $publisher
 */
class GamePublisher extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game_publisher}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['game_id', 'publisher_id'], 'required'],
            [['game_id', 'publisher_id'], 'integer'],
            [['game_id', 'publisher_id'], 'unique', 'targetAttribute' => ['game_id', 'publisher_id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::className(), 'targetAttribute' => ['game_id' => 'id']],
            [['publisher_id'], 'exist', 'skipOnError' => true, 'targetClass' => Publisher::className(), 'targetAttribute' => ['publisher_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'game_id' => 'Game ID',
            'publisher_id' => 'Publisher ID',
        ];
    }

    /**
     * Gets query for [[Game]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGame()
    {
        return $this->hasOne(Game::className(), ['id' => 'game_id']);
    }

    /**
     * Gets query for [[Publisher]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPublisher()
    {
        return $this->hasOne(Publisher::className(), ['id' => 'publisher_id']);
    }

    public static function removeConnectionsByGameID($game_id)
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function removeConnectionsByDeveloperID($publisher_id)
    {
        self::deleteAll(['publisher_id' => $publisher_id]);
    }

    public static function createConnection($game_id, $publisher_id)
    {
        $connection = new self();
        $connection->publisher_id = $publisher_id;
        $connection->game_id = $game_id;
        $connection->save();
    }
}