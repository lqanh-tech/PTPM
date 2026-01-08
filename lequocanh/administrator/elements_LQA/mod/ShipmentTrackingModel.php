<?php

require_once __DIR__ . '/database.php';

class ShipmentTrackingModel
{
    private $db;
    private $conn;

    const STATUS_PENDING = 'pending';
    const STATUS_PICKING = 'picking';
    const STATUS_PICKED = 'picked';
    const STATUS_SHIPPING = 'shipping';
    const STATUS_DELIVERING = 'delivering';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function addTracking($orderId, $status, $description = null, $location = null, $trackingCode = null, $carrier = null)
    {
        $sql = "INSERT INTO shipment_tracking (order_id, tracking_code, carrier, status, status_description, location) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $orderId,
            $trackingCode,
            $carrier,
            $status,
            $description,
            $location
        ]);
    }

    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getLatestByOrderId($orderId)
    {
        $sql = "SELECT * FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getByTrackingCode($trackingCode)
    {
        $sql = "SELECT st.*, dh.ma_don_hang_text, dh.ma_nguoi_dung 
                FROM shipment_tracking st
                LEFT JOIN don_hang dh ON st.order_id = dh.id
                WHERE st.tracking_code = ? 
                ORDER BY st.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$trackingCode]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function updateOrderShippingStatus($orderId, $status, $description = null)
    {

        $this->addTracking($orderId, $status, $description);

        $sql = "UPDATE don_hang SET shipping_status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $orderId]);
    }

    public static function getStatusName($status)
    {
        $statusNames = [
            self::STATUS_PENDING => 'Chờ lấy hàng',
            self::STATUS_PICKING => 'Đang lấy hàng',
            self::STATUS_PICKED => 'Đã lấy hàng',
            self::STATUS_SHIPPING => 'Đang vận chuyển',
            self::STATUS_DELIVERING => 'Đang giao hàng',
            self::STATUS_DELIVERED => 'Giao thành công',
            self::STATUS_FAILED => 'Giao thất bại',
            self::STATUS_RETURNED => 'Hoàn trả',
            self::STATUS_CANCELLED => 'Đã hủy'
        ];

        return $statusNames[$status] ?? $status;
    }

    public static function getStatusBadgeClass($status)
    {
        $badgeClasses = [
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_PICKING => 'badge-info',
            self::STATUS_PICKED => 'badge-info',
            self::STATUS_SHIPPING => 'badge-primary',
            self::STATUS_DELIVERING => 'badge-primary',
            self::STATUS_DELIVERED => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_RETURNED => 'badge-secondary',
            self::STATUS_CANCELLED => 'badge-dark'
        ];

        return $badgeClasses[$status] ?? 'badge-secondary';
    }

    public static function getAllStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PICKING,
            self::STATUS_PICKED,
            self::STATUS_SHIPPING,
            self::STATUS_DELIVERING,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_RETURNED,
            self::STATUS_CANCELLED
        ];
    }
}
