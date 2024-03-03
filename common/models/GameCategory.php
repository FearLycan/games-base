<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_category}}".
 *
 * @property int      $game_id
 * @property int      $category_id
 *
 * @property Category $category
 * @property Game     $game
 */
class GameCategory extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%game_category}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['game_id', 'category_id'], 'required'],
            [['game_id', 'category_id'], 'integer'],
            [['game_id', 'category_id'], 'unique', 'targetAttribute' => ['game_id', 'category_id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'game_id'     => 'Game ID',
            'category_id' => 'Category ID',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return ActiveQuery
     */
    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    /**
     * Gets query for [[Game]].
     *
     * @return ActiveQuery
     */
    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    public static function removeConnectionsByGameID($game_id): void
    {
        self::deleteAll(['game_id' => $game_id]);
    }

    public static function removeConnectionsByCategoryID($category_id): void
    {
        self::deleteAll(['category_id' => $category_id]);
    }

    public static function createConnection($game_id, $category_id): void
    {
        $connection = self::findOne(['category_id' => $category_id, 'game_id' => $game_id]);

        if (!$connection) {
            $connection = new self();
            $connection->category_id = $category_id;
            $connection->game_id = $game_id;
            $connection->save();
        }
    }
}