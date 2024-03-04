<?php

use yii\log\FileTarget;
use common\models\User;
use common\components\WebUser;
use frontend\modules\game\GameModule;
use frontend\modules\homepage\HomepageModule;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id'                  => 'gamentator-app',
    'name'                => 'Gamentator',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'defaultRoute'        => 'homepage/home/index',
    'components'          => [
        'request'      => [
            'csrfParam' => '_csrf-gamentator',
        ],
        'user'         => [
            'class'           => WebUser::class,
            'identityClass'   => User::class,
            'enableAutoLogin' => true,
            'identityCookie'  => ['name' => '_identity', 'httpOnly' => true],
        ],
        'session'      => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'session',
        ],
        'log'          => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager'   => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [
                '/'                    => 'homepage/home/index',
                'game/<id>/<slug>'     => 'game/game/view',
                '<alias:games>/<slug>' => 'game/game/list',
                'game/<action>'        => 'game/game/<action>',
            ],
        ],
    ],
    'modules'             => [
        'homepage' => [
            'class' => HomepageModule::class,
        ],
        'game'     => [
            'class' => GameModule::class,
        ],
    ],
    'params'              => $params,
];
