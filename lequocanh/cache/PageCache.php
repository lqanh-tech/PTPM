<?php
/**
 * Page Cache - Cache toàn bộ HTML output
 * Tối ưu cho các trang ít thay đổi
 */

require_once __DIR__ . '/CacheManager.php';

class PageCache {
    private static $instance = null;
    private $cache;
    private $enabled = true;
    private $startTime;
    private $cacheKey;
    
    // Các trang không cache
    private $excludedPages = [
        'checkout', 'cart', 'login', 'logout', 'admin', 
        'payment', 'api', 'userAct', 'giohangAct'
    ];
    
    // Các tham số không cache
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
    
    /**
     * Kiểm tra xem có nên cache trang này không
     */
    public function shouldCache() {
        // Không cache nếu đã đăng nhập admin
        if (isset($_SESSION['ADMIN'])) {
            return false;
        }
        
        // Không cache POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }
        
        // Kiểm tra excluded params
        foreach ($this->excludedParams as $param) {
            if (isset($_GET[$param])) {
                return false;
            }
        }
        
        // Kiểm tra excluded pages
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($this->excludedPages as $page) {
            if (stripos($uri, $page) !== false) {
                return false;
            }
        }
        
        return $this->enabled;
    }
    
    /**
     * Bắt đầu cache page
     */
    public function start($ttl = 180) {
        if (!$this->shouldCache()) {
            return false;
        }
        
        $this->cacheKey = $this->generateKey();
        
        // Kiểm tra cache
        $cached = $this->cache->get($this->cacheKey);
        if ($cached !== null) {
            // Thêm header cho biết đây là cached response
            header('X-Page-Cache: HIT');
            header('X-Cache-Time: ' . round((microtime(true) - $this->startTime) * 1000, 2) . 'ms');
            echo $cached;
            exit;
        }
        
        // Bắt đầu output buffering
        header('X-Page-Cache: MISS');
        ob_start();
        
        return true;
    }
    
    /**
     * Kết thúc và lưu cache
     */
    public function end($ttl = 180) {
        if (!$this->shouldCache() || !$this->cacheKey) {
            return;
        }
        
        $content = ob_get_contents();
        
        // Chỉ cache nếu response thành công
        if (http_response_code() === 200 && strlen($content) > 100) {
            // Thêm comment HTML về cache time
            $cacheInfo = "\n<!-- Cached: " . date('Y-m-d H:i:s') . " | TTL: {$ttl}s -->";
            $content .= $cacheInfo;
            
            $this->cache->set($this->cacheKey, $content, $ttl);
        }
        
        ob_end_flush();
    }
    
    /**
     * Generate cache key từ URL và user state
     */
    private function generateKey() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $isLoggedIn = isset($_SESSION['USER']) ? '1' : '0';
        
        return 'page_' . md5($uri . '_' . $isLoggedIn);
    }
    
    /**
     * Xóa cache của một trang cụ thể
     */
    public function invalidatePage($uri) {
        $key = 'page_' . md5($uri . '_0');
        $this->cache->delete($key);
        $key = 'page_' . md5($uri . '_1');
        $this->cache->delete($key);
    }
    
    /**
     * Xóa tất cả page cache
     */
    public function invalidateAll() {
        $this->cache->clear();
    }
    
    /**
     * Disable cache
     */
    public function disable() {
        $this->enabled = false;
    }
    
    /**
     * Enable cache
     */
    public function enable() {
        $this->enabled = true;
    }
}

// Helper functions
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
