<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'name' => 'Gamentator',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'defaultRoute' => 'homepage/home/index',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'class' => 'common\components\WebUser',
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'session',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'homepage/home/index',
                'game/<id>/<slug>' => 'game/game/view',
                //'ticket/create/<type>/<object_id>' => 'ticket/create',
                '<alias:games>/<slug>' => 'game/game/list',
                //'<alias:product>/<id:\w+>' => 'site/<alias>',
                'game/<action>' => 'game/game/<action>',
            ],
        ],
    ],
    'modules' => [
        'homepage' => [
            'class' => 'frontend\modules\homepage\Module',
        ],
        'game' => [
            'class' => 'frontend\modules\game\Module',
        ],
    ],
    'params' => $params,
];
