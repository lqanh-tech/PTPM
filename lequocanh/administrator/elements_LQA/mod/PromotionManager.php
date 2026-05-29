<?php

require_once __DIR__ . '/database.php';

class PromotionManager
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function getActivePromotions()
    {
        try {
            $sql = "SELECT id, name, description, discount_type, discount_value, start_date, end_date, is_active, created_at FROM promotions 
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

    public function getAllPromotions()
    {
        try {
            $sql = "SELECT id, name, description, discount_type, discount_value, start_date, end_date, is_active, created_at FROM promotions ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all promotions: " . $e->getMessage());
            return [];
        }
    }

    public function getPromotionById($id)
    {
        try {
            $sql = "SELECT id, name, description, discount_type, discount_value, start_date, end_date, is_active, created_at FROM promotions WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting promotion by ID: " . $e->getMessage());
            return null;
        }
    }

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

    public function getDiscountedProducts()
    {
        try {
            $sql = "SELECT h.*, ha.duong_dan AS hinhanh_url
                    FROM hanghoa h
                    LEFT JOIN hinhanh ha ON h.hinhanh = ha.id
                    WHERE h.giakhuyenmai > 0 
                    AND h.giakhuyenmai < h.giathamkhao
                    AND h.trang_thai = 1
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
