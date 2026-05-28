<?php

declare(strict_types=1);

namespace App\Services;

use Database;
use PDO;

class OrderService
{
    private static ?self $instance = null;
    private PDO $db;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of singleton.
     */
    private function __clone() {}

    /**
     * Get orders by user ID with pagination.
     */
    public function getOrdersByUserId($userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, ngay_tao as ngay_dat_hang,
                       phuong_thuc_thanh_toan, shipping_method as phuong_thuc_van_chuyen,
                       trang_thai_thanh_toan as payment_status,
                       phi_van_chuyen, thue as thue_vat, coupon_discount as giam_gia
                FROM don_hang
                WHERE ma_nguoi_dung = ?
                ORDER BY ngay_tao DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get order by ID.
     */
    public function getOrderById($orderId): ?object
    {
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai,
                       ngay_tao as ngay_dat_hang, phuong_thuc_thanh_toan,
                       shipping_method as phuong_thuc_van_chuyen,
                       trang_thai_thanh_toan as payment_status,
                       phi_van_chuyen, thue as thue_vat, coupon_discount as giam_gia,
                       ho_ten, so_dien_thoai, email, dia_chi_giao_hang, ghi_chu
                FROM don_hang
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get order by order code.
     */
    public function getOrderByCode(string $orderCode): ?object
    {
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai,
                       ngay_tao as ngay_dat_hang, phuong_thuc_thanh_toan,
                       shipping_method as phuong_thuc_van_chuyen,
                       trang_thai_thanh_toan as payment_status,
                       phi_van_chuyen, thue as thue_vat, coupon_discount as giam_gia
                FROM don_hang
                WHERE ma_don_hang_text = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderCode]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get order items/details.
     */
    public function getOrderDetails($orderId): array
    {
        $sql = "SELECT ct.id, ct.ma_don_hang, ct.ma_san_pham as ma_hang_hoa, ct.so_luong, ct.gia,
                       h.tenhanghoa
                FROM chi_tiet_don_hang ct
                INNER JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
                WHERE ct.ma_don_hang = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get order count, optionally filtered by user.
     */
    public function getOrderCount(?string $userId = null): int
    {
        if ($userId) {
            $sql = "SELECT COUNT(*) as count FROM don_hang WHERE ma_nguoi_dung = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM don_hang";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }

        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return (int) ($result->count ?? 0);
    }

    /**
     * Get recent orders, optionally filtered by user.
     */
    public function getRecentOrders(?string $userId = null, int $limit = 5): array
    {
        if ($userId) {
            $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, ngay_tao as ngay_dat_hang
                    FROM don_hang
                    WHERE ma_nguoi_dung = ?
                    ORDER BY ngay_tao DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
        } else {
            $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, ngay_tao as ngay_dat_hang
                    FROM don_hang
                    ORDER BY ngay_tao DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        }

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get orders by status.
     */
    public function getOrdersByStatus(string $status): array
    {
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai, ngay_tao as ngay_dat_hang
                FROM don_hang
                WHERE trang_thai = ?
                ORDER BY ngay_tao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $sql = "UPDATE don_hang SET trang_thai = ?, ngay_cap_nhat = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $orderId]);
    }

    /**
     * Get order statistics.
     */
    public function getOrderStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN trang_thai = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN trang_thai = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN trang_thai = 'shipping' THEN 1 ELSE 0 END) as shipping_orders,
                    SUM(CASE WHEN trang_thai = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN trang_thai = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(tong_tien) as total_revenue
                FROM don_hang";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new order.
     */
    public function createOrder(array $data): int
    {
        $sql = "INSERT INTO don_hang (ma_nguoi_dung, ho_ten, so_dien_thoai, email, dia_chi_giao_hang, 
                                      ghi_chu, tong_tien, phuong_thuc_thanh_toan, shipping_method,
                                      phi_van_chuyen, thue, coupon_discount, trang_thai, ngay_tao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['ma_nguoi_dung'],
            $data['ho_ten'],
            $data['so_dien_thoai'],
            $data['email'],
            $data['dia_chi_giao_hang'],
            $data['ghi_chu'] ?? null,
            $data['tong_tien'],
            $data['phuong_thuc_thanh_toan'],
            $data['shipping_method'] ?? 'standard',
            $data['phi_van_chuyen'] ?? 0,
            $data['thue'] ?? 0,
            $data['coupon_discount'] ?? 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Add order item.
     */
    public function addOrderItem(int $orderId, array $item): bool
    {
        $sql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_san_pham, so_luong, gia)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $orderId,
            $item['ma_san_pham'],
            $item['so_luong'],
            $item['gia'],
        ]);
    }
}

if (!function_exists('getOrderService')) {
    function getOrderService(): OrderService
    {
        return OrderService::getInstance();
    }
}
