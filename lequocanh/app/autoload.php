<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    // PSR-4 autoloading for App\ namespace
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Legacy support: load non-namespaced class names
spl_autoload_register(function ($class) {
    $serviceMap = [
        'CategoryService' => __DIR__ . '/Services/CategoryService.php',
        'UserService' => __DIR__ . '/Services/UserService.php',
        'OrderService' => __DIR__ . '/Services/OrderService.php',
        'ShippingService' => __DIR__ . '/Services/ShippingService.php',
        // Legacy global classes (mod/* now handled by composer classmap)
        'QueryCache' => __DIR__ . '/../cache/QueryCache.php',
        'CacheManager' => __DIR__ . '/../cache/CacheManager.php',
        'ConfigManager' => __DIR__ . '/Services/ConfigManager.php',
    ];

    if (isset($serviceMap[$class])) {
        require_once $serviceMap[$class];
    }
});
