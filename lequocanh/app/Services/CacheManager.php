<?php
declare(strict_types=1);

namespace App\Services;

class CacheManager
{
    private static ?CacheManager $instance = null;
    private $redis = null;
    private bool $available = false;
    private int $defaultTTL = 300; // 5 minutes
    
    private function __construct()
    {
        try {
            if (class_exists('Redis')) {
                $this->redis = new \Redis();
                $this->redis->connect('redis', 6379, 1);
                $this->available = true;
            }
        } catch (\Exception $e) {
            $this->available = false;
            Logger::warning('CacheManager: Redis not available', ['error' => $e->getMessage()]);
        }
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached value or execute callback
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get value from cache
     */
    public function get(string $key)
    {
        if (!$this->available) {
            return null;
        }
        
        try {
            $data = $this->redis->get($key);
            return $data ? unserialize($data) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Set value in cache
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        if (!$this->available) {
            return false;
        }
        
        try {
            $ttl = $ttl > 0 ? $ttl : $this->defaultTTL;
            return $this->redis->setex($key, $ttl, serialize($value));
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        if (!$this->available) {
            return false;
        }
        
        try {
            return $this->redis->del($key) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        if (!$this->available) {
            return false;
        }
        
        try {
            return $this->redis->flushDB();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete cache by pattern
     */
    public function deletePattern(string $pattern): int
    {
        if (!$this->available) {
            return 0;
        }
        
        try {
            $keys = $this->redis->keys($pattern);
            return !empty($keys) ? $this->redis->del($keys) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Check if Redis is available
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }
}