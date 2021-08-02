<?php

namespace common\components;

/**
 * Internal access control filter.
 */
class AccessControl extends \yii\filters\AccessControl
{
    /**
     * {@inheritdoc}
     */
    public $ruleConfig = [
        'class' => 'common\components\AccessRule',
    ];
}
