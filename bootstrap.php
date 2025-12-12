<?php

/**
 * Application Bootstrap - Enhanced with Configuration Management
 * Modern Architecture Foundation with MVC Support
 */

// Load config and perform redirect
require_once __DIR__ . '/lequocanh/config/ConfigManager.php';

// Initialize configuration
$config = ConfigManager::getInstance();

// Get legacy configuration for backward compatibility
$legacyConfig = $config->getLegacyConfig();

// Set up constants and globals (only if not already defined)
if (!defined('BASE_URL')) {
    define('BASE_URL', $config->getBaseUrl());
}
if (!defined('LOCAL_DEV')) {
    define('LOCAL_DEV', $config->get('app.environment') === 'development' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
}

// Automatic redirect logic - redirect localhost to tunnel when FORCE_NGROK or FORCE_TUNNEL is enabled
$forceNgrok = filter_var($_ENV['FORCE_NGROK'] ?? false, FILTER_VALIDATE_BOOLEAN);
$forceTunnel = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Get the configured tunnel URL
$tunnelUrl = $_ENV['BASE_URL'] ?? BASE_URL;

// Check if we're currently on the tunnel domain
$isOnTunnel = isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false || 
    strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false
);

// Only redirect if forcing tunnel/ngrok AND accessing from localhost AND not already on the tunnel
if (($forceNgrok || $forceTunnel) && isset($_SERVER['HTTP_HOST']) && !$isOnTunnel) {
    $currentHost = $_SERVER['HTTP_HOST'];
    // Check if accessing via localhost or 127.0.0.1 (including port)
    if (strpos($currentHost, 'localhost') === 0 || strpos($currentHost, '127.0.1') === 0 || strpos($currentHost, '0.0.0') === 0) {
        if ($tunnelUrl && (strpos($tunnelUrl, 'ngrok') !== false || strpos($tunnelUrl, 'trycloudflare.com') !== false)) {
            // Add a small delay to prevent rapid redirects in case of misconfiguration
            usleep(10000); // 0.1 second delay
            header('Location: ' . $tunnelUrl . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

// Error reporting for development
if ($config->get('app.app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define application constants (only if not already defined)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('APP_ENV')) {
    define('APP_ENV', $config->get('app.app.environment', 'production'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $config->get('app.app.debug', false));
}

// Load security functions
require_once __DIR__ . '/security.php';

// Load performance optimization
require_once __DIR__ . '/performance.php';

// Autoloader for both old and new structure
spl_autoload_register(function ($class) {
    $paths = [
        // New MVC structure
        APP_ROOT . '/lequocanh/app/Controllers/',
        APP_ROOT . '/lequocanh/app/Controllers/Admin/',
        APP_ROOT . '/lequocanh/app/Models/',
        APP_ROOT . '/lequocanh/app/Services/',

        // Existing structure (for backward compatibility)
        APP_ROOT . '/lequocanh/administrator/elements_LQA/mod/',
        APP_ROOT . '/lequocanh/administrator/elements_LQA/monitoring/',
        APP_ROOT . '/lequocanh/api/v1/',
        APP_ROOT . '/lequocanh/api/middleware/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Initialize core services
try {
    // Session Management
    if (class_exists('SessionManager')) {
        SessionManager::start();
    }

    // Error Tracking
    if (class_exists('ErrorTracker')) {
        ErrorTracker::registerErrorHandler();
    }

    // Performance Monitoring
    if (class_exists('RealtimePerformanceMonitor')) {
        RealtimePerformanceMonitor::startOperation('Application');
    }

    // User Activity Tracking
    if (class_exists('UserActivityTracker')) {
        UserActivityTracker::init();
    }
} catch (Exception $e) {
    error_log("Bootstrap error: " . $e->getMessage());
    if (APP_DEBUG) {
        die("Bootstrap failed: " . $e->getMessage());
    }
}

// Register shutdown function
register_shutdown_function(function () {
    if (class_exists('RealtimePerformanceMonitor')) {
        RealtimePerformanceMonitor::endOperation('Application');
    }
});