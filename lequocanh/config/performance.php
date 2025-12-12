<?php
/**
 * Performance Configuration
 * Cấu hình tối ưu hiệu suất cho website
 */

return [
    // Cache settings
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // file, redis, memcached
        'ttl' => [
            'page' => 180,        // 3 phút cho page cache
            'products' => 300,    // 5 phút cho sản phẩm
            'categories' => 600,  // 10 phút cho danh mục
            'static' => 3600,     // 1 giờ cho nội dung tĩnh
            'ratings' => 600,     // 10 phút cho ratings
        ],
        'exclude_paths' => [
            '/administrator/',
            '/api/',
            '/payment/',
            '/checkout',
            '/cart',
        ],
    ],
    
    // Compression settings
    'compression' => [
        'enabled' => true,
        'level' => 6,
        'min_size' => 1024, // Chỉ compress files > 1KB
    ],
    
    // Image optimization
    'images' => [
        'lazy_load' => true,
        'webp_enabled' => true,
        'quality' => 85,
        'max_width' => 1200,
        'thumbnail_sizes' => [
            'small' => 150,
            'medium' => 300,
            'large' => 600,
        ],
    ],
    
    // Database optimization
    'database' => [
        'query_cache' => true,
        'persistent_connections' => false,
        'slow_query_log' => true,
        'slow_query_threshold' => 1.0, // seconds
    ],
    
    // Frontend optimization
    'frontend' => [
        'minify_html' => true,
        'minify_css' => true,
        'minify_js' => true,
        'defer_js' => true,
        'preload_fonts' => true,
        'prefetch_links' => true,
    ],
    
    // Cloudflare settings
    'cloudflare' => [
        'enabled' => true,
        'cache_static_assets' => true,
        'browser_cache_ttl' => 31536000, // 1 year
        'edge_cache_ttl' => 86400,       // 1 day
    ],
    
    // Debug settings
    'debug' => [
        'show_metrics' => false,
        'log_slow_queries' => true,
        'log_cache_stats' => false,
    ],
];
