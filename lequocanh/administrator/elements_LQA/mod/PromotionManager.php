<?php

/**
 * Promotion Manager
 * Quản lý chương trình ưu đãi
 */

require_once 'database.php';

class PromotionManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lấy tất cả chương trình ưu đãi đang hoạt động
     */
    public function getActivePromotions()
    {
        try {
            $sql = "SELECT * FROM promotions 
                    WHERE is_active = 1 
                    AND start_date <= CURDATE() 
                    AND end_date >= CURDATE() 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active promotions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tất cả chương trình ưu đãi (cho admin)
     */
    public function getAllPromotions()
    {
        try {
            $sql = "SELECT * FROM promotions ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all promotions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy chương trình ưu đãi theo ID
     */
    public function getPromotionById($id)
    {
        try {
            $sql = "SELECT * FROM promotions WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting promotion by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Thêm chương trình ưu đãi mới
     */
    public function addPromotion($title, $description, $discount_percent, $start_date, $end_date, $is_active)
    {
        try {
            $sql = "INSERT INTO promotions (title, description, discount_percent, start_date, end_date, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $description, $discount_percent, $start_date, $end_date, $is_active]);
        } catch (Exception $e) {
            error_log("Error adding promotion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật chương trình ưu đãi
     */
    public function updatePromotion($id, $title, $description, $discount_percent, $start_date, $end_date, $is_active)
    {
        try {
            $sql = "UPDATE promotions SET title = ?, description = ?, discount_percent = ?, 
                           start_date = ?, end_date = ?, is_active = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $description, $discount_percent, $start_date, $end_date, $is_active, $id]);
        } catch (Exception $e) {
            error_log("Error updating promotion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa chương trình ưu đãi
     */
    public function deletePromotion($id)
    {
        try {
            $sql = "DELETE FROM promotions WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting promotion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy sản phẩm được giảm giá (sản phẩm có giá khuyến mãi nhỏ hơn giá tham khảo)
     */
    public function getDiscountedProducts()
    {
        try {
            $sql = "SELECT h.*, ha.ten AS hinhanh_url
                    FROM hanghoa h
                    LEFT JOIN hinhanh ha ON h.hinhanh = ha.id
                    WHERE h.giakhuyenmai > 0 
                    AND h.giakhuyenmai < h.giathamkhao
                    AND h.trangthai = 1
                    ORDER BY (CASE WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' THEN 0 ELSE 1 END) ASC,
                             (h.giathamkhao - h.giakhuyenmai) / h.giathamkhao DESC
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting discounted products: " . $e->getMessage());
            return [];
        }
    }
}
