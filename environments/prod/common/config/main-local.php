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
        // Redis connection. On MyDevil the server is a self-launched process
        // reached over a unix socket; set REDIS_SOCKET to its path. Falls back
        // to TCP (REDIS_HOST/REDIS_PORT) when no socket is given. REDIS_PASSWORD
        // is required on shared hosting so other users can't read the cache.
        'redis' => [
            'class' => 'yii\redis\Connection',
            'unixSocket' => getenv('REDIS_SOCKET') ?: null,
            'hostname' => getenv('REDIS_HOST') ?: 'localhost',
            'port' => (int)(getenv('REDIS_PORT') ?: 6379),
            'password' => getenv('REDIS_PASSWORD') ?: null,
            'database' => (int)(getenv('REDIS_DB') ?: 0),
        ],
        // Override main.php's FileCache: production caches in Redis.
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
    ],
];
