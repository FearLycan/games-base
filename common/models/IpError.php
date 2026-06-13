<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%ip_error}}".
 *
 * One row per error response attributed to an IP — the per-event detail behind
 * {@see IpAddress::$error_count}. Written by {@see \common\components\IpTracker}
 * from the app's error action; read on the admin IP view page to show which
 * errors a given source produced.
 *
 * @property int         $id
 * @property int         $ip_address_id
 * @property int         $status
 * @property string|null $method
 * @property string      $path
 * @property string|null $referrer
 * @property string|null $user_agent
 * @property int         $is_bot
 * @property string      $created_at
 *
 * @property IpAddress   $ipAddress
 */
class IpError extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value'      => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%ip_error}}';
    }

    public function rules(): array
    {
        return [
            [['ip_address_id', 'status', 'path'], 'required'],
            [['ip_address_id', 'status', 'is_bot'], 'integer'],
            [['method'], 'string', 'max' => 10],
            [['path', 'referrer'], 'string', 'max' => 1024],
            [['user_agent'], 'string', 'max' => 512],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'            => 'ID',
            'ip_address_id' => 'IP address',
            'status'        => 'Status',
            'method'        => 'Method',
            'path'          => 'Path',
            'referrer'      => 'Referrer',
            'user_agent'    => 'User agent',
            'is_bot'        => 'Bot',
            'created_at'    => 'When',
        ];
    }

    public function getIpAddress(): ActiveQuery
    {
        return $this->hasOne(IpAddress::class, ['id' => 'ip_address_id']);
    }
}
