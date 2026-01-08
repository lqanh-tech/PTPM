<?php

require_once __DIR__ . '/CacheManager.php';

class PageCache {
    private static $instance = null;
    private $cache;
    private $enabled = true;
    private $startTime;
    private $cacheKey;
    
    private $excludedPages = [
        'checkout', 'cart', 'login', 'logout', 'admin', 
        'payment', 'api', 'userAct', 'giohangAct'
    ];
    
    private $excludedParams = [
        'nocache', 'clear_session', 'payment_success'
    ];
    
    private function __construct() {
        $this->cache = CacheManager::getInstance();
        $this->startTime = microtime(true);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function shouldCache() {

        if (isset($_SESSION['ADMIN'])) {
            return false;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }
        
        foreach ($this->excludedParams as $param) {
            if (isset($_GET[$param])) {
                return false;
            }
        }
        
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($this->excludedPages as $page) {
            if (stripos($uri, $page) !== false) {
                return false;
            }
        }
        
        return $this->enabled;
    }
    
    public function start($ttl = 180) {
        if (!$this->shouldCache()) {
            return false;
        }
        
        $this->cacheKey = $this->generateKey();
        
        $cached = $this->cache->get($this->cacheKey);
        if ($cached !== null) {

            header('X-Page-Cache: HIT');
            header('X-Cache-Time: ' . round((microtime(true) - $this->startTime) * 1000, 2) . 'ms');
            echo $cached;
            exit;
        }
        
        header('X-Page-Cache: MISS');
        ob_start();
        
        return true;
    }
    
    public function end($ttl = 180) {
        if (!$this->shouldCache() || !$this->cacheKey) {
            return;
        }
        
        $content = ob_get_contents();
        
        if (http_response_code() === 200 && strlen($content) > 100) {

            $cacheInfo = "\n<!-- Cached: " . date('Y-m-d H:i:s') . " | TTL: {$ttl}s -->";
            $content .= $cacheInfo;
            
            $this->cache->set($this->cacheKey, $content, $ttl);
        }
        
        ob_end_flush();
    }
    
    private function generateKey() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $isLoggedIn = isset($_SESSION['USER']) ? '1' : '0';
        
        return 'page_' . md5($uri . '_' . $isLoggedIn);
    }
    
    public function invalidatePage($uri) {
        $key = 'page_' . md5($uri . '_0');
        $this->cache->delete($key);
        $key = 'page_' . md5($uri . '_1');
        $this->cache->delete($key);
    }
    
    public function invalidateAll() {
        $this->cache->clear();
    }
    
    public function disable() {
        $this->enabled = false;
    }
    
    public function enable() {
        $this->enabled = true;
    }
}

if (!function_exists('page_cache_start')) {
    function page_cache_start($ttl = 180) {
        return PageCache::getInstance()->start($ttl);
    }
}

if (!function_exists('page_cache_end')) {
    function page_cache_end($ttl = 180) {
        PageCache::getInstance()->end($ttl);
    }
}
