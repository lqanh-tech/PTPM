<?php

return [

    'app' => [
        'name' => 'PHP Sales Management System',
        'version' => '2.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'timezone' => 'Asia/Ho_Chi_Minh'
    ],

    'url' => [
        'base' => $_ENV['BASE_URL'] ?? 'http://localhost:18080',
        'local' => 'http://localhost:18080/lequocanh',
        'tunnel' => $_ENV['BASE_URL'] ?? '',
        'assets' => '/lequocanh/public_files'
    ],

    'security' => [
        'session_lifetime' => 7200,
        'csrf_protection' => true,
        'rate_limiting' => true,
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-key'
    ],

    'performance' => [
        'enable_compression' => true,
        'enable_caching' => true,
        'cache_duration' => 3600,
        'query_cache' => true
    ],

    'uploads' => [
        'max_size' => '10M',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'path' => '/administrator/uploads/',
        'temp_path' => '/tmp/uploads/'
    ]
];
