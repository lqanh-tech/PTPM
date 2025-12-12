<?php

/**
 * Main Application Configuration
 * Centralized configuration management
 */

return [
    // Application Settings
    'app' => [
        'name' => 'PHP Sales Management System',
        'version' => '2.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'timezone' => 'Asia/Ho_Chi_Minh'
    ],

    // URL Configuration
    // 💡 Tip: Chỉ cần thay đổi BASE_URL trong .env, tất cả URL khác sẽ được tính tự động
    'url' => [
        'base' => $_ENV['BASE_URL'] ?? 'http://localhost:18080',
        'local' => 'http://localhost:18080/lequocanh',
        'tunnel' => $_ENV['BASE_URL'] ?? '', // Cloudflare tunnel URL (nếu có)
        'assets' => '/lequocanh/public_files'
    ],

    // Security Settings
    'security' => [
        'session_lifetime' => 7200, // 2 hours
        'csrf_protection' => true,
        'rate_limiting' => true,
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-key'
    ],

    // Performance Settings
    'performance' => [
        'enable_compression' => true,
        'enable_caching' => true,
        'cache_duration' => 3600,
        'query_cache' => true
    ],

    // File Upload Settings
    'uploads' => [
        'max_size' => '10M',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'path' => '/administrator/uploads/',
        'temp_path' => '/tmp/uploads/'
    ]
];
