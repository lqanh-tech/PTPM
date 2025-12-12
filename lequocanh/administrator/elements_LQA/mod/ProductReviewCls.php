<?php
/**
 * Product Review Class
 * Handles all product review operations
 */

require_once __DIR__ . '/database.php';

class ProductReview
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Add a new review
     */
    public function addReview($idhanghoa, $iduser, $idhoadon, $rating, $title, $text)
    {
        try {
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => 'Rating phải từ 1 đến 5 sao'];
            }

            // Check if user already reviewed this product
            if ($this->hasUserReviewed($iduser, $idhanghoa)) {
                return ['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi'];
            }

            // Check if this is a verified purchase
            $isVerified = $idhoadon ? $this->verifyPurchase($iduser, $idhanghoa, $idhoadon) : false;

            $sql = "INSERT INTO product_reviews 
                    (idhanghoa, iduser, idhoadon, rating, review_title, review_text, is_verified_purchase) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $idhanghoa,
                $iduser,
                $idhoadon,
                $rating,
                $title,
                $text,
                $isVerified ? 1 : 0
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Cảm ơn bạn đã đánh giá!',
                    'review_id' => $this->db->lastInsertId()
                ];
            }

            return ['success' => false, 'message' => 'Không thể thêm đánh giá'];

        } catch (PDOException $e) {
            error_log("Error adding review: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống'];
        }
    }

    /**
     * Get reviews for a product
     */
    public function getProductReviews($idhanghoa, $limit = 10, $offset = 0, $rating_filter = null)
    {
        try {
            // Chỉ lấy bình luận visible (không bị ẩn/xóa)
            $sql = "SELECT r.*, u.hoten, u.username 
                    FROM product_reviews r
                    INNER JOIN user u ON r.iduser = u.iduser
                    WHERE r.idhanghoa = ? AND r.is_approved = 1
                    AND (r.status = 'visible' OR r.status IS NULL)";
            
            $params = [$idhanghoa];

            if ($rating_filter) {
                $sql .= " AND r.rating = ?";
                $params[] = $rating_filter;
            }

            $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            error_log("Error getting reviews: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get rating statistics for a product
     */
    public function getProductRatingStats($idhanghoa)
    {
        try {
            // Chỉ tính thống kê từ bình luận visible (không bị ẩn/xóa)
            $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                    FROM product_reviews 
                    WHERE idhanghoa = ? AND is_approved = 1
                    AND (status = 'visible' OR status IS NULL)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            
            $stats = $stmt->fetch(PDO::FETCH_OBJ);
            
            // Calculate percentages
            if ($stats && $stats->total_reviews > 0) {
                $stats->five_star_percent = round(($stats->five_star / $stats->total_reviews) * 100);
                $stats->four_star_percent = round(($stats->four_star / $stats->total_reviews) * 100);
                $stats->three_star_percent = round(($stats->three_star / $stats->total_reviews) * 100);
                $stats->two_star_percent = round(($stats->two_star / $stats->total_reviews) * 100);
                $stats->one_star_percent = round(($stats->one_star / $stats->total_reviews) * 100);
                $stats->avg_rating = round($stats->avg_rating, 1);
            }

            return $stats;

        } catch (PDOException $e) {
            error_log("Error getting rating stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user can review a product
     */
    public function canUserReview($iduser, $idhanghoa)
    {
        // User must be logged in
        if (!$iduser) {
            return ['can_review' => false, 'reason' => 'Bạn cần đăng nhập để đánh giá'];
        }

        // Check if user already reviewed
        if ($this->hasUserReviewed($iduser, $idhanghoa)) {
            return ['can_review' => false, 'reason' => 'Bạn đã đánh giá sản phẩm này'];
        }

        // Check if user purchased this product and order is delivered
        $purchase = $this->getUserPurchase($iduser, $idhanghoa);
        
        if (!$purchase) {
            return ['can_review' => false, 'reason' => 'Bạn chưa mua sản phẩm này'];
        }

        if ($purchase->trangthai != 'Đã giao' && $purchase->trangthai != 'Hoàn thành') {
            return ['can_review' => false, 'reason' => 'Đơn hàng chưa được giao'];
        }

        return [
            'can_review' => true,
            'idhoadon' => $purchase->idhoadon
        ];
    }

    /**
     * Check if user has already reviewed a product
     */
    public function hasUserReviewed($iduser, $idhanghoa)
    {
        try {
            $sql = "SELECT COUNT(*) FROM product_reviews 
                    WHERE iduser = ? AND idhanghoa = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$iduser, $idhanghoa]);
            
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("Error checking review: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's purchase of a product
     */
    private function getUserPurchase($iduser, $idhanghoa)
    {
        try {
            $sql = "SELECT h.idhoadon, h.trangthai 
                    FROM hoadon h
                    INNER JOIN chitiethoadon ct ON h.idhoadon = ct.idhoadon
                    WHERE h.iduser = ? AND ct.idhanghoa = ?
                    ORDER BY h.ngaylap DESC
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$iduser, $idhanghoa]);
            
            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            error_log("Error getting user purchase: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify if purchase is valid
     */
    private function verifyPurchase($iduser, $idhanghoa, $idhoadon)
    {
        try {
            $sql = "SELECT COUNT(*) FROM hoadon h
                    INNER JOIN chitiethoadon ct ON h.idhoadon = ct.idhoadon
                    WHERE h.idhoadon = ? AND h.iduser = ? AND ct.idhanghoa = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhoadon, $iduser, $idhanghoa]);
            
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("Error verifying purchase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful($review_id, $iduser)
    {
        try {
            // Check if user already marked this review
            $check = "SELECT COUNT(*) FROM review_helpful 
                     WHERE review_id = ? AND iduser = ?";
            $stmt = $this->db->prepare($check);
            $stmt->execute([$review_id, $iduser]);
            
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Bạn đã đánh dấu review này rồi'];
            }

            // Add helpful mark
            $sql = "INSERT INTO review_helpful (review_id, iduser) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$review_id, $iduser]);

            // Update helpful count
            $update = "UPDATE product_reviews 
                      SET helpful_count = helpful_count + 1 
                      WHERE id = ?";
            $stmt = $this->db->prepare($update);
            $stmt->execute([$review_id]);

            return ['success' => true, 'message' => 'Cảm ơn phản hồi của bạn!'];

        } catch (PDOException $e) {
            error_log("Error marking helpful: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống'];
        }
    }

    /**
     * Get user's reviews
     */
    public function getUserReviews($iduser)
    {
        try {
            $sql = "SELECT r.*, h.tenhanghoa 
                    FROM product_reviews r
                    INNER JOIN hanghoa h ON r.idhanghoa = h.idhanghoa
                    WHERE r.iduser = ?
                    ORDER BY r.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$iduser]);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            error_log("Error getting user reviews: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete review (user can delete their own review)
     */
    public function deleteReview($review_id, $iduser)
    {
        try {
            // Verify ownership
            $sql = "DELETE FROM product_reviews 
                    WHERE id = ? AND iduser = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$review_id, $iduser]);

            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Đã xóa đánh giá'];
            }

            return ['success' => false, 'message' => 'Không thể xóa đánh giá'];

        } catch (PDOException $e) {
            error_log("Error deleting review: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống'];
        }
    }

    /**
     * Get total review count for product
     */
    public function getReviewCount($idhanghoa)
    {
        try {
            // Chỉ đếm bình luận visible (không bị ẩn/xóa)
            $sql = "SELECT COUNT(*) FROM product_reviews 
                    WHERE idhanghoa = ? AND is_approved = 1
                    AND (status = 'visible' OR status IS NULL)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            
            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Error getting review count: " . $e->getMessage());
            return 0;
        }
    }
}
