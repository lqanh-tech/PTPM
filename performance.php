<?php
/**
 * Performance Optimization Helper
 * Tối ưu cho Cloudflare
 */

class Performance {
    
    /**
     * Enable output compression
     */
    public static function enableCompression() {
        // Check if output buffering is already active
        if (ob_get_level() == 0) {
            // Check if zlib compression is not already enabled
            if (!ini_get('zlib.output_compression')) {
                if (extension_loaded('zlib') && !headers_sent()) {
                    if (!ob_start('ob_gzhandler')) {
                        ob_start();
                    }
                } else {
                    ob_start();
                }
            }
        }
    }
    
    /**
     * Set Cloudflare-optimized caching headers
     */
    public static function setCloudflareCache($maxAge = 3600, $isPublic = true) {
        if (headers_sent()) return;
        
        $cacheControl = $isPublic ? 'public' : 'private';
        header("Cache-Control: {$cacheControl}, max-age={$maxAge}, s-maxage={$maxAge}");
        header("CDN-Cache-Control: max-age={$maxAge}");
        header("Cloudflare-CDN-Cache-Control: max-age={$maxAge}");
    }
    
    /**
     * Disable caching (for dynamic content)
     */
    public static function noCache() {
        if (headers_sent()) return;
        
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    }
    
    /**
     * Set cache for static assets (1 year)
     */
    public static function staticAssetCache() {
        if (headers_sent()) return;
        
        $oneYear = 31536000;
        header("Cache-Control: public, max-age={$oneYear}, immutable");
        header("CDN-Cache-Control: max-age={$oneYear}");
    }
    
    /**
     * Set browser caching headers
     */
    public static function setBrowserCache($seconds = 86400) {
        $expires = gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT';
        header("Cache-Control: public, max-age=$seconds");
        header("Expires: $expires");
        header("Pragma: cache");
    }
    
    /**
     * Minify HTML output
     */
    public static function minifyHTML($html) {
        // Remove comments
        $html = preg_replace('/<!--(?!<!)[^\[>].*?-->/s', '', $html);
        
        // Remove whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        return trim($html);
    }
    
    /**
     * Start output buffering with minification
     */
    public static function startMinification() {
        ob_start(function($buffer) {
            return Performance::minifyHTML($buffer);
        });
    }
    
    /**
     * Lazy load images
     */
    public static function lazyLoadImages($html) {
        return preg_replace(
            '/<img(.*?)src=/i',
            '<img$1loading="lazy" src=',
            $html
        );
    }
    
    /**
     * Generate optimized image tag
     */
    public static function optimizedImage($src, $alt = '', $class = '') {
        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async">',
            htmlspecialchars($src),
            htmlspecialchars($alt),
            htmlspecialchars($class)
        );
    }
    
    /**
     * Defer JavaScript loading
     */
    public static function deferJS($html) {
        return preg_replace(
            '/<script(.*?)src=/i',
            '<script$1defer src=',
            $html
        );
    }
    
    /**
     * Preload critical resources
     */
    public static function preloadResource($url, $type = 'style') {
        header("Link: <$url>; rel=preload; as=$type", false);
    }
    
    /**
     * Set performance headers
     */
    public static function setPerformanceHeaders() {
        // Enable HTTP/2 Server Push hints
        header('X-Accel-Buffering: no');
        
        // Enable early hints
        header('Accept-CH: DPR, Viewport-Width, Width');
        
        // Optimize for speed
        header('X-Content-Type-Options: nosniff');
    }
}

// Auto-enable performance headers only (compression handled by nginx)
Performance::setPerformanceHeaders();
