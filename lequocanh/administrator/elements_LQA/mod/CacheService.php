<?php
/**
 * Cache Service
 * 
 * Simple file-based cache để tối ưu API calls
 */

class CacheService {
    
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../../../../cache';
        
        // Create cache directory if not exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($filename), true);
        
        if (!$data) {
            return null;
        }
        
        // Check if expired
        if (isset($data['expires_at']) && time() > $data['expires_at']) {
            $this->delete($key);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    /**
     * Set cache data
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($filename, json_encode($data)) !== false;
    }
    
    /**
     * Delete cache
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Remember - Get from cache or execute callback
     */
    public function remember($key, $callback, $ttl = null) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get cache filename
     */
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Get cache stats
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $validCount = 0;
        $expiredCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires_at'])) {
                if (time() > $data['expires_at']) {
                    $expiredCount++;
                } else {
                    $validCount++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_count' => $validCount,
            'expired_count' => $expiredCount,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize)
        ];
    }
    
    /**
     * Format bytes
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Clean expired cache
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}
