<?php
/**
 * Shipment Tracking Model - Theo dõi vận chuyển
 * MVC Pattern - Model Layer
 */

require_once __DIR__ . '/database.php';

class ShipmentTrackingModel
{
    private $db;
    private $conn;

    // Các trạng thái vận chuyển
    const STATUS_PENDING = 'pending';           // Chờ lấy hàng
    const STATUS_PICKING = 'picking';           // Đang lấy hàng
    const STATUS_PICKED = 'picked';             // Đã lấy hàng
    const STATUS_SHIPPING = 'shipping';         // Đang vận chuyển
    const STATUS_DELIVERING = 'delivering';     // Đang giao hàng
    const STATUS_DELIVERED = 'delivered';       // Giao thành công
    const STATUS_FAILED = 'failed';             // Giao thất bại
    const STATUS_RETURNED = 'returned';         // Hoàn trả
    const STATUS_CANCELLED = 'cancelled';       // Đã hủy

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Thêm lịch sử tracking
     */
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

    /**
     * Lấy lịch sử tracking theo đơn hàng
     */
    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Lấy tracking mới nhất của đơn hàng
     */
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

    /**
     * Lấy tracking theo mã vận đơn
     */
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

    /**
     * Cập nhật trạng thái vận chuyển cho đơn hàng
     */
    public function updateOrderShippingStatus($orderId, $status, $description = null)
    {
        // Thêm vào lịch sử tracking
        $this->addTracking($orderId, $status, $description);

        // Cập nhật trạng thái trong bảng don_hang
        $sql = "UPDATE don_hang SET shipping_status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $orderId]);
    }

    /**
     * Lấy tên trạng thái tiếng Việt
     */
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

    /**
     * Lấy màu badge cho trạng thái
     */
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

    /**
     * Lấy tất cả trạng thái
     */
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
