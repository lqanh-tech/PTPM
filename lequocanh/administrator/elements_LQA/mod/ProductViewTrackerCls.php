<?php
/**
 * Product View Tracker
 * Theo dõi lượt xem sản phẩm
 */

require_once __DIR__ . '/database.php';

class ProductViewTracker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Tăng lượt xem sản phẩm
     * Sử dụng session để tránh đếm trùng trong cùng 1 phiên
     */
    public function trackView($idhanghoa) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Tạo key để track trong session
        $sessionKey = 'viewed_product_' . $idhanghoa;
        
        // Nếu đã xem trong phiên này (trong 30 phút), không đếm lại
        if (isset($_SESSION[$sessionKey])) {
            $lastView = $_SESSION[$sessionKey];
            if (time() - $lastView < 1800) { // 30 phút
                return false; // Không tăng view
            }
        }
        
        // Cập nhật lượt xem
        $sql = "UPDATE hanghoa SET view_count = view_count + 1 WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$idhanghoa]);
        
        // Lưu vào session
        $_SESSION[$sessionKey] = time();
        
        // Log vào bảng tracking (optional - để phân tích chi tiết)
        $this->logView($idhanghoa);
        
        return $result;
    }
    
    /**
     * Log chi tiết lượt xem (optional)
     * Để phân tích theo thời gian, IP, user agent
     */
    private function logView($idhanghoa) {
        try {
            // Tạo bảng nếu chưa có
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
            // Không throw error nếu log thất bại
            error_log("View log error: " . $e->getMessage());
        }
    }
    
    /**
     * Tạo bảng log nếu chưa có
     */
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
    
    /**
     * Lấy lượt xem của sản phẩm
     */
    public function getViewCount($idhanghoa) {
        $sql = "SELECT view_count FROM hanghoa WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idhanghoa]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->view_count : 0;
    }
    
    /**
     * Lấy thống kê lượt xem theo thời gian
     */
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
    
    /**
     * Lấy sản phẩm xem nhiều nhất
     */
    public function getMostViewedProducts($limit = 10, $days = null) {
        if ($days) {
            // Lấy từ log table (chính xác hơn)
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
                // Fallback to view_count column
            }
        }
        
        // Lấy từ cột view_count
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
    
    /**
     * Reset lượt xem (admin only)
     */
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
