<?php

define('PAGE_START_TIME', microtime(true));

require_once __DIR__ . '/../cache/CacheManager.php';
require_once __DIR__ . '/../cache/QueryCache.php';
require_once __DIR__ . '/../cache/PageCache.php';

function optimizePHPSettings() {

    if (ob_get_level() == 0 && !headers_sent()) {
        if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'On');
            ini_set('zlib.output_compression_level', '6');
        }
    }
    
    if ((int)ini_get('memory_limit') < 128) {
        ini_set('memory_limit', '128M');
    }
}

function setOptimizedHeaders($cacheTime = 0) {
    if (headers_sent()) return;
    
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    
    if ($cacheTime > 0) {
        header("Cache-Control: public, max-age={$cacheTime}, s-maxage={$cacheTime}");
        header("CDN-Cache-Control: max-age={$cacheTime}");
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}

function preloadResources() {
    if (headers_sent()) return;
    
    header('Link: </lequocanh/public_files/critical.css>; rel=preload; as=style', false);
    
    header('Link: <https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2>; rel=preload; as=font; crossorigin', false);
}

function renderPerformanceDebug() {
    if (!defined('APP_DEBUG') || !APP_DEBUG) return;
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - PAGE_START_TIME) * 1000, 2);
    $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    $queryCache = QueryCache::getInstance();
    $cacheStats = $queryCache->getStats();
    
    echo "\n<!-- Performance Debug:\n";
    echo "    Execution Time: {$executionTime}ms\n";
    echo "    Memory Usage: {$memoryUsage}MB\n";
    echo "    Cache Hits: {$cacheStats['hits']}\n";
    echo "    Cache Misses: {$cacheStats['misses']}\n";
    echo "    Cache Hit Rate: {$cacheStats['hit_rate']}%\n";
    echo "-->\n";
}

function minifyHTML($html) {

    if (defined('APP_DEBUG') && APP_DEBUG) {
        return $html;
    }
    
    $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
    
    $html = preg_replace('/\s+/', ' ', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    
    return trim($html);
}

function startOptimizedOutput() {
    ob_start(function($buffer) {
        return minifyHTML($buffer);
    });
}

optimizePHPSettings();

register_shutdown_function('renderPerformanceDebug');
