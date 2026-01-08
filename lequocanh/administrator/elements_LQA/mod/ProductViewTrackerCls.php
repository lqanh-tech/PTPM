<?php

require_once __DIR__ . '/database.php';

class ProductViewTracker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function trackView($idhanghoa) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionKey = 'viewed_product_' . $idhanghoa;
        
        if (isset($_SESSION[$sessionKey])) {
            $lastView = $_SESSION[$sessionKey];
            if (time() - $lastView < 1800) {
                return false;
            }
        }
        
        $sql = "UPDATE hanghoa SET view_count = view_count + 1 WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$idhanghoa]);
        
        $_SESSION[$sessionKey] = time();
        
        $this->logView($idhanghoa);
        
        return $result;
    }
    
    private function logView($idhanghoa) {
        try {

            $this->createViewLogTable();
            
            $sql = "INSERT INTO product_view_logs 
                    (idhanghoa, ip_address, user_agent, referer, viewed_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $idhanghoa,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $_SERVER['HTTP_REFERER'] ?? ''
            ]);
        } catch (Exception $e) {

            error_log("View log error: " . $e->getMessage());
        }
    }
    
    private function createViewLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS product_view_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            idhanghoa INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            referer TEXT,
            viewed_at DATETIME,
            INDEX idx_product (idhanghoa),
            INDEX idx_date (viewed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->exec($sql);
    }
    
    public function getViewCount($idhanghoa) {
        $sql = "SELECT view_count FROM hanghoa WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idhanghoa]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->view_count : 0;
    }
    
    public function getViewStats($idhanghoa, $days = 30) {
        try {
            $sql = "SELECT 
                    DATE(viewed_at) as date,
                    COUNT(*) as views
                    FROM product_view_logs
                    WHERE idhanghoa = ?
                    AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(viewed_at)
                    ORDER BY date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa, $days]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getMostViewedProducts($limit = 10, $days = null) {
        if ($days) {

            try {
                $sql = "SELECT 
                        h.idhanghoa,
                        h.tenhanghoa,
                        h.giathamkhao,
                        COUNT(l.id) as view_count
                        FROM hanghoa h
                        INNER JOIN product_view_logs l ON h.idhanghoa = l.idhanghoa
                        WHERE l.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                        GROUP BY h.idhanghoa
                        ORDER BY view_count DESC
                        LIMIT ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$days, $limit]);
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            } catch (Exception $e) {

            }
        }
        
        $sql = "SELECT 
                idhanghoa,
                tenhanghoa,
                giathamkhao,
                view_count
                FROM hanghoa
                WHERE view_count > 0
                ORDER BY view_count DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    public function resetViewCount($idhanghoa = null) {
        if ($idhanghoa) {
            $sql = "UPDATE hanghoa SET view_count = 0 WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idhanghoa]);
        } else {
            $sql = "UPDATE hanghoa SET view_count = 0";
            return $this->db->exec($sql);
        }
    }
}
