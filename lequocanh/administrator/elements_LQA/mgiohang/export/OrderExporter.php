<?php
/**
 * Order Exporter Class
 * Xử lý xuất đơn hàng ra PDF/Excel
 */

require_once __DIR__ . '/../../mod/database.php';

class OrderExporter {
    private $conn;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    /**
     * Lấy thông tin đơn hàng chi tiết
     */
    public function getOrderDetails($orderId) {
        $sql = "SELECT 
                    dh.*,
                    u.hoten as ten_khach_hang,
                    u.email,
                    u.dienthoai as dien_thoai
                FROM don_hang dh
                LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci
                WHERE dh.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Lấy chi tiết sản phẩm
            $sql = "SELECT 
                        ct.*,
                        hh.tenhanghoa,
                        hh.giathamkhao as gia
                    FROM chi_tiet_don_hang ct
                    LEFT JOIN hanghoa hh ON ct.ma_san_pham = hh.idhanghoa
                    WHERE ct.ma_don_hang = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$orderId]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $order;
    }
    
    /**
     * Lấy danh sách đơn hàng theo bộ lọc
     */
    public function getOrdersList($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "dh.trang_thai = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_method'])) {
            $where[] = "dh.phuong_thuc_thanh_toan = ?";
            $params[] = $filters['payment_method'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(dh.ngay_tao) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(dh.ngay_tao) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(dh.ma_don_hang_text LIKE ? OR u.hoten LIKE ? OR u.dienthoai LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql = "SELECT 
                    dh.id,
                    dh.ma_don_hang_text,
                    dh.ngay_tao,
                    dh.tong_tien,
                    dh.trang_thai,
                    dh.phuong_thuc_thanh_toan,
                    dh.trang_thai_thanh_toan,
                    u.hoten as ten_khach_hang,
                    u.dienthoai as dien_thoai,
                    u.email
                FROM don_hang dh
                LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci
                WHERE " . implode(" AND ", $where) . "
                ORDER BY dh.ngay_tao DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy chi tiết nhiều đơn hàng
     */
    public function getMultipleOrdersDetails($orderIds) {
        if (empty($orderIds)) return [];
        
        $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
        
        $sql = "SELECT 
                    dh.*,
                    u.hoten as ten_khach_hang,
                    u.email,
                    u.dienthoai as dien_thoai
                FROM don_hang dh
                LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci
                WHERE dh.id IN ($placeholders)
                ORDER BY dh.ngay_tao DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($orderIds);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy chi tiết sản phẩm cho tất cả đơn
        foreach ($orders as &$order) {
            $sql = "SELECT 
                        ct.*,
                        hh.tenhanghoa,
                        hh.giathamkhao as gia
                    FROM chi_tiet_don_hang ct
                    LEFT JOIN hanghoa hh ON ct.ma_san_pham = hh.idhanghoa
                    WHERE ct.ma_don_hang = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $orders;
    }
}
