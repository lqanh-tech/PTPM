<?php

return [

    'cache' => [
        'enabled' => true,
        'driver' => 'file',
        'ttl' => [
            'page' => 180,
            'products' => 300,
            'categories' => 600,
            'static' => 3600,
            'ratings' => 600,
        ],
        'exclude_paths' => [
            '/administrator/',
            '/api/',
            '/payment/',
            '/checkout',
            '/cart',
        ],
    ],
    
    'compression' => [
        'enabled' => true,
        'level' => 6,
        'min_size' => 1024,
    ],
    
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
    
    'database' => [
        'query_cache' => true,
        'persistent_connections' => false,
        'slow_query_log' => true,
        'slow_query_threshold' => 1.0,
    ],
    
    'frontend' => [
        'minify_html' => true,
        'minify_css' => true,
        'minify_js' => true,
        'defer_js' => true,
        'preload_fonts' => true,
        'prefetch_links' => true,
    ],
    
    'cloudflare' => [
        'enabled' => true,
        'cache_static_assets' => true,
        'browser_cache_ttl' => 31536000,
        'edge_cache_ttl' => 86400,
    ],
    
    'debug' => [
        'show_metrics' => false,
        'log_slow_queries' => true,
        'log_cache_stats' => false,
    ],
];
