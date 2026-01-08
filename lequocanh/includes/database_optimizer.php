<?php

class DatabaseOptimizer
{
    private static $instance = null;
    private $db;
    private $queryLog = [];
    private $slowQueryThreshold = 100;
    
    private function __construct()
    {
        require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
        $this->db = Database::getInstance()->getConnection();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function analyzeQuery($sql)
    {
        $explainSql = "EXPLAIN " . $sql;
        $stmt = $this->db->query($explainSql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSlowQueries()
    {
        return array_filter($this->queryLog, function($query) {
            return $query['time_ms'] > $this->slowQueryThreshold;
        });
    }
    
    public function logQuery($sql, $timeMs)
    {
        $this->queryLog[] = [
            'sql' => $sql,
            'time_ms' => $timeMs,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    public function getTableStats()
    {
        $sql = "SELECT 
                    TABLE_NAME as table_name,
                    TABLE_ROWS as row_count,
                    ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_size_mb,
                    ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_size_mb,
                    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as total_size_mb
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getIndexStats()
    {
        $sql = "SELECT 
                    TABLE_NAME as table_name,
                    INDEX_NAME as index_name,
                    COLUMN_NAME as column_name,
                    NON_UNIQUE as non_unique,
                    CARDINALITY as cardinality
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY TABLE_NAME, INDEX_NAME";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMissingIndexSuggestions()
    {
        $suggestions = [];
        
        $commonPatterns = [
            'hanghoa' => ['idloaihang', 'idThuongHieu', 'trang_thai', 'giathamkhao'],
            'don_hang' => ['ma_nguoi_dung', 'trang_thai', 'ngay_dat_hang'],
            'product_reviews' => ['product_id', 'status', 'rating'],
            'user' => ['username', 'email'],
            'gio_hang' => ['ma_nguoi_dung', 'ma_hang_hoa'],
            'chi_tiet_don_hang' => ['ma_don_hang', 'ma_hang_hoa']
        ];
        
        $existingIndexes = $this->getIndexStats();
        $indexedColumns = [];
        
        foreach ($existingIndexes as $idx) {
            $key = $idx['table_name'] . '.' . $idx['column_name'];
            $indexedColumns[$key] = true;
        }
        
        foreach ($commonPatterns as $table => $columns) {
            foreach ($columns as $column) {
                $key = $table . '.' . $column;
                if (!isset($indexedColumns[$key])) {
                    $suggestions[] = [
                        'table' => $table,
                        'column' => $column,
                        'sql' => "ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_{$column}` (`{$column}`);"
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    public function optimizeTables()
    {
        $tables = ['hanghoa', 'don_hang', 'product_reviews', 'user', 'gio_hang', 'chi_tiet_don_hang', 'loaihang'];
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $this->db->exec("ANALYZE TABLE `{$table}`");
                $results[$table] = 'optimized';
            } catch (PDOException $e) {
                $results[$table] = 'error: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    public function getConnectionPoolStats()
    {
        $sql = "SHOW STATUS LIKE 'Threads%'";
        $stmt = $this->db->query($sql);
        $threads = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $sql = "SHOW STATUS LIKE 'Connections'";
        $stmt = $this->db->query($sql);
        $connections = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'threads_connected' => $threads['Threads_connected'] ?? 0,
            'threads_running' => $threads['Threads_running'] ?? 0,
            'threads_cached' => $threads['Threads_cached'] ?? 0,
            'total_connections' => $connections['Value'] ?? 0
        ];
    }
    
    public function getQueryCacheStats()
    {
        try {
            $sql = "SHOW STATUS LIKE 'Qcache%'";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return ['error' => 'Query cache not available'];
        }
    }
    
    public function generateOptimizationReport()
    {
        return [
            'table_stats' => $this->getTableStats(),
            'index_stats' => $this->getIndexStats(),
            'missing_indexes' => $this->getMissingIndexSuggestions(),
            'connection_stats' => $this->getConnectionPoolStats(),
            'slow_queries' => $this->getSlowQueries()
        ];
    }
}

function db_optimizer() {
    return DatabaseOptimizer::getInstance();
}
