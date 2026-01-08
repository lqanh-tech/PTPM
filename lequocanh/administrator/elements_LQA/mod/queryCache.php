<?php

class QueryCache {

    private $cacheDir;
    private $ttl;

    public function __construct($cacheDir = 'cache/', $ttl = 3600) {
        $this->cacheDir = $cacheDir;
        $this->ttl = $ttl;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    private function generateKey($query, $params) {
        return md5($query . serialize($params));
    }

    public function get($query, $params = []) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = unserialize($content);

            if (time() - $data['time'] < $this->ttl) {
                return $data['data'];
            }
        }

        return false;
    }

    public function set($query, $params = [], $data) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        $content = serialize(['time' => time(), 'data' => $data]);
        file_put_contents($cacheFile, $content);
    }

    public function delete($query, $params = []) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function clearAll() {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
?>