<?php

return [
    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_DATABASE'] ?? 'trainingdb',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? 'pw',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ],

        'mysql_docker' => [
            'driver' => 'mysql',
            'host' => 'mysql',
            'port' => 3306,
            'database' => 'trainingdb',
            'username' => 'root',
            'password' => 'pw',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ]
    ],

    'fallback_connections' => [
        ['host' => 'mysql', 'port' => 3306],
        ['host' => 'localhost', 'port' => 3306],
        ['host' => '127.0.0.1', 'port' => 3306]
    ],

    'query' => [
        'cache_enabled' => true,
        'cache_duration' => 300,
        'slow_query_log' => true,
        'slow_query_threshold' => 1.0
    ]
];
