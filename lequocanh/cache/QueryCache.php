<?php

require_once __DIR__ . '/CacheManager.php';

class QueryCache {
    private static $instance = null;
    private $cache;
    private $stats = ['hits' => 0, 'misses' => 0];
    
    const TTL_PRODUCTS = 300;
    const TTL_CATEGORIES = 600;
    const TTL_HOMEPAGE = 180;
    const TTL_STATIC = 3600;
    
    private function __construct() {
        $this->cache = CacheManager::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($pdo, $sql, $params = [], $ttl = 300) {
        $cacheKey = $this->generateKey($sql, $params);
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->stats['hits']++;
            return $cached;
        }
        
        $this->stats['misses']++;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $this->cache->set($cacheKey, $result, $ttl);
        
        return $result;
    }
    
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
    
    public function invalidate($pattern) {
        $cacheDir = dirname(__FILE__);
        $this->cache->clear();
    }

    public function invalidateProducts() {
        $this->cache->clear();
    }
    
    private function generateKey($sql, $params) {
        return 'query_' . md5($sql . serialize($params));
    }
    
    public function getStats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'hit_rate' => $total > 0 ? round($this->stats['hits'] / $total * 100, 2) : 0
        ];
    }
}

if (!function_exists('cached_query')) {
    function cached_query($pdo, $sql, $params = [], $ttl = 300) {
        return QueryCache::getInstance()->query($pdo, $sql, $params, $ttl);
    }
}
