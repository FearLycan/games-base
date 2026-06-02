<?php

namespace frontend\modules\user\models;

use common\models\User;
use Yii;
use yii\base\Model;

/**
 * Login form (username + password) for the frontend.
 *
 * Steam accounts authenticate through OpenID instead (see AuthController), so
 * this only covers local password accounts.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private ?User $_user = null;
    private bool $_userResolved = false;

    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password against the resolved user.
     */
    public function validatePassword($attribute, $params): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();

        if (!$user || !$user->password_hash || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Incorrect username or password.');
            return;
        }

        $status = (int)$user->status;

        if ($status === User::STATUS_INACTIVE) {
            $this->addError($attribute, 'Please verify your email address before signing in. You can request a new verification link from the sign-in page.');
            return;
        }

        if ($status !== User::STATUS_ACTIVE) {
            $this->addError($attribute, 'Incorrect username or password.');
        }
    }

    /**
     * Logs in a user using the provided username and password.
     */
    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->getUser();
        if (!$user || (int)$user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        return Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    /**
     * Finds user by [[username]] regardless of status (except deleted), so we
     * can give better feedback for inactive accounts. {@see login()} gates by
     * status.
     */
    protected function getUser(): ?User
    {
        if (!$this->_userResolved) {
            $this->_user = User::find()
                ->where(['username' => $this->username])
                ->andWhere(['!=', 'status', User::STATUS_DELETED])
                ->one();
            $this->_userResolved = true;
        }

        return $this->_user;
    }
}
