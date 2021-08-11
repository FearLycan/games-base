<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%game_category}}".
 *
 * @property int $game_id
 * @property int $category_id
 *
 * @property Category $category
 * @property Game $game
 */
class GameCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game_category}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['game_id', 'category_id'], 'required'],
            [['game_id', 'category_id'], 'integer'],
            [['game_id', 'category_id'], 'unique', 'targetAttribute' => ['game_id', 'category_id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::className(), 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'game_id' => 'Game ID',
            'category_id' => 'Category ID',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
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

    public static function removeConnectionsByGameID($game_id)
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function removeConnectionsByCategoryID($category_id)
    {
        self::deleteAll(['category_id' => $category_id]);
    }

    public static function createConnection($game_id, $category_id)
    {
        $connection = new self();
        $connection->category_id = $category_id;
        $connection->game_id = $game_id;
        $connection->save();
    }
}