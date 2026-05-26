<?php

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'mysql')
                . ';dbname=' . (getenv('DB_NAME') ?: 'yii2advanced'),
            'username' => getenv('DB_USER') ?: 'yii2advanced',
            'password' => getenv('DB_PASSWORD') ?: 'secret',
            'charset' => 'utf8mb4',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@common/mail',
        ],
    ],
];
