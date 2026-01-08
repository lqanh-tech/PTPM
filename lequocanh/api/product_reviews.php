<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

$security = ApiSecurityMiddleware::getInstance();
$security->handle('product_reviews');

class ProductReviewAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function submitReview() {
        try {

            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập để đánh giá', 401);
            }
            
            $userId = $_SESSION['USER'];
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
            
            $checkOrderSql = "SELECT id, trang_thai, trang_thai_thanh_toan 
                             FROM don_hang 
                             WHERE id = ? AND ma_nguoi_dung = ?";
            $stmt = $this->conn->prepare($checkOrderSql);
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return $this->error('Đơn hàng không tồn tại hoặc không thuộc về bạn');
            }
            
            if ($order['trang_thai'] !== 'approved' && $order['trang_thai_thanh_toan'] !== 'paid') {
                return $this->error('Chỉ có thể đánh giá đơn hàng đã được duyệt');
            }
            
            $checkProductSql = "SELECT id FROM chi_tiet_don_hang 
                               WHERE ma_don_hang = ? AND ma_san_pham = ?";
            $stmt = $this->conn->prepare($checkProductSql);
            $stmt->execute([$orderId, $productId]);
            
            if (!$stmt->fetch()) {
                return $this->error('Sản phẩm không có trong đơn hàng này');
            }
            
            $checkReviewSql = "SELECT id FROM product_reviews 
                              WHERE ma_don_hang = ? AND ma_san_pham = ? AND ma_nguoi_dung = ?";
            $stmt = $this->conn->prepare($checkReviewSql);
            $stmt->execute([$orderId, $productId, $userId]);
            
            if ($stmt->fetch()) {
                return $this->error('Bạn đã đánh giá sản phẩm này rồi');
            }
            
            $insertSql = "INSERT INTO product_reviews 
                         (ma_don_hang, ma_san_pham, ma_nguoi_dung, rating, comment, is_verified_purchase, is_approved)
                         VALUES (?, ?, ?, ?, ?, 1, 1)";
            $stmt = $this->conn->prepare($insertSql);
            $stmt->execute([$orderId, $productId, $userId, $rating, $comment]);
            
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
            
            $statsSql = "SELECT * FROM v_product_review_stats WHERE ma_san_pham = ?";
            $stmt = $this->conn->prepare($statsSql);
            $stmt->execute([$productId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $reviewsSql = "SELECT 
                            pr.*,
                            pr.ma_nguoi_dung as user_name,
                            '' as user_email
                          FROM product_reviews pr
                          WHERE pr.ma_san_pham = ? 
                            AND pr.is_approved = 1
                            AND (pr.status = 'visible' OR pr.status IS NULL)
                          ORDER BY pr.ngay_tao DESC
                          LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($reviewsSql);
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM product_reviews 
                        WHERE ma_san_pham = ? AND is_approved = 1 
                        AND (status = 'visible' OR status IS NULL)";
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
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function checkReviewed() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->success(['can_review' => false, 'reason' => 'not_logged_in']);
            }
            
            $userId = $_SESSION['USER'];
            $orderId = $_GET['order_id'] ?? null;
            
            if (!$orderId) {
                return $this->error('Thiếu order_id');
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
                            WHERE ma_don_hang = ? AND ma_san_pham = ? AND ma_nguoi_dung = ?";
                $stmt = $this->conn->prepare($checkSql);
                $stmt->execute([$orderId, $product['ma_san_pham'], $userId]);
                
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
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function markHelpful() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            $reviewId = $_POST['review_id'] ?? null;
            
            if (!$reviewId) {
                return $this->error('Thiếu review_id');
            }
            
            $checkSql = "SELECT id FROM review_helpful WHERE review_id = ? AND ma_nguoi_dung = ?";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([$reviewId, $userId]);
            
            if ($stmt->fetch()) {
                return $this->error('Bạn đã đánh dấu hữu ích rồi');
            }
            
            $insertSql = "INSERT INTO review_helpful (review_id, ma_nguoi_dung) VALUES (?, ?)";
            $stmt = $this->conn->prepare($insertSql);
            $stmt->execute([$reviewId, $userId]);
            
            $updateSql = "UPDATE product_reviews SET helpful_count = helpful_count + 1 WHERE id = ?";
            $stmt = $this->conn->prepare($updateSql);
            $stmt->execute([$reviewId]);
            
            return $this->success(['message' => 'Cảm ơn phản hồi của bạn!']);
            
        } catch (Exception $e) {
            error_log("Mark helpful error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    private function updateOrderReviewStatus($orderId) {
        try {

            $countProductsSql = "SELECT COUNT(DISTINCT ma_san_pham) as total 
                                FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
            $stmt = $this->conn->prepare($countProductsSql);
            $stmt->execute([$orderId]);
            $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $countReviewsSql = "SELECT COUNT(DISTINCT ma_san_pham) as total 
                               FROM product_reviews WHERE ma_don_hang = ?";
            $stmt = $this->conn->prepare($countReviewsSql);
            $stmt->execute([$orderId]);
            $totalReviews = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($totalProducts == $totalReviews) {
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
