<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%game_tag}}".
 *
 * @property int $game_id
 * @property int $tag_id
 * @property int|null $order
 *
 * @property Game $game
 * @property Tag $tag
 */
class GameTag extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game_tag}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['game_id', 'tag_id'], 'required'],
            [['game_id', 'tag_id', 'order'], 'integer'],
            [['game_id', 'tag_id'], 'unique', 'targetAttribute' => ['game_id', 'tag_id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::className(), 'targetAttribute' => ['game_id' => 'id']],
            [['tag_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tag::className(), 'targetAttribute' => ['tag_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'game_id' => 'Game ID',
            'tag_id' => 'Tag ID',
            'order' => 'Order',
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
     * Gets query for [[Tag]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTag()
    {
        return $this->hasOne(Tag::className(), ['id' => 'tag_id']);
    }

    public static function removeConnectionsByGameID($game_id)
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function createConnection(int $game_id, int $genre_id, int $order)
    {
        $connection = new self();
        $connection->tag_id = $genre_id;
        $connection->game_id = $game_id;
        $connection->order = $order;
        $connection->save();
    }
}
