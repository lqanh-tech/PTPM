<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

try {
    $security = ApiSecurityMiddleware::getInstance();
    $security->handle('review_management');
} catch (Exception $e) {
    error_log("Middleware error: " . $e->getMessage());
}

class ReviewManagementAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    private function checkAdmin() {
        return true;
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
            } elseif ($status !== 'all' && !$hasStatusColumn) {
                if ($status === 'visible') {
                    $where[] = "pr.is_approved = 1";
                } elseif ($status === 'hidden') {
                    $where[] = "pr.is_approved = 0";
                }
            }
            
            if ($search) {
                $where[] = "(pr.comment LIKE ? OR h.tenhanghoa LIKE ? OR u.hoten LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT 
                        pr.*,
                        COALESCE(h.tenhanghoa, 'Sản phẩm không xác định') as product_name,
                        h.hinhanh as product_image,
                        u.hoten as user_name,
                        0 as report_count
                    FROM product_reviews pr
                    LEFT JOIN hanghoa h ON pr.product_id = h.idhanghoa
                    LEFT JOIN user u ON pr.user_id = u.iduser
                    {$whereClause}
                    ORDER BY pr.created_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM product_reviews pr
                        LEFT JOIN hanghoa h ON pr.product_id = h.idhanghoa
                        LEFT JOIN user u ON pr.user_id = u.iduser
                        {$whereClause}";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($hasStatusColumn) {
                $statsSql = "SELECT 
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN status = 'approved' OR status IS NULL THEN 1 ELSE 0 END) as visible_reviews,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_approval,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as hidden_reviews,
                    0 as deleted_reviews
                FROM product_reviews";
            } else {
                $statsSql = "SELECT 
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as visible_reviews,
                    0 as pending_approval,
                    0 as hidden_reviews,
                    0 as deleted_reviews
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
            
            $newStatus = ($action === 'hide') ? 'rejected' : 'approved';
            
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
                    SET status = 'rejected',
                        is_approved = 0,
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
            
            $sql = "SELECT rr.*, pr.comment as review_comment, pr.rating as review_rating,
                        u.hoten as reporter_name, h.tenhanghoa as product_name
                    FROM review_reports rr
                    LEFT JOIN product_reviews pr ON rr.review_id = pr.id
                    LEFT JOIN user u ON rr.reporter_id = u.iduser
                    LEFT JOIN hanghoa h ON pr.product_id = h.idhanghoa
                    {$where}
                    ORDER BY rr.created_at DESC
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
            $action = $_POST['action_type'] ?? $_POST['action'] ?? null;
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
    
    public function markReportViewed() {
        try {
            $reportId = intval($_POST['report_id'] ?? 0);
            if ($reportId <= 0) return $this->error('Thiếu report_id');
            
            $stmt = $this->conn->prepare("UPDATE review_reports SET status = 'resolved' WHERE id = ?");
            $stmt->execute([$reportId]);
            
            return $this->success(['message' => 'Đã đánh dấu đã xem']);
        } catch (Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    public function markReportPending() {
        try {
            $reportId = intval($_POST['report_id'] ?? 0);
            if ($reportId <= 0) return $this->error('Thiếu report_id');
            
            $stmt = $this->conn->prepare("UPDATE review_reports SET status = 'pending' WHERE id = ?");
            $stmt->execute([$reportId]);
            
            return $this->success(['message' => 'Đã đánh dấu chưa xem']);
        } catch (Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage());
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
        case 'mark_report_viewed':
            $api->markReportViewed();
            break;
        case 'mark_report_pending':
            $api->markReportPending();
            break;
        default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
