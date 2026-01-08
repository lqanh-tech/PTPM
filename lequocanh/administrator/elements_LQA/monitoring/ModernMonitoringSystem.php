<?php

class ModernMonitoringSystem {
    private static $instance = null;
    private $metrics = [];
    private $startTime;
    private $db;
    
    private function __construct() {
        $this->startTime = microtime(true);
        $this->db = Database::getInstance()->getConnection();
        $this->initializeMetricsTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeMetricsTables() {
        $sql = "CREATE TABLE IF NOT EXISTS system_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            metric_name VARCHAR(100) NOT NULL,
            metric_value DECIMAL(15,6) NOT NULL,
            metric_type ENUM('counter', 'gauge', 'histogram') DEFAULT 'gauge',
            labels JSON,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_metric_name (metric_name),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB";
        
        $this->db->exec($sql);
    }
    
    public function recordMetric($name, $value, $type = 'gauge', $labels = []) {
        $this->metrics[$name] = [
            'value' => $value,
            'type' => $type,
            'labels' => $labels,
            'timestamp' => microtime(true)
        ];
        
        $sql = "INSERT INTO system_metrics (metric_name, metric_value, metric_type, labels) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name, $value, $type, json_encode($labels)]);
    }
    
    public function incrementCounter($name, $labels = []) {
        $current = $this->metrics[$name]['value'] ?? 0;
        $this->recordMetric($name, $current + 1, 'counter', $labels);
    }
    
    public function recordTiming($name, $startTime, $labels = []) {
        $duration = microtime(true) - $startTime;
        $this->recordMetric($name . '_duration_seconds', $duration, 'histogram', $labels);
    }
    
    public function recordMemoryUsage($labels = []) {
        $this->recordMetric('memory_usage_bytes', memory_get_usage(), 'gauge', $labels);
        $this->recordMetric('memory_peak_bytes', memory_get_peak_usage(), 'gauge', $labels);
    }
    
    public function recordDatabaseQuery($query, $duration, $rows = 0) {
        $this->incrementCounter('database_queries_total', [
            'query_type' => $this->getQueryType($query)
        ]);
        
        $this->recordMetric('database_query_duration_seconds', $duration, 'histogram', [
            'query_type' => $this->getQueryType($query)
        ]);
        
        if ($duration > 1.0) {
            $this->incrementCounter('database_slow_queries_total');
        }
    }
    
    private function getQueryType($query) {
        $query = trim(strtoupper($query));
        if (strpos($query, 'SELECT') === 0) return 'SELECT';
        if (strpos($query, 'INSERT') === 0) return 'INSERT';
        if (strpos($query, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($query, 'DELETE') === 0) return 'DELETE';
        return 'OTHER';
    }
    
    public function recordHttpRequest($method, $path, $statusCode, $duration) {
        $this->incrementCounter('http_requests_total', [
            'method' => $method,
            'status_code' => $statusCode,
            'path' => $this->normalizePath($path)
        ]);
        
        $this->recordMetric('http_request_duration_seconds', $duration, 'histogram', [
            'method' => $method,
            'path' => $this->normalizePath($path)
        ]);
    }
    
    private function normalizePath($path) {

        return preg_replace('/\/\d+/', '/{id}', $path);
    }
    
    public function getMetrics() {
        return $this->metrics;
    }
    
    public function exportPrometheusMetrics() {
        $output = [];
        
        foreach ($this->metrics as $name => $data) {
            $labels = '';
            if (!empty($data['labels'])) {
                $labelPairs = [];
                foreach ($data['labels'] as $key => $value) {
                    $labelPairs[] = $key . '="' . addslashes($value) . '"';
                }
                $labels = '{' . implode(',', $labelPairs) . '}';
            }
            
            $output[] = "# TYPE $name {$data['type']}";
            $output[] = "$name$labels {$data['value']}";
        }
        
        return implode("\n", $output);
    }
    
    public function getSystemHealth() {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'uptime' => microtime(true) - $this->startTime,
            'memory' => [
                'usage' => memory_get_usage(),
                'peak' => memory_get_peak_usage(),
                'limit' => ini_get('memory_limit')
            ],
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'disk' => $this->checkDiskHealth()
        ];
        
        if (!$health['database']['healthy'] || !$health['disk']['healthy']) {
            $health['status'] = 'unhealthy';
        } elseif ($health['memory']['usage'] > $health['memory']['peak'] * 0.9) {
            $health['status'] = 'degraded';
        }
        
        return $health;
    }
    
    private function checkDatabaseHealth() {
        try {
            $stmt = $this->db->query("SELECT 1");
            return [
                'healthy' => true,
                'response_time' => 0.001
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkCacheHealth() {

        $cacheDir = __DIR__ . '/../../../cache';
        return [
            'healthy' => is_dir($cacheDir) && is_writable($cacheDir),
            'type' => extension_loaded('redis') ? 'redis' : 'file'
        ];
    }
    
    private function checkDiskHealth() {
        $free = disk_free_space('.');
        $total = disk_total_space('.');
        $usage = ($total - $free) / $total;
        
        return [
            'healthy' => $usage < 0.9,
            'usage_percent' => round($usage * 100, 2),
            'free_bytes' => $free,
            'total_bytes' => $total
        ];
    }
}
