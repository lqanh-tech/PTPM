<?php
require_once 'queryCache.php';

class CacheManager {

    private $queryCache;
    private $fileCacheDir;
    private $templateCacheDir;
    private $ttl;

    public function __construct($ttl = 3600) {
        $this->ttl = $ttl;
        
        $this->queryCache = new QueryCache('cache/query/', $ttl);

        $this->fileCacheDir = 'cache/files/';
        $this->templateCacheDir = 'cache/templates/';

        if (!is_dir($this->fileCacheDir)) {
            mkdir($this->fileCacheDir, 0755, true);
        }
        if (!is_dir($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, 0755, true);
        }
    }

    public function getQueryCache() {
        return $this->queryCache;
    }

    public function setData($key, $data, $ttl = null) {
        $cacheFile = $this->fileCacheDir . md5($key);
        $lifetime = $ttl ?? $this->ttl;
        $content = serialize(['time' => time(), 'ttl' => $lifetime, 'data' => $data]);
        file_put_contents($cacheFile, $content);
    }

    public function getData($key) {
        $cacheFile = $this->fileCacheDir . md5($key);

        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = unserialize($content);

            if (time() - $data['time'] < $data['ttl']) {
                return $data['data'];
            }
        }
        return false;
    }
    
    public function setTemplateCache($templatePath, $output) {
        $cacheFile = $this->templateCacheDir . md5($templatePath);
        file_put_contents($cacheFile, $output);
    }

    public function getTemplateCache($templatePath) {
        $cacheFile = $this->templateCacheDir . md5($templatePath);

        if (file_exists($cacheFile) && filemtime($cacheFile) > filemtime($templatePath)) {
            return file_get_contents($cacheFile);
        }

        return false;
    }

    public function clearAllCaches() {
        $this->queryCache->clearAll();
        $this->clearDirectory($this->fileCacheDir);
        $this->clearDirectory($this->templateCacheDir);
    }

    private function clearDirectory($dir) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
?>