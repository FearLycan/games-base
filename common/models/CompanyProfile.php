<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%company_profile}}".
 *
 * @property int         $id
 * @property string|null $description
 * @property string|null $history
 * @property string|null $logo_url
 * @property string|null $country
 * @property string|null $city
 * @property int|null    $founded_year
 * @property int|null    $closed_year
 * @property string|null $website
 * @property string|null $twitter
 * @property string|null $discord
 * @property string      $created_at
 * @property string|null $updated_at
 */
class CompanyProfile extends ActiveRecord
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
        return '{{%company_profile}}';
    }

    public function rules(): array
    {
        return [
            [['description', 'history'], 'string'],
            [['founded_year', 'closed_year'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['logo_url', 'website', 'discord'], 'string', 'max' => 500],
            [['country', 'city'], 'string', 'max' => 100],
            [['twitter'], 'string', 'max' => 200],
        ];
    }

    public function getLocation(): ?string
    {
        return match (true) {
            $this->city && $this->country => $this->city . ', ' . $this->country,
            (bool)$this->country          => $this->country,
            (bool)$this->city             => $this->city,
            default                       => null,
        };
    }

    public function isClosed(): bool
    {
        return $this->closed_year !== null;
    }
}
