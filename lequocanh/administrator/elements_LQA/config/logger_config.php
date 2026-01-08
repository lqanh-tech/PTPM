<?php

require_once __DIR__ . '/../mod/loggerCls.php';

$environment = getenv('APP_ENV') ?: 'production';

$isDevelopment = (
    $environment === 'development' || 
    $environment === 'dev' ||
    getenv('DEBUG') === 'true' ||
    (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
);

if ($isDevelopment) {

    if (class_exists('Logger')) {
        Logger::configure([
            'logLevel' => Logger::DEBUG,
            'enabled' => true
        ]);
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {

    if (class_exists('Logger')) {
        Logger::configure([
            'logLevel' => Logger::WARNING,
            'enabled' => true
        ]);
    }
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

if (class_exists('Logger')) {
    Logger::info('Logger configured', [
        'environment' => $environment,
        'development_mode' => $isDevelopment,
        'level' => $isDevelopment ? 'DEBUG' : 'WARNING'
    ]);
}