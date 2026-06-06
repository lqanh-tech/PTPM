<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

try {
    $security = ApiSecurityMiddleware::getInstance();
    $security->handle('product_reviews');
} catch (Exception $e) {
    error_log("Middleware error: " . $e->getMessage());
}

class ProductReviewAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    private function resolveUserId($identifier) {
        if (is_numeric($identifier)) {
            return (int)$identifier;
        }
        $sql = "SELECT iduser FROM user WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['iduser'] : null;
    }
    
    public function submitReview() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập để đánh giá', 401);
            }
            
            $userIdentifier = $_SESSION['USER'];
            $orderId = $_POST['order_id'] ?? null;
            $productId = $_POST['product_id'] ?? null;
            $rating = $_POST['rating'] ?? null;
            $comment = trim($_POST['comment'] ?? '');
            
            if (!$orderId || !$productId || !$rating) {
                return $this->error('Thiếu thông tin bắt buộc');
            }
            
            if ($rating < 1 || $rating > 5) {
                return $this->error('Đánh giá phải từ 1-5 sao');
            }

            $userId = $this->resolveUserId($userIdentifier);
            if (!$userId) {
                return $this->error('Người dùng không tồn tại');
            }
            
            $checkOrderSql = "SELECT id, trang_thai, trang_thai_thanh_toan 
                             FROM don_hang 
                             WHERE id = ? AND ma_nguoi_dung = ?";
            $stmt = $this->conn->prepare($checkOrderSql);
            $stmt->execute([$orderId, $userIdentifier]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return $this->error('Đơn hàng không tồn tại hoặc không thuộc về bạn');
            }
            
            if ($order['trang_thai'] !== 'completed' || !in_array($order['trang_thai_thanh_toan'], ['paid', 'completed'])) {
                return $this->error('Chỉ có thể đánh giá sau khi đơn hàng đã hoàn tất và thanh toán');
            }
            
            $checkProductSql = "SELECT id FROM chi_tiet_don_hang 
                               WHERE ma_don_hang = ? AND ma_san_pham = ?";
            $stmt = $this->conn->prepare($checkProductSql);
            $stmt->execute([$orderId, $productId]);
            
            if (!$stmt->fetch()) {
                return $this->error('Sản phẩm không có trong đơn hàng này');
            }
            
            $checkReviewSql = "SELECT id FROM product_reviews 
                               WHERE product_id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($checkReviewSql);
            $stmt->execute([$productId, $userId]);
            
            if ($stmt->fetch()) {
                return $this->error('Bạn đã đánh giá sản phẩm này rồi');
            }
            
            $insertSql = "INSERT INTO product_reviews 
                         (product_id, user_id, rating, comment, is_verified_purchase, is_approved, status)
                         VALUES (?, ?, ?, ?, 1, 1, 'approved')";
            $stmt = $this->conn->prepare($insertSql);
            $stmt->execute([$productId, $userId, $rating, $comment]);
            
            $reviewId = $this->conn->lastInsertId();
            
            $this->updateOrderReviewStatus($orderId);
            
            return $this->success([
                'review_id' => $reviewId,
                'message' => 'Cảm ơn bạn đã đánh giá!'
            ]);
            
        } catch (Exception $e) {
            error_log("Submit review error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    public function getReviews() {
        try {
            $productId = $_GET['product_id'] ?? null;
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            if (!$productId) {
                return $this->error('Thiếu product_id');
            }
            
            $statsSql = "SELECT 
                COUNT(*) as total_reviews,
                COALESCE(AVG(rating), 0) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM product_reviews 
                WHERE product_id = ? AND is_approved = 1 AND (status = 'approved' OR status IS NULL)";
            $stmt = $this->conn->prepare($statsSql);
            $stmt->execute([$productId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $reviewsSql = "SELECT 
                            pr.*,
                            u.hoten as user_name,
                            '' as user_email
                          FROM product_reviews pr
                          LEFT JOIN user u ON pr.user_id = u.iduser
                          WHERE pr.product_id = ? 
                            AND pr.is_approved = 1
                            AND (pr.status = 'approved' OR pr.status IS NULL)
                          ORDER BY pr.created_at DESC
                          LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($reviewsSql);
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM product_reviews 
                        WHERE product_id = ? AND is_approved = 1 
                        AND (status = 'approved' OR status IS NULL)";
            $stmt = $this->conn->prepare($countSql);
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $this->success([
                'stats' => $stats ?: [
                    'total_reviews' => 0,
                    'average_rating' => 0,
                    'five_star' => 0,
                    'four_star' => 0,
                    'three_star' => 0,
                    'two_star' => 0,
                    'one_star' => 0
                ],
                'reviews' => $reviews,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get reviews error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    public function checkReviewed() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->success(['can_review' => false, 'reason' => 'not_logged_in']);
            }
            
            $userIdentifier = $_SESSION['USER'];
            $orderId = $_GET['order_id'] ?? null;
            
            if (!$orderId) {
                return $this->error('Thiếu order_id');
            }
            
            $userId = $this->resolveUserId($userIdentifier);
            if (!$userId) {
                return $this->error('Người dùng không tồn tại');
            }
            
            $productsSql = "SELECT DISTINCT cdh.ma_san_pham, h.tenhanghoa as product_name
                           FROM chi_tiet_don_hang cdh
                           JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
                           WHERE cdh.ma_don_hang = ?";
            $stmt = $this->conn->prepare($productsSql);
            $stmt->execute([$orderId]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reviewStatus = [];
            foreach ($products as $product) {
                $checkSql = "SELECT id FROM product_reviews 
                            WHERE product_id = ? AND user_id = ?";
                $stmt = $this->conn->prepare($checkSql);
                $stmt->execute([$product['ma_san_pham'], $userId]);
                
                $reviewStatus[] = [
                    'product_id' => $product['ma_san_pham'],
                    'product_name' => $product['product_name'],
                    'reviewed' => $stmt->fetch() ? true : false
                ];
            }
            
            return $this->success([
                'can_review' => true,
                'products' => $reviewStatus
            ]);
            
        } catch (Exception $e) {
            error_log("Check reviewed error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    public function markHelpful() {
        try {
            $reviewId = $_POST['review_id'] ?? null;
            
            if (!$reviewId) {
                return $this->error('Thiếu review_id');
            }
            
            $updateSql = "UPDATE product_reviews SET helpful_count = helpful_count + 1 WHERE id = ?";
            $stmt = $this->conn->prepare($updateSql);
            $stmt->execute([$reviewId]);
            
            return $this->success(['message' => 'Cảm ơn phản hồi của bạn!']);
            
        } catch (Exception $e) {
            error_log("Mark helpful error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    private function updateOrderReviewStatus($orderId) {
        try {
            $countProductsSql = "SELECT COUNT(DISTINCT ma_san_pham) as total 
                                FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
            $stmt = $this->conn->prepare($countProductsSql);
            $stmt->execute([$orderId]);
            $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $countReviewsSql = "SELECT COUNT(DISTINCT pr.product_id) as total 
                               FROM product_reviews pr
                               INNER JOIN chi_tiet_don_hang ct ON pr.product_id = ct.ma_san_pham
                               WHERE ct.ma_don_hang = ?";
            $stmt = $this->conn->prepare($countReviewsSql);
            $stmt->execute([$orderId]);
            $totalReviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($totalProducts == $totalReviews && $totalProducts > 0) {
                $updateSql = "UPDATE don_hang SET is_reviewed = 1 WHERE id = ?";
                $stmt = $this->conn->prepare($updateSql);
                $stmt->execute([$orderId]);
            }
        } catch (Exception $e) {
            error_log("Update order review status error: " . $e->getMessage());
        }
    }
    
    private function success($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function error($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new ProductReviewAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'submit':
        $api->submitReview();
        break;
    case 'list':
        $api->getReviews();
        break;
    case 'check':
        $api->checkReviewed();
        break;
    case 'helpful':
        $api->markHelpful();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
