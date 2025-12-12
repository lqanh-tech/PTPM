<?php
/**
 * Simple File-based Cache Manager
 * Giảm tải database queries và tăng tốc response
 */

class CacheManager {
    private static $instance = null;
    private $cacheDir;
    private $defaultTTL = 300; // 5 phút
    
    private function __construct() {
        $this->cacheDir = __DIR__;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Lấy cache key từ tên
     */
    private function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    /**
     * Lấy dữ liệu từ cache
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Kiểm tra hết hạn
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Lưu dữ liệu vào cache
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Xóa cache theo key
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    /**
     * Xóa tất cả cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Cache với callback - tự động lấy/lưu
     */
    public function remember($key, $ttl, callable $callback) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}

// Helper function
if (!function_exists('cache')) {
    function cache($key = null, $value = null, $ttl = 300) {
        $cache = CacheManager::getInstance();
        
        if ($key === null) {
            return $cache;
        }
        
        if ($value === null) {
            return $cache->get($key);
        }
        
        return $cache->set($key, $value, $ttl);
    }
}
