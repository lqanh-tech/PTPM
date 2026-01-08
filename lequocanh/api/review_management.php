<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

$security = ApiSecurityMiddleware::getInstance();
$security->handle('review_management');

class ReviewManagementAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    private function checkAdmin() {
        if (!isset($_SESSION['ADMIN'])) {
            $this->error('Không có quyền truy cập', 403);
        }
        return $_SESSION['ADMIN'];
    }
    
    public function getAllReviews() {
        $this->checkAdmin();
        
        try {
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? 'all';
            $search = $_GET['search'] ?? '';
            
            $where = [];
            $params = [];
            
            $hasStatusColumn = false;
            try {
                $checkCol = $this->conn->query("SHOW COLUMNS FROM product_reviews LIKE 'status'");
                $hasStatusColumn = $checkCol->rowCount() > 0;
            } catch (Exception $e) {
                $hasStatusColumn = false;
            }
            
            if ($status !== 'all' && $hasStatusColumn) {
                $where[] = "pr.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $where[] = "(pr.comment LIKE ? OR h.tenhanghoa LIKE ? OR pr.ma_nguoi_dung LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $reportCountSql = "(SELECT COUNT(*) FROM review_reports WHERE review_id = pr.id AND status = 'pending')";
            try {
                $this->conn->query("SELECT 1 FROM review_reports LIMIT 1");
            } catch (Exception $e) {
                $reportCountSql = "0";
            }
            
            $sql = "SELECT 
                        pr.*,
                        COALESCE(h.tenhanghoa, 'Sản phẩm không xác định') as product_name,
                        h.hinhanh as product_image,
                        pr.ma_nguoi_dung as user_name,
                        {$reportCountSql} as report_count
                    FROM product_reviews pr
                    LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
                    {$whereClause}
                    ORDER BY pr.ngay_tao DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM product_reviews pr
                        LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
                        {$whereClause}";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($hasStatusColumn) {
                $statsSql = "SELECT 
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN status = 'visible' OR status IS NULL THEN 1 ELSE 0 END) as visible_reviews,
                    SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_reviews,
                    SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted_reviews,
                    0 as pending_approval
                FROM product_reviews";
            } else {
                $statsSql = "SELECT 
                    COUNT(*) as total_reviews,
                    COUNT(*) as visible_reviews,
                    0 as hidden_reviews,
                    0 as deleted_reviews,
                    0 as pending_approval
                FROM product_reviews";
            }
            $stmt = $this->conn->query($statsSql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->success([
                'reviews' => $reviews,
                'stats' => $stats,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get all reviews error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    public function toggleReviewVisibility() {
        $admin = $this->checkAdmin();
        
        try {
            $reviewId = $_POST['review_id'] ?? null;
            $action = $_POST['action_type'] ?? null;
            $note = $_POST['note'] ?? '';
            
            if (!$reviewId || !$action) {
                return $this->error('Thiếu thông tin');
            }
            
            $newStatus = ($action === 'hide') ? 'hidden' : 'visible';
            
            $sql = "UPDATE product_reviews 
                    SET status = ?,
                        admin_note = ?,
                        hidden_at = ?,
                        hidden_by = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $newStatus,
                $note,
                ($action === 'hide') ? date('Y-m-d H:i:s') : null,
                ($action === 'hide') ? $admin : null,
                $reviewId
            ]);
            
            return $this->success([
                'message' => $action === 'hide' ? 'Đã ẩn bình luận' : 'Đã hiện bình luận'
            ]);
            
        } catch (Exception $e) {
            error_log("Toggle visibility error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function deleteReview() {
        $admin = $this->checkAdmin();
        
        try {
            $reviewId = $_POST['review_id'] ?? null;
            $note = $_POST['note'] ?? '';
            
            if (!$reviewId) {
                return $this->error('Thiếu review_id');
            }
            
            $sql = "UPDATE product_reviews 
                    SET status = 'deleted',
                        admin_note = ?,
                        hidden_at = NOW(),
                        hidden_by = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$note, $admin, $reviewId]);
            
            return $this->success(['message' => 'Đã xóa bình luận']);
            
        } catch (Exception $e) {
            error_log("Delete review error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function getReports() {
        $this->checkAdmin();
        
        try {
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? 'all';
            
            $where = $status !== 'all' ? "WHERE rr.status = ?" : '';
            $params = $status !== 'all' ? [$status] : [];
            
            $sql = "SELECT * FROM v_review_reports_list
                    {$where}
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM review_reports rr {$where}";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $this->success([
                'reports' => $reports,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get reports error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function resolveReport() {
        $admin = $this->checkAdmin();
        
        try {
            $reportId = $_POST['report_id'] ?? null;
            $action = $_POST['action'] ?? null;
            $response = $_POST['response'] ?? '';
            
            if (!$reportId || !$action) {
                return $this->error('Thiếu thông tin');
            }
            
            $newStatus = ($action === 'approve') ? 'resolved' : 'rejected';
            
            $sql = "UPDATE review_reports 
                    SET status = ?,
                        admin_response = ?,
                        resolved_by = ?,
                        resolved_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$newStatus, $response, $admin, $reportId]);
            
            if ($action === 'approve') {
                $reportSql = "SELECT review_id FROM review_reports WHERE id = ?";
                $stmt = $this->conn->prepare($reportSql);
                $stmt->execute([$reportId]);
                $report = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($report) {
                    $hideSql = "UPDATE product_reviews 
                               SET status = 'hidden',
                                   admin_note = ?,
                                   hidden_at = NOW(),
                                   hidden_by = ?
                               WHERE id = ?";
                    $stmt = $this->conn->prepare($hideSql);
                    $stmt->execute(["Ẩn do khiếu nại: {$response}", $admin, $report['review_id']]);
                }
            }
            
            return $this->success([
                'message' => $action === 'approve' ? 'Đã chấp nhận khiếu nại và ẩn bình luận' : 'Đã từ chối khiếu nại'
            ]);
            
        } catch (Exception $e) {
            error_log("Resolve report error: " . $e->getMessage());
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

$api = new ReviewManagementAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $api->getAllReviews();
        break;
    case 'toggle_visibility':
        $api->toggleReviewVisibility();
        break;
    case 'delete':
        $api->deleteReview();
        break;
    case 'reports':
        $api->getReports();
        break;
    case 'resolve_report':
        $api->resolveReport();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
