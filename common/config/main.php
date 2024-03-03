<?php

use yii\caching\FileCache;

return [
    'timeZone'   => 'Europe/Warsaw',
    'aliases'    => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'components' => [
        'cache'        => [
            'class' => FileCache::class,
        ],
        'assetManager' => [
            'appendTimestamp' => true,
        ],
    ],
];
