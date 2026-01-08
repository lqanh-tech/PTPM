<?php

class PageCache
{
    private static $instance = null;
    private $cacheDir;
    private $enabled = true;
    private $ttl = 300;
    private $excludePatterns = [];
    private $startTime;
    
    private function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/pages/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        $this->startTime = microtime(true);
        
        $this->excludePatterns = [
            '/administrator/',
            '/checkout/',
            '/cart/',
            '/login/',
            '/logout/',
            '/register/',
            '/profile/',
            '/order/'
        ];
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setTTL($seconds)
    {
        $this->ttl = $seconds;
        return $this;
    }
    
    public function disable()
    {
        $this->enabled = false;
        return $this;
    }
    
    public function enable()
    {
        $this->enabled = true;
        return $this;
    }
    
    public function addExcludePattern($pattern)
    {
        $this->excludePatterns[] = $pattern;
        return $this;
    }
    
    private function shouldCache()
    {
        if (!$this->enabled) {
            return false;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }
        
        if (isset($_SESSION['USER']) || isset($_SESSION['ADMIN'])) {
            return false;
        }
        
        $uri = $_SERVER['REQUEST_URI'];
        foreach ($this->excludePatterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return false;
            }
        }
        
        if (!empty($_POST)) {
            return false;
        }
        
        return true;
    }
    
    private function getCacheKey()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $query = $_SERVER['QUERY_STRING'] ?? '';
        return md5($uri . '|' . $query);
    }
    
    private function getCachePath()
    {
        return $this->cacheDir . $this->getCacheKey() . '.html';
    }
    
    public function start()
    {
        if (!$this->shouldCache()) {
            return false;
        }
        
        $cachePath = $this->getCachePath();
        
        if (file_exists($cachePath) && filemtime($cachePath) > time() - $this->ttl) {
            $content = file_get_contents($cachePath);
            
            $genTime = round((microtime(true) - $this->startTime) * 1000, 2);
            $content = str_replace('</body>', "<!-- Cached page served in {$genTime}ms -->\n</body>", $content);
            
            echo $content;
            exit;
        }
        
        ob_start();
        return true;
    }
    
    public function end()
    {
        if (!$this->shouldCache()) {
            return;
        }
        
        $content = ob_get_contents();
        
        if (!empty($content) && strpos($content, '<!DOCTYPE') !== false) {
            $genTime = round((microtime(true) - $this->startTime) * 1000, 2);
            $content = str_replace('</body>', "<!-- Page generated in {$genTime}ms, cached at " . date('Y-m-d H:i:s') . " -->\n</body>", $content);
            
            file_put_contents($this->getCachePath(), $content);
        }
    }
    
    public function clear()
    {
        $files = glob($this->cacheDir . '*.html');
        foreach ($files as $file) {
            unlink($file);
        }
        return count($files);
    }
    
    public function clearPattern($pattern)
    {
        $count = 0;
        $files = glob($this->cacheDir . '*.html');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, $pattern) !== false) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }
    
    public function getStats()
    {
        $files = glob($this->cacheDir . '*.html');
        $totalSize = 0;
        $validCount = 0;
        $expiredCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            if (filemtime($file) > time() - $this->ttl) {
                $validCount++;
            } else {
                $expiredCount++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validCount,
            'expired_files' => $expiredCount,
            'total_size_kb' => round($totalSize / 1024, 2),
            'cache_dir' => $this->cacheDir
        ];
    }
}

function page_cache() {
    return PageCache::getInstance();
}

function start_page_cache($ttl = 300) {
    return PageCache::getInstance()->setTTL($ttl)->start();
}

function end_page_cache() {
    PageCache::getInstance()->end();
}
