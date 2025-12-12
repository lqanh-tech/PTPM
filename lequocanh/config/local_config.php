<?php

/**
 * Local Development Configuration
 * Use this for faster development without ngrok
 */

// Check if we're running locally
$isLocal = isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'localhost') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '::1') === 0
);

// Check if user wants to force tunnel usage
$forceTunnel = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($isLocal && !$forceTunnel) {
    // Local development settings when not forcing tunnel
    if (!defined('LOCAL_DEV')) {
        define('LOCAL_DEV', true);
    }
    if (!defined('LOCAL_BASE_URL')) {
        define('LOCAL_BASE_URL', 'http://localhost/lequocanh');
    }

    // Override the tunnel redirect in bootstrap if local and not forcing tunnel
    if (defined('BASE_URL') && (strpos(BASE_URL, 'ngrok') !== false || strpos(BASE_URL, 'trycloudflare.com') !== false)) {
        if (!defined('BASE_URL_OVERRIDE')) {
            define('BASE_URL_OVERRIDE', LOCAL_BASE_URL);
        }
    }
} else {
    if (!defined('LOCAL_DEV')) {
        define('LOCAL_DEV', false);
    }
}

/**
 * Performance optimization settings
 */
if (!defined('ENABLE_COMPRESSION')) {
    define('ENABLE_COMPRESSION', true);
}
if (!defined('ENABLE_CACHING')) {
    define('ENABLE_CACHING', true);
}
if (!defined('CACHE_DURATION')) {
    define('CACHE_DURATION', 3600); // 1 hour
}

/**
 * Resource optimization
 */
if (!function_exists('optimizeResources')) {
    function optimizeResources()
    {
        if (!LOCAL_DEV && ENABLE_COMPRESSION) {
            // Enable gzip compression
            if (!ob_get_level() && extension_loaded('zlib')) {
                ob_start('ob_gzhandler');
            }
        }

        if (ENABLE_CACHING) {
            // Set cache headers for static resources
            $extension = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
            $cacheableExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg'];

            if (in_array($extension, $cacheableExtensions)) {
                header('Cache-Control: public, max-age=' . CACHE_DURATION);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + CACHE_DURATION) . ' GMT');
            }
        }
    }
}

// Auto-optimize if this file is included
optimizeResources();