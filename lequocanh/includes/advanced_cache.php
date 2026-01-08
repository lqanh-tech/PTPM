<?php

class AdvancedCache
{
    private static $instance = null;
    private $memoryCache = [];
    private $cacheDir;
    private $stats = ['hits' => 0, 'misses' => 0, 'writes' => 0];
    
    private function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/advanced/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null)
    {
        if (isset($this->memoryCache[$key])) {
            $this->stats['hits']++;
            return $this->memoryCache[$key]['value'];
        }
        
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                $this->memoryCache[$key] = $data;
                $this->stats['hits']++;
                return $data['value'];
            }
            unlink($file);
        }
        
        $this->stats['misses']++;
        return $default;
    }
    
    public function set($key, $value, $ttl = 300)
    {
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $this->memoryCache[$key] = $data;
        file_put_contents($this->getFilePath($key), serialize($data));
        $this->stats['writes']++;
        
        return true;
    }
    
    public function remember($key, $ttl, $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    public function tags($tags)
    {
        return new CacheTagManager($this, (array)$tags);
    }
    
    public function invalidateTag($tag)
    {
        $tagFile = $this->cacheDir . 'tags/' . md5($tag) . '.tag';
        if (file_exists($tagFile)) {
            $keys = unserialize(file_get_contents($tagFile));
            foreach ($keys as $key) {
                $this->delete($key);
            }
            unlink($tagFile);
        }
    }
    
    public function delete($key)
    {
        unset($this->memoryCache[$key]);
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    public function clear()
    {
        $this->memoryCache = [];
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return count($files);
    }
    
    public function getStats()
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'writes' => $this->stats['writes'],
            'hit_rate' => $total > 0 ? round($this->stats['hits'] / $total * 100, 2) : 0,
            'memory_items' => count($this->memoryCache)
        ];
    }
    
    private function getFilePath($key)
    {
        return $this->cacheDir . md5($key) . '.cache';
    }
}

class CacheTagManager
{
    private $cache;
    private $tags;
    
    public function __construct($cache, $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }
    
    public function get($key, $default = null)
    {
        return $this->cache->get($this->taggedKey($key), $default);
    }
    
    public function set($key, $value, $ttl = 300)
    {
        $taggedKey = $this->taggedKey($key);
        $this->cache->set($taggedKey, $value, $ttl);
        $this->registerKeyWithTags($taggedKey);
        return true;
    }
    
    public function remember($key, $ttl, $callback)
    {
        $taggedKey = $this->taggedKey($key);
        return $this->cache->remember($taggedKey, $ttl, function() use ($callback, $taggedKey) {
            $this->registerKeyWithTags($taggedKey);
            return $callback();
        });
    }
    
    private function taggedKey($key)
    {
        return implode(':', $this->tags) . ':' . $key;
    }
    
    private function registerKeyWithTags($key)
    {
        $cacheDir = dirname(__FILE__) . '/../cache/advanced/tags/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        foreach ($this->tags as $tag) {
            $tagFile = $cacheDir . md5($tag) . '.tag';
            $keys = file_exists($tagFile) ? unserialize(file_get_contents($tagFile)) : [];
            if (!in_array($key, $keys)) {
                $keys[] = $key;
                file_put_contents($tagFile, serialize($keys));
            }
        }
    }
}

function cache() {
    return AdvancedCache::getInstance();
}

function cache_remember($key, $ttl, $callback) {
    return AdvancedCache::getInstance()->remember($key, $ttl, $callback);
}
