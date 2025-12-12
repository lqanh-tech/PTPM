<?php
/**
 * Optimized Header - Include ở đầu các trang cần tối ưu
 * Tự động áp dụng cache, compression, và performance headers
 */

// Đo thời gian bắt đầu
define('PAGE_START_TIME', microtime(true));

// Load cache systems
$cacheDir = __DIR__ . '/../cache';
if (file_exists($cacheDir . '/CacheManager.php')) {
    require_once $cacheDir . '/CacheManager.php';
}
if (file_exists($cacheDir . '/PageCache.php')) {
    require_once $cacheDir . '/PageCache.php';
}
if (file_exists($cacheDir . '/QueryCache.php')) {
    require_once $cacheDir . '/QueryCache.php';
}

/**
 * Set optimized headers cho Cloudflare
 */
function setOptimizedHeaders($cacheSeconds = 0) {
    if (headers_sent()) return;
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Cache headers
    if ($cacheSeconds > 0) {
        header("Cache-Control: public, max-age={$cacheSeconds}, s-maxage={$cacheSeconds}");
        header("CDN-Cache-Control: max-age={$cacheSeconds}");
        header("Cloudflare-CDN-Cache-Control: max-age={$cacheSeconds}");
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}

/**
 * Preload critical resources
 */
function preloadCriticalResources() {
    if (headers_sent()) return;
    
    // Preload CSS
    header('Link: </lequocanh/public_files/critical.css>; rel=preload; as=style', false);
    
    // Preload fonts
    header('Link: <https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2>; rel=preload; as=font; crossorigin', false);
    
    // DNS prefetch
    header('Link: <https://cdn.jsdelivr.net>; rel=dns-prefetch', false);
    header('Link: <https://cdnjs.cloudflare.com>; rel=dns-prefetch', false);
}

/**
 * Enable output compression
 */
function enableCompression() {
    if (ob_get_level() == 0 && !headers_sent()) {
        if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'On');
            ini_set('zlib.output_compression_level', '6');
        }
    }
}

/**
 * Render performance metrics (chỉ trong development)
 */
function renderPerformanceMetrics() {
    if (!defined('APP_DEBUG') || !APP_DEBUG) return;
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - PAGE_START_TIME) * 1000, 2);
    $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    echo "\n<!-- Performance: {$executionTime}ms | Memory: {$memoryUsage}MB -->\n";
}

/**
 * Minify HTML output
 */
function minifyHTML($html) {
    // Không minify trong development
    if (defined('APP_DEBUG') && APP_DEBUG) {
        return $html;
    }
    
    // Loại bỏ comments HTML (trừ IE conditionals)
    $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
    
    // Loại bỏ whitespace thừa
    $html = preg_replace('/\s+/', ' ', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    
    return trim($html);
}

/**
 * Start optimized output buffering
 */
function startOptimizedOutput($minify = true) {
    if ($minify) {
        ob_start(function($buffer) {
            return minifyHTML($buffer);
        });
    } else {
        ob_start();
    }
}

// Auto-initialize
enableCompression();
preloadCriticalResources();

// Register shutdown function
register_shutdown_function('renderPerformanceMetrics');

/**
 * Helper: Get cached data hoặc execute callback
 */
function getCached($key, $ttl, callable $callback) {
    if (!class_exists('CacheManager')) {
        return $callback();
    }
    
    return CacheManager::getInstance()->remember($key, $ttl, $callback);
}
