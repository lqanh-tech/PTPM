<?php

require_once 'database.php';
require_once 'CustomerNotificationManager.php';

class AutoOrderProcessor
{
    private $db;
    private $notificationManager;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->notificationManager = new CustomerNotificationManager();
    }

    public function autoApprovePaymentConfirmedOrders()
    {
        try {

            $autoApproveEnabled = $this->getConfig('auto_approve_paid_orders', '1');

            if ($autoApproveEnabled !== '1') {
                return ['success' => false, 'message' => 'Tự động duyệt đã bị tắt'];
            }

            $sql = "SELECT id, ma_nguoi_dung, ma_don_hang_text, tong_tien
                    FROM don_hang
                    WHERE trang_thai = 'pending'
                    AND (trang_thai_thanh_toan = 'completed' OR trang_thai_thanh_toan = 'paid')
                    AND phuong_thuc_thanh_toan != 'cod'
                    AND (auto_approved = 0 OR auto_approved IS NULL)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $approvedCount = 0;

            foreach ($orders as $order) {
                if ($this->approveOrder($order['id'], true)) {

                    $this->notificationManager->notifyOrderApproved($order['id'], $order['ma_nguoi_dung']);
                    $this->notificationManager->notifyPaymentConfirmed($order['id'], $order['ma_nguoi_dung']);

                    $approvedCount++;

                    error_log("Auto approved order #{$order['id']} for user {$order['ma_nguoi_dung']}");
                }
            }

            return [
                'success' => true,
                'message' => "Đã tự động duyệt {$approvedCount} đơn hàng",
                'approved_count' => $approvedCount
            ];
        } catch (Exception $e) {
            error_log("Error in autoApprovePaymentConfirmedOrders: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function approveSpecificOrder($orderId, $isAutoApproved = false)
    {
        try {

            $checkSql = "SELECT id, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan
                        FROM don_hang WHERE id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$orderId]);
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
            }

            if ($order['trang_thai'] !== 'pending') {
                return ['success' => false, 'message' => 'Đơn hàng đã được xử lý'];
            }

            if ($this->approveOrder($orderId, $isAutoApproved)) {
                return [
                    'success' => true,
                    'message' => 'Đơn hàng đã được duyệt thành công',
                    'order_id' => $orderId
                ];
            } else {
                return ['success' => false, 'message' => 'Lỗi khi duyệt đơn hàng'];
            }
        } catch (Exception $e) {
            error_log("Error in approveSpecificOrder: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function approveOrder($orderId, $isAutoApproved = false)
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE don_hang
                    SET trang_thai = 'approved',
                        auto_approved = ?,
                        ngay_cap_nhat = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$isAutoApproved ? 1 : 0, $orderId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error approving order {$orderId}: " . $e->getMessage());
            return false;
        }
    }

    public function processExpiredCancelDeadlines()
    {
        try {

            $sql = "SELECT id, ma_nguoi_dung 
                    FROM don_hang 
                    WHERE trang_thai = 'pending' 
                    AND phuong_thuc_thanh_toan = 'cod'
                    AND cancel_deadline IS NOT NULL 
                    AND cancel_deadline < NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $processedCount = 0;

            foreach ($orders as $order) {

                $updateSql = "UPDATE don_hang SET cancel_deadline = NULL WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$order['id']]);

                $processedCount++;
            }

            return [
                'success' => true,
                'message' => "Đã xử lý {$processedCount} đơn hàng hết hạn hủy",
                'processed_count' => $processedCount
            ];
        } catch (Exception $e) {
            error_log("Error in processExpiredCancelDeadlines: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getOrdersRequiringManualApproval()
    {
        try {

            $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, 
                           phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao,
                           cancel_deadline
                    FROM don_hang 
                    WHERE trang_thai = 'pending' 
                    AND (
                        phuong_thuc_thanh_toan = 'cod' 
                        OR trang_thai_thanh_toan = 'pending'
                    )
                    ORDER BY ngay_tao ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $codOrders = [];
            $pendingPaymentOrders = [];

            foreach ($orders as $order) {
                if ($order['phuong_thuc_thanh_toan'] === 'cod') {
                    $codOrders[] = $order;
                } else {
                    $pendingPaymentOrders[] = $order;
                }
            }

            return [
                'success' => true,
                'cod_orders' => $codOrders,
                'pending_payment_orders' => $pendingPaymentOrders,
                'total_count' => count($orders)
            ];
        } catch (Exception $e) {
            error_log("Error in getOrdersRequiringManualApproval: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getConfig($key, $default = '')
    {
        try {
            $sql = "SELECT config_value FROM order_auto_config WHERE config_key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['config_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    public function updateConfig($key, $value, $description = '')
    {
        try {
            $sql = "INSERT INTO order_auto_config (config_key, config_value, description) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    config_value = VALUES(config_value),
                    description = VALUES(description)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$key, $value, $description]);
        } catch (Exception $e) {
            error_log("Error updating config: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderStats()
    {
        try {
            $stats = [];

            $sql = "SELECT COUNT(*) as count FROM don_hang WHERE trang_thai = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['pending_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $sql = "SELECT COUNT(*) as count FROM don_hang 
                    WHERE trang_thai = 'pending' AND phuong_thuc_thanh_toan = 'cod'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['cod_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $sql = "SELECT COUNT(*) as count FROM don_hang 
                    WHERE trang_thai = 'pending' AND trang_thai_thanh_toan = 'completed'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['paid_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $sql = "SELECT COUNT(*) as count FROM don_hang 
                    WHERE auto_approved = 1 AND DATE(ngay_cap_nhat) = CURDATE()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['auto_approved_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return ['success' => true, 'stats' => $stats];
        } catch (Exception $e) {
            error_log("Error getting order stats: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
