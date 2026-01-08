<?php

$isLocal = isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'localhost') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '::1') === 0
);

$forceTunnel = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($isLocal && !$forceTunnel) {

    if (!defined('LOCAL_DEV')) {
        define('LOCAL_DEV', true);
    }
    if (!defined('LOCAL_BASE_URL')) {
        define('LOCAL_BASE_URL', 'http://localhost/lequocanh');
    }

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

if (!defined('ENABLE_COMPRESSION')) {
    define('ENABLE_COMPRESSION', true);
}
if (!defined('ENABLE_CACHING')) {
    define('ENABLE_CACHING', true);
}
if (!defined('CACHE_DURATION')) {
    define('CACHE_DURATION', 3600);
}

if (!function_exists('optimizeResources')) {
    function optimizeResources()
    {
        if (!LOCAL_DEV && ENABLE_COMPRESSION) {

            if (!ob_get_level() && extension_loaded('zlib')) {
                ob_start('ob_gzhandler');
            }
        }

        if (ENABLE_CACHING) {

            $extension = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
            $cacheableExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg'];

            if (in_array($extension, $cacheableExtensions)) {
                header('Cache-Control: public, max-age=' . CACHE_DURATION);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + CACHE_DURATION) . ' GMT');
            }
        }
    }
}

optimizeResources();