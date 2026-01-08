<?php

if (!class_exists('SessionManager')) {

    $sessionManagerPaths = [
        __DIR__ . '/sessionManager.php',
        __DIR__ . '/../mod/sessionManager.php',
        __DIR__ . '/../../elements_LQA/mod/sessionManager.php',
        './elements_LQA/mod/sessionManager.php',
        '../mod/sessionManager.php'
    ];
    
    $foundSessionManager = false;
    foreach ($sessionManagerPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $foundSessionManager = true;
            break;
        }
    }
    
    if (!$foundSessionManager) {

        if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!class_exists('Logger')) {

    $loggerConfigPaths = [
        __DIR__ . '/../config/logger_config.php',
        __DIR__ . '/../../elements_LQA/config/logger_config.php',
        './elements_LQA/config/logger_config.php',
        '../config/logger_config.php'
    ];
    
    $foundLoggerConfig = false;
    foreach ($loggerConfigPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $foundLoggerConfig = true;
            break;
        }
    }
}

if (class_exists('SessionManager')) {
    SessionManager::start();
} else if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {

    session_start();
}

function safeRedirect($url, $statusCode = 302) {
    if (class_exists('ResponseManager')) {
        ResponseManager::redirect($url, $statusCode);
        return;
    }
    
    if (headers_sent($file, $line)) {
        echo "<script>window.location.href = '" . addslashes($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
        exit;
    }
    
    header("Location: $url", true, $statusCode);
    exit;
}

function safeLog($message, $level = 'info', $context = []) {
    if (class_exists('Logger')) {
        switch ($level) {
            case 'debug':
                Logger::debug($message, $context);
                break;
            case 'info':
                Logger::info($message, $context);
                break;
            case 'warning':
                Logger::warning($message, $context);
                break;
            case 'error':
                Logger::error($message, $context);
                break;
            default:
                Logger::info($message, $context);
        }
    } else {
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        error_log("[$level] $message$contextStr");
    }
}