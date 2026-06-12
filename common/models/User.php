<?php

namespace common\models;

use common\components\steam\SteamApi;
use common\enums\AfterDarkLayout;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int    $id
 * @property string      $username
 * @property string|null $password_hash
 * @property string      $password_reset_token
 * @property string      $verification_token
 * @property string|null $email
 * @property bool         $email_verified
 * @property string|null $steam_id
 * @property string|null $steam_avatar
 * @property string|null $steam_profile_url
 * @property string|null $steam_synced_at
 * @property int|null    $steam_visibility
 * @property string|null $steam_sync_requested_at
 * @property string      $auth_key
 * @property int    $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $password write-only password
 * @property int    $role
 * @property bool        $show_adult       show 18+ games in the public catalogue
 * @property bool        $show_adult_owned show 18+ games in the user's own library/wishlist/achievements
 * @property string      $after_dark_layout chosen visual theme for the After Dark (18+) area
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const int STATUS_DELETED  = 0;
    public const int STATUS_INACTIVE = 9;
    public const int STATUS_ACTIVE   = 10;

    public const int ROLE_ADMIN = 10;
    public const int ROLE_USER  = 1;

    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public function behaviors(): array
    {
        return [
            // created_at/updated_at are MySQL TIMESTAMP (datetime) columns, so we
            // store formatted datetimes — not the behavior's default UNIX int,
            // which MySQL strict mode rejects. Matches Game/GameAchievement.
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            [['show_adult', 'show_adult_owned'], 'boolean'],
            [['show_adult', 'show_adult_owned'], 'default', 'value' => false],
            ['after_dark_layout', 'default', 'value' => AfterDarkLayout::default()->value],
            ['after_dark_layout', 'in', 'range' => AfterDarkLayout::values()],
        ];
    }

    /**
     * The member's chosen After Dark (18+) theme as an enum, defaulting safely
     * when the stored value is empty or unknown.
     */
    public function getAfterDarkLayout(): AfterDarkLayout
    {
        return AfterDarkLayout::fromValue($this->after_dark_layout);
    }

    public static function findIdentity($id): ?self
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     */
    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds an active account by its SteamID64.
     */
    public static function findBySteamId(string $steamId): ?self
    {
        return static::findOne(['steam_id' => $steamId, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Returns the Steam account for this SteamID64, creating it on first
     * sign-in. The verified Steam identity is enough to activate the account
     * immediately; the (optional) email is added and confirmed separately. The
     * public profile summary, when available, fills in the display name on
     * creation and refreshes the avatar/profile URL on every sign-in.
     *
     * @param array<string, mixed> $summary GetPlayerSummaries player node.
     */
    public static function findOrCreateBySteam(string $steamId, array $summary = []): self
    {
        $user = static::findBySteamId($steamId);

        if ($user === null) {
            $user = new static();
            $user->steam_id = $steamId;
            $user->status   = self::STATUS_ACTIVE;
            $user->role     = self::ROLE_USER;
            $user->username = static::uniqueUsernameFromSteam($summary['personaname'] ?? null, $steamId);
            $user->generateAuthKey();
        }

        $user->applySteamProfile($summary);
        $user->save(false);

        return $user;
    }

    /**
     * Copies the mutable bits of a Steam profile summary onto the account.
     * Username is intentionally left untouched after creation so a user's later
     * persona-name changes don't churn their local handle.
     *
     * @param array<string, mixed> $summary GetPlayerSummaries player node.
     */
    public function applySteamProfile(array $summary): void
    {
        if (!empty($summary['avatarfull'])) {
            $this->steam_avatar = $summary['avatarfull'];
        }
        if (!empty($summary['profileurl'])) {
            $this->steam_profile_url = $summary['profileurl'];
        }
    }

    /**
     * A unique username seeded from the Steam persona name (or a steam_<id>
     * fallback), suffixed with " #n" on collision so the NOT NULL UNIQUE
     * constraint always holds. Steam accounts never sign in by username, so the
     * value is for display only.
     */
    private static function uniqueUsernameFromSteam(?string $persona, string $steamId): string
    {
        $base = trim((string)$persona);
        if ($base === '') {
            $base = 'steam_' . $steamId;
        }
        $base = mb_substr($base, 0, 240);

        $candidate = $base;
        $suffix = 1;
        while (static::find()->where(['username' => $candidate])->exists()) {
            $candidate = $base . ' #' . (++$suffix);
        }

        return $candidate;
    }

    public function isEmailVerified(): bool
    {
        return (bool)$this->email_verified;
    }

    /**
     * Gets query for the user's owned Steam games (library).
     */
    public function getUserGames(): ActiveQuery
    {
        return $this->hasMany(UserGame::class, ['user_id' => 'id']);
    }

    /** True once the library has been synced from Steam at least once. */
    public function isSteamLibrarySynced(): bool
    {
        return $this->steam_synced_at !== null;
    }

    /**
     * True when the linked Steam profile is public (so the library/wishlist/
     * achievements pulls actually return data). Null visibility (not yet probed)
     * counts as not-public.
     */
    public function isSteamProfilePublic(): bool
    {
        return (int)$this->steam_visibility === SteamApi::VISIBILITY_PUBLIC;
    }

    /** Hours a user must wait between manual "Sync now" requests. */
    public const int STEAM_SYNC_COOLDOWN_HOURS = 25;

    /**
     * Queues a (re-)sync of this user's Steam data by clearing steam_synced_at —
     * the paced SteamUserController cron picks NULL-stamped users first. The pull
     * itself never happens in the request cycle.
     */
    public function enqueueSteamSync(): void
    {
        $this->updateAttributes(['steam_synced_at' => null]);
    }

    /**
     * Queues a full re-sync (library + wishlist + achievements) and stamps the
     * manual request time for the cooldown. Library/wishlist re-run because
     * steam_synced_at is cleared; achievements because every owned game's
     * ach_synced_at is cleared — the two crons pick NULLs up first.
     */
    public function enqueueFullSync(): void
    {
        $this->updateAttributes([
            'steam_synced_at'         => null,
            'steam_sync_requested_at' => date('Y-m-d H:i:s'),
        ]);
        UserGame::updateAll(['ach_synced_at' => null], ['user_id' => $this->id]);
    }

    /** True when the manual full sync may be requested again (24h elapsed). */
    public function canRequestSync(): bool
    {
        return $this->steam_sync_requested_at === null
            || strtotime($this->steam_sync_requested_at) <= strtotime('-' . self::STEAM_SYNC_COOLDOWN_HOURS . ' hours');
    }

    /**
     * Short "time until sync is available again" label (e.g. "23h", "45m"), or
     * null when a sync can be requested now.
     */
    public function getSyncCooldownLabel(): ?string
    {
        if ($this->canRequestSync()) {
            return null;
        }

        $remaining = strtotime($this->steam_sync_requested_at) + self::STEAM_SYNC_COOLDOWN_HOURS * 3600 - time();
        if ($remaining <= 0) {
            return null;
        }

        $hours = intdiv($remaining, 3600);
        return $hours > 0 ? $hours . 'h' : max(1, intdiv($remaining, 60)) . 'm';
    }

    /**
     * Finds the account awaiting confirmation of a newly added email by its
     * verification token. Unlike {@see findByVerificationToken()} (the signup
     * flow, which expects an INACTIVE account), this leaves the account's status
     * alone — the user is an already-active Steam account adding an email.
     */
    public static function findByEmailVerificationToken(string $token): ?self
    {
        if (empty($token)) {
            return null;
        }

        return static::findOne(['verification_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     */
    public static function findByPasswordResetToken(string $token): ?self
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status'               => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     */
    public static function findByVerificationToken(string $token): ?self
    {
        return static::findOne([
            'verification_token' => $token,
            'status'             => self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     */
    public static function isPasswordResetTokenValid(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int)substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    public function getId(): int|string|null
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateEmailVerificationToken(): void
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }
}
