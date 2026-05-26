<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_image}}".
 *
 * @property int         $id
 * @property string      $url
 * @property int         $game_id
 * @property string      $type
 * @property int|null    $status
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game        $game
 */
class GameImage extends ActiveRecord
{
    public const string TYPE_SCREENSHOT = 'screenshot';
    public const string TYPE_ICON       = 'icon';
    public const string TYPE_BACKGROUND = 'background';
    public const string TYPE_HEADER     = 'header';

    public const int STATUS_ACTIVE   = 1;
    public const int STATUS_INACTIVE = 0;

    public static function tableName(): string
    {
        return '{{%game_image}}';
    }

    public function rules(): array
    {
        return [
            [['url', 'game_id', 'type'], 'required'],
            [['game_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['url', 'type'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'url'        => 'Url',
            'game_id'    => 'Game ID',
            'type'       => 'Type',
            'status'     => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }
}