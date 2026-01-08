<?php

class Performance {
    
    public static function enableCompression() {

        if (ob_get_level() == 0) {

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
    
    public static function setCloudflareCache($maxAge = 3600, $isPublic = true) {
        if (headers_sent()) return;
        
        $cacheControl = $isPublic ? 'public' : 'private';
        header("Cache-Control: {$cacheControl}, max-age={$maxAge}, s-maxage={$maxAge}");
        header("CDN-Cache-Control: max-age={$maxAge}");
        header("Cloudflare-CDN-Cache-Control: max-age={$maxAge}");
    }
    
    public static function noCache() {
        if (headers_sent()) return;
        
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    }
    
    public static function staticAssetCache() {
        if (headers_sent()) return;
        
        $oneYear = 31536000;
        header("Cache-Control: public, max-age={$oneYear}, immutable");
        header("CDN-Cache-Control: max-age={$oneYear}");
    }
    
    public static function setBrowserCache($seconds = 86400) {
        $expires = gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT';
        header("Cache-Control: public, max-age=$seconds");
        header("Expires: $expires");
        header("Pragma: cache");
    }
    
    public static function minifyHTML($html) {

        $html = preg_replace('/<!--(?!<!)[^\[>].*?-->/s', '', $html);
        
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        return trim($html);
    }
    
    public static function startMinification() {
        ob_start(function($buffer) {
            return Performance::minifyHTML($buffer);
        });
    }
    
    public static function lazyLoadImages($html) {
        return preg_replace(
            '/<img(.*?)src=/i',
            '<img$1loading="lazy" src=',
            $html
        );
    }
    
    public static function optimizedImage($src, $alt = '', $class = '') {
        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async">',
            htmlspecialchars($src),
            htmlspecialchars($alt),
            htmlspecialchars($class)
        );
    }
    
    public static function deferJS($html) {
        return preg_replace(
            '/<script(.*?)src=/i',
            '<script$1defer src=',
            $html
        );
    }
    
    public static function preloadResource($url, $type = 'style') {
        header("Link: <$url>; rel=preload; as=$type", false);
    }
    
    public static function setPerformanceHeaders() {

        header('X-Accel-Buffering: no');
        
        header('Accept-CH: DPR, Viewport-Width, Width');
        
        header('X-Content-Type-Options: nosniff');
    }
}

Performance::setPerformanceHeaders();
