<?php

namespace common\components;

/**
 * Internal access rule which takes into account the status of the user account.
 *
 */
class AccessRule extends \yii\filters\AccessRule
{
    /**
     * List of statuses that this rule applies to.
     * @var array
     */
    public $statuses = [];

    /**
     * {@inheritdoc}
     * @param WebUser $user
     */
    public function allows($action, $user, $request)
    {
        if ($this->matchStatus($user)) {
            return parent::allows($action, $user, $request);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function matchRole($user)
    {
        if (empty($this->roles)) {
            return true;
        }
        foreach ($this->roles as $role) {
            if ($role === '?') {
                if ($user->getIsGuest()) {
                    return true;
                }
            } elseif ($role === '@') {
                if (!$user->getIsGuest()) {
                    return true;
                }
            } elseif (in_array($user->identity->role, $this->roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param WebUser $user
     * @return bool
     */
    protected function matchStatus($user)
    {
        return empty($this->statuses) || $user->hasStatus($this->statuses);
    }
}
