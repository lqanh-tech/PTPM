<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

$security = ApiSecurityMiddleware::getInstance();
$security->handle('report_review');

class ReportReviewAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function reportReview() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập để báo cáo', 401);
            }
            
            $userId = $_SESSION['USER'];
            $reviewId = $_POST['review_id'] ?? null;
            $reason = $_POST['reason'] ?? null;
            $description = trim($_POST['description'] ?? '');
            
            if (!$reviewId || !$reason) {
                return $this->error('Vui lòng chọn lý do báo cáo');
            }
            
            $checkSql = "SELECT id FROM product_reviews WHERE id = ?";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([$reviewId]);
            
            if (!$stmt->fetch()) {
                return $this->error('Bình luận không tồn tại');
            }
            
            $checkReportSql = "SELECT id FROM review_reports 
                              WHERE review_id = ? AND reporter_id = ?";
            $stmt = $this->conn->prepare($checkReportSql);
            $stmt->execute([$reviewId, $userId]);
            
            if ($stmt->fetch()) {
                return $this->error('Bạn đã báo cáo bình luận này rồi');
            }
            
            $sql = "INSERT INTO review_reports 
                    (review_id, reporter_id, reason, description, status)
                    VALUES (?, ?, ?, ?, 'pending')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$reviewId, $userId, $reason, $description]);
            
            $reportId = $this->conn->lastInsertId();
            
            return $this->success([
                'report_id' => $reportId,
                'message' => 'Cảm ơn bạn đã báo cáo. Chúng tôi sẽ xem xét trong thời gian sớm nhất.'
            ]);
            
        } catch (Exception $e) {
            error_log("Report review error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function getUserReports() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            
            $sql = "SELECT 
                        rr.*,
                        pr.comment as review_comment,
                        pr.rating as review_rating,
                        h.tenhanghoa as product_name
                    FROM review_reports rr
                    JOIN product_reviews pr ON rr.review_id = pr.id
                    LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
                    WHERE rr.reporter_id = ?
                    ORDER BY rr.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success(['reports' => $reports]);
            
        } catch (Exception $e) {
            error_log("Get user reports error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
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

$api = new ReportReviewAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'submit':
        $api->reportReview();
        break;
    case 'my_reports':
        $api->getUserReports();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
