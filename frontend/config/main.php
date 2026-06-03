<?php

use frontend\modules\user\UserModule;
use yii\log\FileTarget;
use common\models\User;
use common\components\WebUser;
use frontend\modules\company\CompanyModule;
use frontend\modules\developer\DeveloperModule;
use frontend\modules\game\GameModule;
use frontend\modules\homepage\HomepageModule;
use frontend\modules\publisher\PublisherModule;

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
            'loginUrl'        => ['/user/auth/login'],
        ],
        'session'      => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'session',
        ],
        'authClientCollection' => [
            'class'   => \yii\authclient\Collection::class,
            'clients' => [
                'steam' => [
                    'class' => \common\components\auth\SteamOpenId::class,
                ],
            ],
        ],
        'log'          => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => FileTarget::class,
                    'levels' => ['error', 'warning'],
                    // 404 context is logged at info level under its own category;
                    // keep it out of the main error log to avoid duplication/noise.
                    'except' => [\common\components\NotFoundLogger::CATEGORY . '*'],
                ],
                [
                    // Dedicated 404/4xx tracing — referrer + request context.
                    'class'      => FileTarget::class,
                    'levels'     => ['info'],
                    // Wildcard captures both human (`notfound`) and bot (`notfound.bot`).
                    'categories' => [\common\components\NotFoundLogger::CATEGORY . '*'],
                    'logFile'    => '@runtime/logs/notfound.log',
                    'logVars'    => [], // we capture exactly the fields we need
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
                '/'                                                    => 'homepage/home/index',
                'login'                                                => 'user/auth/login',
                'logout'                                               => 'user/auth/logout',
                'signup'                                               => 'user/auth/signup',
                'auth/<authclient:\w+>'                                => 'user/auth/auth',
                'profile'                                              => 'user/profile/index',
                'profile/<action>'                                     => 'user/profile/<action>',
                'games'                                                => 'game/game/index',
                'games/<type:bestsellers|new-and-noteworthy|upcoming>' => 'game/game/sale',
                'genres'                                               => 'game/game/genres',
                'tags'                                                 => 'game/game/tags',
                'categories'                                           => 'game/game/categories',
                'how-it-works'                                         => 'site/how-it-works',
                'contact'                                              => 'site/contact',
                'game/tag/<slug>'                                      => 'game/game/list-by-tag',
                'game/<id:\d+>/<slug>/achievements'                    => 'game/game/achievements',
                'game/<id>/<slug>'                                     => 'game/game/view',
                '<alias:games>/<slug>'                                 => 'game/game/list',
                'game/<action>'                                        => 'game/game/<action>',
                'developers'                                           => 'developer/developer/index',
                'developer/<slug>'                                     => 'developer/developer/view',
                'publishers'                                           => 'publisher/publisher/index',
                'publisher/<slug>'                                     => 'publisher/publisher/view',
                'company/<slug>'                                       => 'company/company/view',
            ],
        ],
    ],
    'modules'             => [
        'homepage'  => [
            'class' => HomepageModule::class,
        ],
        'game'      => [
            'class' => GameModule::class,
        ],
        'developer' => [
            'class' => DeveloperModule::class,
        ],
        'publisher' => [
            'class' => PublisherModule::class,
        ],
        'company'   => [
            'class' => CompanyModule::class,
        ],
        'user'   => [
            'class' => UserModule::class,
        ],
    ],
    'params'              => $params,
];
