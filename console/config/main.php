<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id'                  => 'app-console',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases'             => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap'       => [
        'fixture' => [
            'class'     => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
        ],
    ],
    'components'          => [
        'cache'         => [
            'class'     => \yii\caching\FileCache::class,
            'cachePath' => dirname(__DIR__, 2) . '/console/runtime/cache',
        ],
        'frontendCache' => [
            'class'     => \yii\caching\FileCache::class,
            'cachePath' => dirname(__DIR__, 2) . '/frontend/runtime/cache',
        ],
        'backendCache'  => [
            'class'     => \yii\caching\FileCache::class,
            'cachePath' => dirname(__DIR__, 2) . '/backend/runtime/cache',
        ],
        'schemaCache'   => [
            'class'     => \yii\caching\FileCache::class,
            'cachePath' => dirname(__DIR__, 2) . '/console/runtime/schema-cache',
        ],
        'log'           => [
            'targets' => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params'              => $params,
];
