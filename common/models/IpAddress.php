<?php

namespace common\models;

use common\components\IpBlocker;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%ip_address}}".
 *
 * One row per source IP we've observed generating an error. It doubles as a
 * lightweight registry the admin curates: `note` records who the IP is, and
 * `is_blocked` lets the admin deny it on the public site (enforced by
 * {@see IpBlocker}). The aggregate counters/last-error snapshot are maintained
 * by {@see \common\components\IpTracker}; the full breakdown lives in the
 * related {@see IpError} rows.
 *
 * @property int         $id
 * @property string      $ip
 * @property string|null $note
 * @property string|null $country
 * @property int         $is_blocked
 * @property string|null $block_reason
 * @property string|null $blocked_at
 * @property int         $error_count
 * @property int|null    $last_status
 * @property string|null $last_path
 * @property string|null $last_user_agent
 * @property int         $is_bot
 * @property string      $first_seen_at
 * @property string|null $last_seen_at
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property IpError[]   $errorLog
 */
class IpAddress extends ActiveRecord
{
    public const int BLOCKED     = 1;
    public const int NOT_BLOCKED = 0;

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%ip_address}}';
    }

    public function rules(): array
    {
        return [
            [['ip'], 'required'],
            [['ip'], 'string', 'max' => 45],
            [['ip'], 'ip'],
            [['ip'], 'unique'],
            [['note', 'block_reason'], 'string', 'max' => 255],
            [['country'], 'string', 'max' => 2],
            [['last_path'], 'string', 'max' => 1024],
            [['last_user_agent'], 'string', 'max' => 512],
            [['is_blocked', 'is_bot', 'error_count', 'last_status'], 'integer'],
            [['is_blocked', 'is_bot'], 'in', 'range' => [self::NOT_BLOCKED, self::BLOCKED]],
            [['blocked_at', 'first_seen_at', 'last_seen_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'ip'              => 'IP address',
            'note'            => 'Who is this',
            'country'         => 'Country',
            'is_blocked'      => 'Blocked',
            'block_reason'    => 'Block reason',
            'blocked_at'      => 'Blocked at',
            'error_count'     => 'Errors',
            'last_status'     => 'Last status',
            'last_path'       => 'Last path',
            'last_user_agent' => 'Last user agent',
            'is_bot'          => 'Bot',
            'first_seen_at'   => 'First seen',
            'last_seen_at'    => 'Last seen',
            'created_at'      => 'Created at',
            'updated_at'      => 'Updated at',
        ];
    }

    /**
     * Per-event error log behind {@see $error_count}, newest first.
     *
     * Named `errorLog` rather than `errors` on purpose — `getErrors()` is taken
     * by yii\base\Model (validation errors), so a relation of that name would
     * shadow it.
     */
    public function getErrorLog(): ActiveQuery
    {
        return $this->hasMany(IpError::class, ['ip_address_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function isBlocked(): bool
    {
        return (int)$this->is_blocked === self::BLOCKED;
    }

    public function isBot(): bool
    {
        return (int)$this->is_bot === 1;
    }

    /**
     * Counts of each HTTP status this IP has produced, highest first — drives
     * the "what errors" breakdown on the view page so the template stays render-only.
     *
     * @return array<int, int> status code => occurrences
     */
    public function statusBreakdown(): array
    {
        return IpError::find()
            ->select(['status', 'cnt' => 'COUNT(*)'])
            ->where(['ip_address_id' => $this->id])
            ->groupBy('status')
            ->orderBy(['cnt' => SORT_DESC])
            ->indexBy('status')
            ->column();
    }

    /**
     * Keeps {@see IpBlocker}'s cached block-list in sync: any change to the
     * block flag (and every insert/delete) invalidates the cache so the new
     * state takes effect on the next request rather than after the TTL.
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert || array_key_exists('is_blocked', $changedAttributes)) {
            IpBlocker::flush();
        }
    }

    public function afterDelete(): void
    {
        parent::afterDelete();
        IpBlocker::flush();
    }
}
