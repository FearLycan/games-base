<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_achievement}}".
 *
 * One row per achievement. When a Steam web API key is configured the full set
 * comes from GetSchemaForGame (name, description, locked/unlocked icons, hidden
 * flag) enriched with the global unlock rate; otherwise only the appdetails
 * `highlighted` subset is stored (name + icon, the rest null).
 *
 * @property int          $id
 * @property int          $game_id
 * @property string|null  $api_name
 * @property string       $name
 * @property string|null  $description
 * @property string|null  $icon
 * @property string|null  $icon_locked
 * @property bool         $hidden
 * @property float|null   $percent
 * @property string       $created_at
 * @property string|null  $updated_at
 *
 * @property Game         $game
 */
class GameAchievement extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => date("Y-m-d H:i:s"),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%game_achievement}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'name'], 'required'],
            [['game_id'], 'integer'],
            [['description'], 'string'],
            [['hidden'], 'boolean'],
            [['percent'], 'number', 'min' => 0, 'max' => 100],
            [['created_at', 'updated_at'], 'safe'],
            [['api_name', 'name', 'icon', 'icon_locked'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'game_id'     => 'Game ID',
            'api_name'    => 'API Name',
            'name'        => 'Name',
            'description' => 'Description',
            'icon'        => 'Icon',
            'icon_locked' => 'Locked Icon',
            'hidden'      => 'Hidden',
            'percent'     => 'Unlock %',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    /** True when Steam reported a global unlock rate for this achievement. */
    public function hasPercent(): bool
    {
        return $this->percent !== null;
    }

    /** Human unlock rate, e.g. "4.2%", or null when unknown. */
    public function getPercentLabel(): ?string
    {
        if ($this->percent === null) {
            return null;
        }

        // Trim a trailing ".0" so common achievements read "50%" not "50.00%".
        return rtrim(rtrim(number_format((float)$this->percent, 1), '0'), '.') . '%';
    }

    /**
     * Rarity bucket derived from the global unlock rate. Drives the colour of
     * the rarity pill/bar on the achievements page. Falls back to 'common' when
     * the rate is unknown so the UI always has a class to apply.
     *
     * @return 'ultra'|'rare'|'uncommon'|'common'
     */
    public function getRarityTier(): string
    {
        $percent = $this->percent;
        if ($percent === null) {
            return 'common';
        }

        return match (true) {
            $percent < 5  => 'ultra',
            $percent < 20 => 'rare',
            $percent < 50 => 'uncommon',
            default       => 'common',
        };
    }

    /** Human label for the rarity tier, e.g. "Ultra rare". */
    public function getRarityLabel(): string
    {
        return [
            'ultra'    => 'Ultra rare',
            'rare'     => 'Rare',
            'uncommon' => 'Uncommon',
            'common'   => 'Common',
        ][$this->getRarityTier()];
    }
}
