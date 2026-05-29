<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/OrderRepositoryInterface.php';

class OrderRepository implements OrderRepositoryInterface
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = "trang_thai = :status";
                $params['status'] = $filters['status'];
            }
            if (!empty($filters['user_id'])) {
                $where[] = "ma_nguoi_dung = :user_id";
                $params['user_id'] = $filters['user_id'];
            }

            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $limit = !empty($filters['limit']) ? intval($filters['limit']) : 100;

            $sql = "SELECT id, ma_don_hang, ma_don_hang_text, ma_nguoi_dung, ho_ten, so_dien_thoai, email, dia_chi_giao_hang, ghi_chu, tong_tien, trang_thai, phuong_thuc_thanh_toan, shipping_method, phi_van_chuyen, thue, coupon_discount, trang_thai_thanh_toan, ngay_tao, ngay_cap_nhat FROM don_hang $whereSQL ORDER BY ngay_tao DESC LIMIT $limit";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderRepository::getAll error: " . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, ma_don_hang, ma_don_hang_text, ma_nguoi_dung, ho_ten, so_dien_thoai, email, dia_chi_giao_hang, ghi_chu, tong_tien, trang_thai, phuong_thuc_thanh_toan, shipping_method, phi_van_chuyen, thue, coupon_discount, trang_thai_thanh_toan, ngay_tao, ngay_cap_nhat FROM don_hang WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("OrderRepository::getById error: " . $e->getMessage());
            return null;
        }
    }

    public function getByUser(string $userId): array
    {
        return $this->getAll(['user_id' => $userId]);
    }

    public function getByStatus(string $status): array
    {
        return $this->getAll(['status' => $status]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        try {
            $validStatuses = ['pending', 'approved', 'delivered', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }

            $sql = "UPDATE don_hang SET trang_thai = :status, ngay_cap_nhat = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['status' => $status, 'id' => $id]);
        } catch (PDOException $e) {
            error_log("OrderRepository::updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    public function getItems(int $orderId): array
    {
        try {
            $sql = "SELECT ct.*, h.tenhanghoa
                    FROM chi_tiet_don_hang ct
                    LEFT JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
                    WHERE ct.ma_don_hang = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderRepository::getItems error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatistics(): array
    {
        try {
            $sql = "SELECT
                        trang_thai,
                        COUNT(*) as count
                    FROM don_hang
                    GROUP BY trang_thai";
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = [
                'pending' => 0,
                'approved' => 0,
                'delivered' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];
            foreach ($rows as $row) {
                $stats[$row['trang_thai']] = intval($row['count']);
            }
            return $stats;
        } catch (PDOException $e) {
            error_log("OrderRepository::getStatistics error: " . $e->getMessage());
            return [];
        }
    }

    public function getRevenueByDate(string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT DATE(ngay_tao) as ngay,
                           COUNT(*) as so_don,
                           SUM(tong_tien) as doanh_thu
                    FROM don_hang
                    WHERE trang_thai IN ('approved', 'delivered', 'completed')
                    AND DATE(ngay_tao) BETWEEN :start AND :end
                    GROUP BY DATE(ngay_tao)
                    ORDER BY ngay";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['start' => $startDate, 'end' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderRepository::getRevenueByDate error: " . $e->getMessage());
            return [];
        }
    }

    public function search(string $keyword): array
    {
        try {
            $sql = "SELECT id, ma_don_hang, ma_don_hang_text, ma_nguoi_dung, ho_ten, so_dien_thoai, email, dia_chi_giao_hang, ghi_chu, tong_tien, trang_thai, phuong_thuc_thanh_toan, shipping_method, phi_van_chuyen, thue, coupon_discount, trang_thai_thanh_toan, ngay_tao, ngay_cap_nhat FROM don_hang
                    WHERE ten_nguoi_nhan LIKE :kw OR ma_nguoi_dung LIKE :kw2 OR sdt_nguoi_nhan LIKE :kw3
                    ORDER BY ngay_tao DESC LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['kw' => "%$keyword%", 'kw2' => "%$keyword%", 'kw3' => "%$keyword%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderRepository::search error: " . $e->getMessage());
            return [];
        }
    }
}
