<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

SessionManager::start();

$security = ApiSecurityMiddleware::getInstance();
$security->handle();

class CustomerDetailAPI {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function getCustomerDetail() {
        try {

            if (!isset($_SESSION['ADMIN'])) {
                return $this->error('Không có quyền truy cập', 403);
            }
            
            $username = $_GET['username'] ?? null;
            
            if (!$username) {
                return $this->error('Thiếu username');
            }
            
            $customerSql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, 
                                   u.diachi, u.dienthoai, u.email, u.ngaydangki as ngaytao, u.setlock
                            FROM user u
                            WHERE u.username = ? AND u.username != 'admin'";
            $stmt = $this->conn->prepare($customerSql);
            $stmt->execute([$username]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                return $this->error('Không tìm thấy khách hàng');
            }
            
            $checkNvSql = "SELECT nv.idnhanvien FROM nhanvien nv 
                           INNER JOIN user u ON nv.iduser = u.iduser 
                           WHERE u.username = ?";
            $stmt = $this->conn->prepare($checkNvSql);
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                return $this->error('Đây là tài khoản nhân viên');
            }
            
            $ordersSql = "SELECT id, ma_don_hang_text, ngay_tao as ngay_dat, tong_tien, trang_thai, 
                                 trang_thai_thanh_toan, phuong_thuc_thanh_toan
                          FROM don_hang 
                          WHERE ma_nguoi_dung = ?
                          ORDER BY ngay_tao DESC
                          LIMIT 10";
            $stmt = $this->conn->prepare($ordersSql);
            $stmt->execute([$username]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $statsSql = "SELECT 
                            COUNT(*) as order_count,
                            COALESCE(SUM(CASE WHEN trang_thai = 'approved' THEN tong_tien ELSE 0 END), 0) as total_spent,
                            SUM(CASE WHEN trang_thai = 'approved' THEN 1 ELSE 0 END) as approved_orders
                         FROM don_hang 
                         WHERE ma_nguoi_dung = ?";
            $stmt = $this->conn->prepare($statsSql);
            $stmt->execute([$username]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->success([
                'customer' => $customer,
                'orders' => $orders,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get customer detail error: " . $e->getMessage());
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

$api = new CustomerDetailAPI();
$api->getCustomerDetail();
