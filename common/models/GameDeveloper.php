<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%game_developer}}".
 *
 * @property int $game_id
 * @property int $developer_id
 *
 * @property Developer $developer
 * @property Game $game
 */
class GameDeveloper extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game_developer}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['game_id', 'developer_id'], 'required'],
            [['game_id', 'developer_id'], 'integer'],
            [['game_id', 'developer_id'], 'unique', 'targetAttribute' => ['game_id', 'developer_id']],
            [['developer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Developer::className(), 'targetAttribute' => ['developer_id' => 'id']],
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
            'developer_id' => 'Developer ID',
        ];
    }

    /**
     * Gets query for [[Developer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDeveloper()
    {
        return $this->hasOne(Developer::className(), ['id' => 'developer_id']);
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
}