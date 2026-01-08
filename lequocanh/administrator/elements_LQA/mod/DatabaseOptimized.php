<?php

class DatabaseOptimized
{
    private static $instance = null;
    private $conn = null;
    private $queryLog = [];
    private $queryCount = 0;

    private function __construct()
    {
        $this->ensureEnvLoaded();
        $this->connect();
    }

    private function connect()
    {
        $servername = $_ENV['DB_HOST'] ?? 'mysql';
        $dbname = $_ENV['DB_DATABASE'] ?? 'sales_management';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? 'pw';
        $port = $_ENV['DB_PORT'] ?? 3306;

        try {
            $dsn = "mysql:host={$servername};port={$port};dbname={$dbname};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->conn = new PDO($dsn, $username, $password, $options);
            
            $this->conn->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Không thể kết nối database. Vui lòng kiểm tra cấu hình.");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseOptimized();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function query($sql, $params = [], $useCache = false, $cacheTTL = 300)
    {
        $this->queryCount++;
        
        if ($_ENV['APP_DEBUG'] ?? false) {
            $startTime = microtime(true);
        }

        if ($useCache && class_exists('QueryCache')) {
            require_once __DIR__ . '/../../../cache/QueryCache.php';
            $cache = new QueryCache();
            $result = $cache->query($this->conn, $sql, $params, $cacheTTL);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($_ENV['APP_DEBUG'] ?? false) {
            $executionTime = microtime(true) - $startTime;
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => $executionTime,
                'cached' => $useCache
            ];
            
            if ($executionTime > 0.1) {
                error_log("SLOW QUERY ({$executionTime}s): $sql");
            }
        }

        return $result;
    }

    public function queryOne($sql, $params = [], $useCache = false, $cacheTTL = 300)
    {
        if ($useCache && class_exists('QueryCache')) {
            require_once __DIR__ . '/../../../cache/QueryCache.php';
            $cache = new QueryCache();
            return $cache->queryOne($this->conn, $sql, $params, $cacheTTL);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function execute($sql, $params = [])
    {
        $this->queryCount++;
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        return $this->conn->rollBack();
    }

    public function getQueryCount()
    {
        return $this->queryCount;
    }

    public function getQueryLog()
    {
        return $this->queryLog;
    }

    public function getSlowQueries($threshold = 0.1)
    {
        return array_filter($this->queryLog, function($log) use ($threshold) {
            return $log['time'] > $threshold;
        });
    }

    private function ensureEnvLoaded()
    {
        if (isset($_ENV['DB_HOST'])) {
            return;
        }

        $envPath = dirname(dirname(dirname(dirname(__DIR__)))) . '/.env';

        if (!file_exists($envPath)) {
            $altPaths = [
                '/var/www/html/.env',
                __DIR__ . '/../../../.env',
                $_SERVER['DOCUMENT_ROOT'] . '/.env'
            ];

            foreach ($altPaths as $alt) {
                if (file_exists($alt)) {
                    $envPath = $alt;
                    break;
                }
            }
        }

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (preg_match('/^(["\'])(.+)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}
