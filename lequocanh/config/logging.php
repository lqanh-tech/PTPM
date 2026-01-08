<?php

return [
    'default' => 'file',

    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/application.log',
            'level' => $_ENV['LOG_LEVEL'] ?? 'info',
            'max_files' => 14,
            'bubble' => true
        ],

        'error' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/error.log',
            'level' => 'error'
        ],

        'security' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/security.log',
            'level' => 'warning'
        ],

        'performance' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/performance.log',
            'level' => 'info'
        ]
    ],

    'levels' => [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7
    ],

    'log_queries' => $_ENV['LOG_QUERIES'] ?? false,
    'log_requests' => $_ENV['LOG_REQUESTS'] ?? true,
    'log_errors' => true,
    'log_performance' => true
];
