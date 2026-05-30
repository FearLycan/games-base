<?php

namespace backend\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private ?\common\models\User $_user = null;
    private bool $_userResolved = false;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     */
    public function validatePassword($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();

        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Incorrect username or password.');
            return;
        }

        $status = (int)$user->status;

        if ($status === \common\models\User::STATUS_INACTIVE) {
            $this->addError($attribute, 'Please verify your email address before signing in. You can request a new verification link from the sign-in page.');
            return;
        }

        if ($status !== \common\models\User::STATUS_ACTIVE) {
            $this->addError($attribute, 'Incorrect username or password.');
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->getUser();
        if (!$user || (int)$user->status !== \common\models\User::STATUS_ACTIVE) {
            return false;
        }

        return Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    /**
     * Finds user by [[username]] regardless of status, so we can give better
     * feedback for inactive accounts. The login() method gates by status.
     */
    protected function getUser(): ?\common\models\User
    {
        if (!$this->_userResolved) {
            $this->_user = \common\models\User::find()
                ->where(['username' => $this->username])
                ->andWhere(['!=', 'status', \common\models\User::STATUS_DELETED])
                ->one();
            $this->_userResolved = true;
        }

        return $this->_user;
    }
}
