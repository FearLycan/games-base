<?php

namespace backend\modules\admin\models;

use backend\models\User;
use yii\base\Model;

/**
 * Backend form for creating/editing users without touching the shared
 * common\models\User rule set (which the frontend auth flow relies on).
 *
 * Holds username/email/role/status plus an optional password, validates them
 * here, then persists onto a User record via {@see save()}.
 */
class UserForm extends Model
{
    public ?string $username = null;
    public ?string $email = null;
    public ?int $role = User::ROLE_USER;
    public ?int $status = User::STATUS_ACTIVE;
    public ?string $password = null;

    private ?User $user;

    public function __construct(?User $user = null, array $config = [])
    {
        $this->user = $user;

        if ($user !== null && !$user->isNewRecord) {
            $this->username = $user->username;
            $this->email = $user->email;
            $this->role = $user->role;
            $this->status = $user->status;
        }

        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['username', 'email'], 'required'],
            [['username', 'email'], 'trim'],
            [['username', 'email'], 'string', 'max' => 255],
            ['email', 'email'],
            ['username', 'unique', 'targetClass' => User::class, 'filter' => $this->ignoreSelf()],
            ['email', 'unique', 'targetClass' => User::class, 'filter' => $this->ignoreSelf()],
            ['role', 'in', 'range' => array_keys(self::roleOptions())],
            ['status', 'in', 'range' => array_keys(self::statusOptions())],
            ['password', 'string', 'min' => 6],
            ['password', 'required', 'when' => fn(): bool => $this->isNewRecord()],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username' => 'Username',
            'email'    => 'Email',
            'role'     => 'Role',
            'status'   => 'Status',
            'password' => $this->isNewRecord() ? 'Password' : 'New password (leave blank to keep current)',
        ];
    }

    /** Excludes the edited user from the uniqueness checks. */
    private function ignoreSelf(): callable
    {
        return function ($query): void {
            if ($this->user !== null && !$this->user->isNewRecord) {
                $query->andWhere(['<>', 'id', $this->user->id]);
            }
        };
    }

    public function isNewRecord(): bool
    {
        return $this->user === null || $this->user->isNewRecord;
    }

    public function getUser(): User
    {
        return $this->user ??= new User();
    }

    /** Persists the form onto the User model. */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->getUser();
        $isNew = $user->isNewRecord;

        $user->username = $this->username;
        $user->email = $this->email;
        $user->role = $this->role;
        $user->status = $this->status;

        if ($isNew) {
            $user->generateAuthKey();
        }

        if (!empty($this->password)) {
            $user->setPassword($this->password);
        }

        return $user->save(false);
    }

    /** @return array<int, string> */
    public static function roleOptions(): array
    {
        return [
            User::ROLE_USER  => 'User',
            User::ROLE_ADMIN => 'Admin',
        ];
    }

    /** @return array<int, string> */
    public static function statusOptions(): array
    {
        return [
            User::STATUS_ACTIVE   => 'Active',
            User::STATUS_INACTIVE => 'Inactive',
            User::STATUS_DELETED  => 'Deleted',
        ];
    }
}
