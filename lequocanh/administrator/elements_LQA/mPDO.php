<?php

$possible_paths = [
    __DIR__ . '/mod/database.php',
    __DIR__ . '/../elements_LQA/mod/database.php',
    dirname(__DIR__) . '/elements_LQA/mod/database.php',
    dirname(dirname(__DIR__)) . '/administrator/elements_LQA/mod/database.php'
];

$database_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $database_loaded = true;
        break;
    }
}

if (!$database_loaded) {
    throw new Exception('Không thể tìm thấy file database.php');
}

class mPDO 
{
    private $conn;
    
    public function __construct() 
    {
        try {
            $db = Database::getInstance();
            $this->conn = $db->getConnection();
        } catch (Exception $e) {
            throw new Exception('Không thể kết nối database: ' . $e->getMessage());
        }
    }
    
    public function execute($sql, $params = []) 
    {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('mPDO Execute Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }
    
    public function executeS($sql, $params = [], $fetchAll = false) 
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            if ($fetchAll) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log('mPDO ExecuteS Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
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
    
    public function rollback() 
    {
        return $this->conn->rollback();
    }
    
    public function getConnection() 
    {
        return $this->conn;
    }
    
    public function tableExists($tableName) 
    {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $result = $this->executeS($sql, [$tableName]);
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function describeTable($tableName) 
    {
        try {
            $sql = "DESCRIBE `$tableName`";
            return $this->executeS($sql, [], true);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function countRows($tableName, $condition = '', $params = []) 
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM `$tableName`";
            if ($condition) {
                $sql .= " WHERE $condition";
            }
            
            $result = $this->executeS($sql, $params);
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function executeMultiple($sqlStatements) 
    {
        try {
            $this->beginTransaction();
            
            foreach ($sqlStatements as $sql) {
                if (is_array($sql)) {
                    $this->execute($sql['sql'], $sql['params'] ?? []);
                } else {
                    $this->execute($sql);
                }
            }
            
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            error_log('mPDO ExecuteMultiple Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function quote($string) 
    {
        return $this->conn->quote($string);
    }
    
    public function errorInfo() 
    {
        return $this->conn->errorInfo();
    }
}

?>
