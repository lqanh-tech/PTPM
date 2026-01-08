<?php

class ConnectionPool
{
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $connectionTimeout = 30;
    private $stats = [
        'created' => 0,
        'reused' => 0,
        'closed' => 0
    ];
    
    private function __construct() {}
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection($config = null)
    {
        if ($config === null) {
            $config = $this->getDefaultConfig();
        }
        
        $key = md5(serialize($config));
        
        if (isset($this->connections[$key])) {
            $conn = $this->connections[$key];
            if ($this->isConnectionAlive($conn['pdo'])) {
                $this->connections[$key]['last_used'] = time();
                $this->stats['reused']++;
                return $conn['pdo'];
            }
            unset($this->connections[$key]);
        }
        
        $this->cleanupOldConnections();
        
        if (count($this->connections) >= $this->maxConnections) {
            $this->closeOldestConnection();
        }
        
        $pdo = $this->createConnection($config);
        $this->connections[$key] = [
            'pdo' => $pdo,
            'created' => time(),
            'last_used' => time()
        ];
        $this->stats['created']++;
        
        return $pdo;
    }
    
    private function createConnection($config)
    {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_TIMEOUT => $this->connectionTimeout
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
    
    private function isConnectionAlive($pdo)
    {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function cleanupOldConnections()
    {
        $now = time();
        foreach ($this->connections as $key => $conn) {
            if ($now - $conn['last_used'] > $this->connectionTimeout) {
                unset($this->connections[$key]);
                $this->stats['closed']++;
            }
        }
    }
    
    private function closeOldestConnection()
    {
        $oldest = null;
        $oldestKey = null;
        
        foreach ($this->connections as $key => $conn) {
            if ($oldest === null || $conn['last_used'] < $oldest) {
                $oldest = $conn['last_used'];
                $oldestKey = $key;
            }
        }
        
        if ($oldestKey !== null) {
            unset($this->connections[$oldestKey]);
            $this->stats['closed']++;
        }
    }
    
    private function getDefaultConfig()
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'sales_management',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? ''
        ];
    }
    
    public function getStats()
    {
        return array_merge($this->stats, [
            'active_connections' => count($this->connections),
            'max_connections' => $this->maxConnections
        ]);
    }
    
    public function closeAll()
    {
        $count = count($this->connections);
        $this->connections = [];
        $this->stats['closed'] += $count;
        return $count;
    }
}

function db_pool() {
    return ConnectionPool::getInstance();
}

function get_db_connection($config = null) {
    return ConnectionPool::getInstance()->getConnection($config);
}
