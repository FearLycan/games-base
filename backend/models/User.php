<?php

namespace backend\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Backend-facing User model. See {@see Game} for the rationale. Authentication
 * still runs through common\models\User (the configured identityClass); this
 * subclass only backs the admin module's user management screens.
 *
 * The shared model relies on the default TimestampBehavior, which stores a Unix
 * integer — but this project's `user` table uses TIMESTAMP columns (as do all
 * the other catalogue tables). We override the behavior here so admin-created /
 * edited users write a `Y-m-d H:i:s` value the column accepts, matching the
 * convention used across common\models.
 */
class User extends \common\models\User
{
    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => static fn(): string => date('Y-m-d H:i:s'),
            ],
        ];
    }
}
