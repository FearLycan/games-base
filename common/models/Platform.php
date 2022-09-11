<?php

namespace common\models;

use Yii;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%platform}}".
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $available
 * @property int $game_id
 * @property string|null $slug
 * @property string|null $requirements_minimum
 * @property string|null $requirements_recommended
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Game $game
 */
class Platform extends ActiveRecord
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => date("Y-m-d H:i:s"),
            ],
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => ['name'],
                'slugAttribute' => 'slug',
                'ensureUnique' => false,
                'immutable' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%platform}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['game_id'], 'integer'],
            [['available'], 'boolean'],
            [['game_id'], 'required'],
            [['requirements_minimum', 'requirements_recommended'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
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
            'name' => 'Name',
            'available' => 'Available',
            'game_id' => 'Game ID',
            'requirements_minimum' => 'Requirements Minimum',
            'requirements_recommended' => 'Requirements Recommended',
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

    public function getIcon(): string
    {
        switch ($this->slug) {
            case 'windows':
                $icon = '<i class="fa fa-windows" aria-hidden="true"></i>';
                break;
            case 'linux':
                $icon = '<i class="fa fa-linux" aria-hidden="true"></i>';
                break;
            case 'mac':
                $icon = '<i class="fa fa-apple" aria-hidden="true"></i>';
                break;
            default:
                $icon = '';
        }

        return $icon;
    }
}