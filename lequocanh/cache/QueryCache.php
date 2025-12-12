<?php
/**
 * Query Cache - Cache kết quả database queries
 * Giảm tải database và tăng tốc response
 */

require_once __DIR__ . '/CacheManager.php';

class QueryCache {
    private static $instance = null;
    private $cache;
    private $stats = ['hits' => 0, 'misses' => 0];
    
    // TTL mặc định cho các loại query khác nhau
    const TTL_PRODUCTS = 300;      // 5 phút cho sản phẩm
    const TTL_CATEGORIES = 600;    // 10 phút cho danh mục
    const TTL_HOMEPAGE = 180;      // 3 phút cho trang chủ
    const TTL_STATIC = 3600;       // 1 giờ cho nội dung tĩnh
    
    private function __construct() {
        $this->cache = CacheManager::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Cache query với key tự động từ SQL
     */
    public function query($pdo, $sql, $params = [], $ttl = 300) {
        $cacheKey = $this->generateKey($sql, $params);
        
        // Kiểm tra cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->stats['hits']++;
            return $cached;
        }
        
        // Thực hiện query
        $this->stats['misses']++;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        // Lưu cache
        $this->cache->set($cacheKey, $result, $ttl);
        
        return $result;
    }
    
    /**
     * Cache single row query
     */
    public function queryOne($pdo, $sql, $params = [], $ttl = 300) {
        $cacheKey = $this->generateKey($sql, $params) . '_one';
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        $this->cache->set($cacheKey, $result, $ttl);
        
        return $result;
    }
    
    /**
     * Invalidate cache theo pattern
     */
    public function invalidate($pattern) {
        // Xóa tất cả cache files matching pattern
        $cacheDir = dirname(__FILE__);
        $files = glob($cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (strpos(file_get_contents($file), $pattern) !== false) {
                unlink($file);
            }
        }
    }
    
    /**
     * Xóa cache sản phẩm
     */
    public function invalidateProducts() {
        $this->cache->clear();
    }
    
    /**
     * Generate cache key từ SQL và params
     */
    private function generateKey($sql, $params) {
        return 'query_' . md5($sql . serialize($params));
    }
    
    /**
     * Lấy thống kê cache
     */
    public function getStats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'hit_rate' => $total > 0 ? round($this->stats['hits'] / $total * 100, 2) : 0
        ];
    }
}

// Helper function
if (!function_exists('cached_query')) {
    function cached_query($pdo, $sql, $params = [], $ttl = 300) {
        return QueryCache::getInstance()->query($pdo, $sql, $params, $ttl);
    }
}
