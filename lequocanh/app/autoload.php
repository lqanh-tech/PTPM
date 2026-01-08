<?php

spl_autoload_register(function ($class) {
    $serviceMap = [
        'ProductService' => __DIR__ . '/Services/ProductService.php',
        'CategoryService' => __DIR__ . '/Services/CategoryService.php',
        'UserService' => __DIR__ . '/Services/UserService.php',
        'OrderService' => __DIR__ . '/Services/OrderService.php',
        'ShippingService' => __DIR__ . '/Services/ShippingService.php',
    ];

    if (isset($serviceMap[$class])) {
        require_once $serviceMap[$class];
    }
});

require_once __DIR__ . '/Services/ProductService.php';
require_once __DIR__ . '/Services/CategoryService.php';
require_once __DIR__ . '/Services/UserService.php';
require_once __DIR__ . '/Services/OrderService.php';
require_once __DIR__ . '/Services/ShippingService.php';
