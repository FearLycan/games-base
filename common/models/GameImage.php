<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%game_image}}".
 *
 * @property int $id
 * @property string $url
 * @property int $game_id
 * @property string $type
 * @property int|null $status
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Game $game
 */
class GameImage extends \yii\db\ActiveRecord
{
    const TYPE_SCREENSHOT = 'screenshot';
    const TYPE_ICON = 'icon';
    const TYPE_BACKGROUND = 'background';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game_image}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url', 'game_id', 'type'], 'required'],
            [['game_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['url', 'type'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::className(), 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'game_id' => 'Game ID',
            'type' => 'Type',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
}