<?php

declare(strict_types=1);

if (!function_exists('route')) {
    /**
     * Generate a URL from a path relative to base_url().
     *
     * @param string $path Path relative to base URL (e.g., 'product/123')
     * @return string Full URL (e.g., 'https://example.com/product/123')
     */
    function route(string $path = ''): string
    {
        $base = rtrim(base_url(), '/');
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }
}

if (!function_exists('asset_url')) {
    /**
     * Generate a URL for a public asset file.
     *
     * @param string $path Path relative to public_files/ (e.g., 'css/app.css')
     * @return string Full URL (e.g., 'https://example.com/lequocanh/public_files/css/app.css')
     */
    function asset_url(string $path): string
    {
        $base = rtrim(base_url(), '/');
        $path = '/lequocanh/public_files/' . ltrim($path, '/');
        return $base . $path;
    }
}

if (!function_exists('admin_url')) {
    /**
     * Generate a URL for an admin panel page.
     *
     * @param string $path Path relative to administrator/ (e.g., 'index.php?req=hanghoaview')
     * @return string Full URL
     */
    function admin_url(string $path = ''): string
    {
        $base = rtrim(base_url(), '/');
        $path = '/lequocanh/administrator/' . ltrim($path, '/');
        return $base . $path;
    }
}
