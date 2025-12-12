<?php
/**
 * Class quản lý mã Coupon (Giảm giá)
 * 
 * Chức năng:
 * - Kiểm tra mã coupon hợp lệ
 * - Tính toán số tiền giảm
 * - Áp dụng coupon vào đơn hàng
 * - Quản lý CRUD coupon
 */

require_once __DIR__ . '/database.php';

class Coupon {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->ensureTablesExist();
    }
    
    /**
     * Đảm bảo các bảng coupon tồn tại
     */
    private function ensureTablesExist() {
        try {
            // Kiểm tra bảng coupons
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'coupons'");
            if ($checkTable->rowCount() == 0) {
                $this->createCouponTables();
            }
            
            // Kiểm tra và thêm cột coupon vào don_hang
            $this->ensureDonHangColumns();
            
        } catch (PDOException $e) {
            error_log("Coupon ensureTablesExist error: " . $e->getMessage());
        }
    }
    
    /**
     * Tạo bảng coupon
     */
    private function createCouponTables() {
        $sql = "CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(15,2) NOT NULL,
            max_discount DECIMAL(15,2) DEFAULT NULL,
            min_order_value DECIMAL(15,2) DEFAULT 0,
            usage_limit INT DEFAULT NULL,
            usage_count INT DEFAULT 0,
            usage_per_user INT DEFAULT 1,
            start_date DATETIME DEFAULT NULL,
            end_date DATETIME DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by VARCHAR(50) DEFAULT NULL,
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->exec($sql);
        
        // Tạo bảng lịch sử sử dụng
        $sql2 = "CREATE TABLE IF NOT EXISTS coupon_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coupon_id INT NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            order_id INT NOT NULL,
            discount_amount DECIMAL(15,2) NOT NULL,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_coupon (coupon_id),
            INDEX idx_user (user_id),
            INDEX idx_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->exec($sql2);
    }
    
    /**
     * Đảm bảo bảng don_hang có các cột coupon
     */
    private function ensureDonHangColumns() {
        $columns = ['coupon_code', 'coupon_discount'];
        
        foreach ($columns as $column) {
            $check = $this->conn->query("SHOW COLUMNS FROM don_hang LIKE '$column'");
            if ($check->rowCount() == 0) {
                if ($column == 'coupon_code') {
                    $this->conn->exec("ALTER TABLE don_hang ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL");
                } else {
                    $this->conn->exec("ALTER TABLE don_hang ADD COLUMN coupon_discount DECIMAL(15,2) DEFAULT 0");
                }
            }
        }
    }
    
    /**
     * Kiểm tra mã coupon có hợp lệ không
     * 
     * @param string $code Mã coupon
     * @param float $orderTotal Tổng tiền đơn hàng
     * @param string|null $userId Username người dùng
     * @return array ['valid' => bool, 'message' => string, 'coupon' => object|null, 'discount' => float]
     */
    public function validateCoupon($code, $orderTotal, $userId = null) {
        $code = strtoupper(trim($code));
        
        if (empty($code)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá', 'coupon' => null, 'discount' => 0];
        }
        
        // Lấy thông tin coupon
        $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại', 'coupon' => null, 'discount' => 0];
        }
        
        // Kiểm tra trạng thái hoạt động
        if (!$coupon->is_active) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã bị vô hiệu hóa', 'coupon' => null, 'discount' => 0];
        }
        
        // Kiểm tra thời gian hiệu lực
        $now = date('Y-m-d H:i:s');
        if ($coupon->start_date && $now < $coupon->start_date) {
            return ['valid' => false, 'message' => 'Mã giảm giá chưa có hiệu lực', 'coupon' => null, 'discount' => 0];
        }
        if ($coupon->end_date && $now > $coupon->end_date) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết hạn', 'coupon' => null, 'discount' => 0];
        }
        
        // Kiểm tra số lần sử dụng tổng
        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng', 'coupon' => null, 'discount' => 0];
        }
        
        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($orderTotal < $coupon->min_order_value) {
            return [
                'valid' => false, 
                'message' => 'Đơn hàng tối thiểu ' . number_format($coupon->min_order_value) . 'đ để áp dụng mã này', 
                'coupon' => null, 
                'discount' => 0
            ];
        }
        
        // Kiểm tra số lần sử dụng của user
        if ($userId && $coupon->usage_per_user) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
            $stmt->execute([$coupon->id, $userId]);
            $userUsageCount = $stmt->fetchColumn();
            
            if ($userUsageCount >= $coupon->usage_per_user) {
                return ['valid' => false, 'message' => 'Bạn đã sử dụng hết lượt cho mã này', 'coupon' => null, 'discount' => 0];
            }
        }
        
        // Tính số tiền giảm
        $discount = $this->calculateDiscount($coupon, $orderTotal);
        
        return [
            'valid' => true, 
            'message' => 'Áp dụng mã giảm giá thành công!', 
            'coupon' => $coupon, 
            'discount' => $discount
        ];
    }
    
    /**
     * Tính số tiền giảm
     */
    public function calculateDiscount($coupon, $orderTotal) {
        if ($coupon->discount_type == 'percent') {
            $discount = $orderTotal * ($coupon->discount_value / 100);
            // Áp dụng giới hạn giảm tối đa
            if ($coupon->max_discount && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }
        } else {
            // Giảm cố định
            $discount = $coupon->discount_value;
        }
        
        // Không giảm quá tổng đơn hàng
        if ($discount > $orderTotal) {
            $discount = $orderTotal;
        }
        
        return round($discount);
    }
    
    /**
     * Áp dụng coupon vào đơn hàng
     */
    public function applyCoupon($couponCode, $orderId, $userId, $discountAmount) {
        try {
            $this->conn->beginTransaction();
            
            // Lấy coupon
            $stmt = $this->conn->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([strtoupper($couponCode)]);
            $coupon = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$coupon) {
                throw new Exception("Coupon not found");
            }
            
            // Cập nhật đơn hàng
            $stmt = $this->conn->prepare("UPDATE don_hang SET coupon_code = ?, coupon_discount = ? WHERE id = ?");
            $stmt->execute([$couponCode, $discountAmount, $orderId]);
            
            // Ghi lịch sử sử dụng
            $stmt = $this->conn->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$coupon->id, $userId, $orderId, $discountAmount]);
            
            // Tăng số lần sử dụng
            $stmt = $this->conn->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
            $stmt->execute([$coupon->id]);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Apply coupon error: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== CRUD METHODS ====================
    
    /**
     * Lấy tất cả coupon
     */
    public function getAllCoupons($includeInactive = false) {
        $sql = "SELECT * FROM coupons";
        if (!$includeInactive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Lấy coupon theo ID
     */
    public function getCouponById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Lấy coupon theo code
     */
    public function getCouponByCode($code) {
        $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([strtoupper($code)]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Tạo coupon mới
     */
    public function createCoupon($data) {
        try {
            $sql = "INSERT INTO coupons (code, name, description, discount_type, discount_value, 
                    max_discount, min_order_value, usage_limit, usage_per_user, start_date, end_date, 
                    is_active, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                strtoupper($data['code']),
                $data['name'],
                $data['description'] ?? null,
                $data['discount_type'],
                $data['discount_value'],
                $data['max_discount'] ?: null,
                $data['min_order_value'] ?? 0,
                $data['usage_limit'] ?: null,
                $data['usage_per_user'] ?? 1,
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['is_active'] ?? 1,
                $data['created_by'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Create coupon error: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                throw new Exception("Mã coupon đã tồn tại");
            }
            throw $e;
        }
    }
    
    /**
     * Cập nhật coupon
     */
    public function updateCoupon($id, $data) {
        try {
            $sql = "UPDATE coupons SET 
                    code = ?, name = ?, description = ?, discount_type = ?, discount_value = ?,
                    max_discount = ?, min_order_value = ?, usage_limit = ?, usage_per_user = ?,
                    start_date = ?, end_date = ?, is_active = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                strtoupper($data['code']),
                $data['name'],
                $data['description'] ?? null,
                $data['discount_type'],
                $data['discount_value'],
                $data['max_discount'] ?: null,
                $data['min_order_value'] ?? 0,
                $data['usage_limit'] ?: null,
                $data['usage_per_user'] ?? 1,
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['is_active'] ?? 1,
                $id
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Update coupon error: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                throw new Exception("Mã coupon đã tồn tại");
            }
            throw $e;
        }
    }
    
    /**
     * Xóa coupon
     */
    public function deleteCoupon($id) {
        $stmt = $this->conn->prepare("DELETE FROM coupons WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Toggle trạng thái coupon
     */
    public function toggleStatus($id) {
        $stmt = $this->conn->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Lấy lịch sử sử dụng coupon
     */
    public function getCouponUsageHistory($couponId) {
        $sql = "SELECT cu.*, dh.ma_don_hang_text, dh.tong_tien 
                FROM coupon_usage cu 
                JOIN don_hang dh ON cu.order_id = dh.id 
                WHERE cu.coupon_id = ? 
                ORDER BY cu.used_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$couponId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Thống kê coupon
     */
    public function getCouponStats() {
        $stats = [];
        
        // Tổng số coupon
        $stmt = $this->conn->query("SELECT COUNT(*) FROM coupons");
        $stats['total'] = $stmt->fetchColumn();
        
        // Coupon đang hoạt động
        $stmt = $this->conn->query("SELECT COUNT(*) FROM coupons WHERE is_active = 1");
        $stats['active'] = $stmt->fetchColumn();
        
        // Tổng lượt sử dụng
        $stmt = $this->conn->query("SELECT SUM(usage_count) FROM coupons");
        $stats['total_usage'] = $stmt->fetchColumn() ?: 0;
        
        // Tổng tiền đã giảm
        $stmt = $this->conn->query("SELECT SUM(discount_amount) FROM coupon_usage");
        $stats['total_discount'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
    }
}
