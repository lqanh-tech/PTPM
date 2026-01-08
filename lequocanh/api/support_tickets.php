<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

$security = ApiSecurityMiddleware::getInstance();
$security->handle('support_tickets');

class SupportTicketAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function createTicket() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            $subject = trim($_POST['subject'] ?? '');
            $category = $_POST['category'] ?? 'other';
            $message = trim($_POST['message'] ?? '');
            $relatedReviewId = $_POST['related_review_id'] ?? null;
            $relatedOrderId = $_POST['related_order_id'] ?? null;
            
            if (!$subject || !$message) {
                return $this->error('Vui lòng nhập đầy đủ thông tin');
            }
            
            $ticketNumber = 'TK' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO support_tickets 
                    (ticket_number, user_id, subject, category, related_review_id, related_order_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'open')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$ticketNumber, $userId, $subject, $category, $relatedReviewId, $relatedOrderId]);
            
            $ticketId = $this->conn->lastInsertId();
            
            $msgSql = "INSERT INTO support_messages (ticket_id, sender_id, sender_type, message)
                      VALUES (?, ?, 'user', ?)";
            $stmt = $this->conn->prepare($msgSql);
            $stmt->execute([$ticketId, $userId, $message]);
            
            return $this->success([
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'message' => 'Đã tạo yêu cầu hỗ trợ thành công'
            ]);
            
        } catch (Exception $e) {
            error_log("Create ticket error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function getUserTickets() {
        try {
            if (!isset($_SESSION['USER'])) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $userId = $_SESSION['USER'];
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM v_support_tickets_list
                    WHERE user_id = ?
                    ORDER BY updated_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM support_tickets WHERE user_id = ?";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute([$userId]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $this->success([
                'tickets' => $tickets,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get user tickets error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function getAdminTickets() {
        try {
            if (!isset($_SESSION['ADMIN'])) {
                return $this->error('Không có quyền truy cập', 403);
            }
            
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? 'all';
            
            $where = $status !== 'all' ? "WHERE status = ?" : '';
            $params = $status !== 'all' ? [$status] : [];
            
            $sql = "SELECT * FROM v_support_tickets_list
                    {$where}
                    ORDER BY updated_at DESC
                    LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(*) as total FROM support_tickets {$where}";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $statsSql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                            SUM(CASE WHEN status = 'waiting_user' THEN 1 ELSE 0 END) as waiting_user_count,
                            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
                        FROM support_tickets";
            $stmt = $this->conn->query($statsSql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->success([
                'tickets' => $tickets,
                'stats' => $stats,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get admin tickets error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function getTicketDetails() {
        try {
            $ticketId = $_GET['ticket_id'] ?? null;
            
            if (!$ticketId) {
                return $this->error('Thiếu ticket_id');
            }
            
            $isAdmin = isset($_SESSION['ADMIN']);
            $userId = $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? null;
            
            if (!$userId) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $sql = "SELECT * FROM support_tickets WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return $this->error('Ticket không tồn tại');
            }
            
            if (!$isAdmin && intval($ticket['user_id']) !== intval($userId)) {
                return $this->error('Không có quyền truy cập', 403);
            }
            
            $msgSql = "SELECT * FROM support_messages 
                      WHERE ticket_id = ? 
                      ORDER BY created_at ASC";
            $stmt = $this->conn->prepare($msgSql);
            $stmt->execute([$ticketId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $senderType = $isAdmin ? 'user' : 'admin';
            $updateSql = "UPDATE support_messages 
                         SET is_read = 1 
                         WHERE ticket_id = ? AND sender_type = ? AND is_read = 0";
            $stmt = $this->conn->prepare($updateSql);
            $stmt->execute([$ticketId, $senderType]);
            
            return $this->success([
                'ticket' => $ticket,
                'messages' => $messages
            ]);
            
        } catch (Exception $e) {
            error_log("Get ticket details error: " . $e->getMessage());
            return $this->error('Lỗi khi tải chi tiết ticket');
        }
    }
    
    public function sendMessage() {
        try {
            $ticketId = $_POST['ticket_id'] ?? null;
            $message = trim($_POST['message'] ?? '');
            
            if (!$ticketId || !$message) {
                return $this->error('Thiếu thông tin');
            }
            
            $isAdmin = isset($_SESSION['ADMIN']);
            $userId = $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? null;
            
            if (!$userId) {
                return $this->error('Vui lòng đăng nhập', 401);
            }
            
            $sql = "SELECT * FROM support_tickets WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return $this->error('Ticket không tồn tại');
            }
            
            if (!$isAdmin && intval($ticket['user_id']) !== intval($userId)) {
                return $this->error('Không có quyền truy cập', 403);
            }
            
            $senderType = $isAdmin ? 'admin' : 'user';
            $senderId = $isAdmin ? $_SESSION['ADMIN'] : $_SESSION['USER'];
            $msgSql = "INSERT INTO support_messages (ticket_id, sender_id, sender_type, message)
                      VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($msgSql);
            $stmt->execute([$ticketId, $senderId, $senderType, $message]);
            
            if ($isAdmin && $ticket['status'] === 'open') {
                $updateSql = "UPDATE support_tickets SET status = 'in_progress' WHERE id = ?";
                $stmt = $this->conn->prepare($updateSql);
                $stmt->execute([$ticketId]);
            }
            
            return $this->success(['message' => 'Đã gửi tin nhắn']);
            
        } catch (Exception $e) {
            error_log("Send message error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function updateTicketStatus() {
        try {
            if (!isset($_SESSION['ADMIN'])) {
                return $this->error('Không có quyền truy cập', 403);
            }
            
            $ticketId = $_POST['ticket_id'] ?? null;
            $status = $_POST['status'] ?? null;
            
            if (!$ticketId || !$status) {
                return $this->error('Thiếu thông tin');
            }
            
            $sql = "UPDATE support_tickets SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$status, $ticketId]);
            
            return $this->success(['message' => 'Đã cập nhật trạng thái']);
            
        } catch (Exception $e) {
            error_log("Update ticket status error: " . $e->getMessage());
            return $this->error('Có lỗi xảy ra');
        }
    }
    
    public function assignTicket() {
        try {
            if (!isset($_SESSION['ADMIN'])) {
                return $this->error('Không có quyền truy cập', 403);
            }
            
            $ticketId = $_POST['ticket_id'] ?? null;
            $assignTo = $_POST['assign_to'] ?? null;
            
            if (!$ticketId) {
                return $this->error('Thiếu ticket_id');
            }
            
            $sql = "UPDATE support_tickets SET assigned_to = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$assignTo, $ticketId]);
            
            return $this->success(['message' => 'Đã gán ticket']);
            
        } catch (Exception $e) {
            error_log("Assign ticket error: " . $e->getMessage());
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

$api = new SupportTicketAPI();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $api->createTicket();
        break;
    case 'user_list':
        $api->getUserTickets();
        break;
    case 'admin_list':
        $api->getAdminTickets();
        break;
    case 'details':
        $api->getTicketDetails();
        break;
    case 'send_message':
        $api->sendMessage();
        break;
    case 'update_status':
        $api->updateTicketStatus();
        break;
    case 'assign':
        $api->assignTicket();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
