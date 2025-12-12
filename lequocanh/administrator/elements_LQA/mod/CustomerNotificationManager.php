<?php

/**
 * Customer Notification Manager
 * Quản lý thông báo cho khách hàng
 */

require_once 'database.php';

class CustomerNotificationManager
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                error_log("CustomerNotificationManager: Database connection is null");
                throw new Exception("Database connection failed");
            }
            
            // Tự động tạo bảng nếu chưa tồn tại
            $this->ensureTableExists();
        } catch (Exception $e) {
            error_log("CustomerNotificationManager constructor error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Đảm bảo bảng customer_notifications tồn tại
     */
    private function ensureTableExists()
    {
        try {
            $checkTableSql = "SHOW TABLES LIKE 'customer_notifications'";
            $stmt = $this->db->prepare($checkTableSql);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Tạo bảng customer_notifications
                $createTableSql = "CREATE TABLE customer_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(100) NOT NULL,
                    order_id INT DEFAULT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'general',
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    read_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_order_id (order_id),
                    INDEX idx_is_read (is_read),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->db->exec($createTableSql);
                error_log("CustomerNotificationManager: Created customer_notifications table");
            }
        } catch (Exception $e) {
            error_log("CustomerNotificationManager: Error ensuring table exists: " . $e->getMessage());
        }
    }

    /**
     * Gửi thông báo khi đơn hàng được duyệt
     */
    public function notifyOrderApproved($orderId, $userId)
    {
        $order = $this->getOrderInfo($orderId);
        if (!$order) {
            error_log("CustomerNotificationManager: Order not found for ID: $orderId");
            return false;
        }

        // Debug log
        error_log("CustomerNotificationManager: Creating notification for order $orderId, user: $userId");

        $title = "✅ Đơn hàng #{$orderId} đã được duyệt";
        
        // Tạo link xem hóa đơn và đánh giá
        $invoiceLink = "/lequocanh/customer/order_invoice.php?order_id={$orderId}";
        
        $message = "Đơn hàng #{$order['ma_don_hang_text']} của bạn đã được duyệt và đang được chuẩn bị. " .
            "Tổng tiền: " . number_format($order['tong_tien'], 0, ',', '.') . " đ. " .
            "Bạn có thể xem hóa đơn và đánh giá sản phẩm tại đây: {$invoiceLink}";

        $result = $this->createInternalNotification($userId, $orderId, 'order_approved', $title, $message);

        // Gửi email thông báo
        $this->sendEmailNotification($orderId, $userId, 'approved');

        // Debug log
        error_log("CustomerNotificationManager: Notification creation result: " . ($result ? 'success' : 'failed'));

        return $result;
    }

    /**
     * Gửi thông báo khi đơn hàng bị hủy
     */
    public function notifyOrderCancelled($orderId, $userId, $reason = '')
    {
        $order = $this->getOrderInfo($orderId);
        if (!$order) return false;

        $title = "❌ Đơn hàng #{$orderId} đã bị hủy";
        $message = "Đơn hàng #{$order['ma_don_hang_text']} của bạn đã bị hủy. " .
            ($reason ? "Lý do: $reason. " : "") .
            "Nếu bạn đã thanh toán, chúng tôi sẽ hoàn tiền trong 1-3 ngày làm việc.";

        $result = $this->createInternalNotification($userId, $orderId, 'order_cancelled', $title, $message);
        
        // Gửi email thông báo
        $this->sendEmailNotification($orderId, $userId, 'cancelled', $reason);
        
        return $result;
    }

    /**
     * Gửi thông báo xác nhận thanh toán
     */
    public function notifyPaymentConfirmed($orderId, $userId)
    {
        $order = $this->getOrderInfo($orderId);
        if (!$order) return false;

        $title = "💰 Thanh toán đã được xác nhận";
        $message = "Thanh toán cho đơn hàng #{$order['ma_don_hang_text']} đã được xác nhận. " .
            "Đơn hàng sẽ được xử lý và giao trong thời gian sớm nhất.";

        $result = $this->createInternalNotification($userId, $orderId, 'payment_confirmed', $title, $message);
        
        // Gửi email thông báo
        $this->sendEmailNotification($orderId, $userId, 'payment');
        
        return $result;
    }

    /**
     * Tạo thông báo mới (public method for external use)
     */
    public function createNotification($userId, $title, $message, $type = 'general', $orderId = null)
    {
        try {
            // Debug log
            error_log("CustomerNotificationManager: Creating notification - User: $userId, Type: $type, Order: $orderId, Title: $title");

            $sql = "INSERT INTO customer_notifications (user_id, order_id, type, title, message) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $orderId, $type, $title, $message]);

            if ($result) {
                error_log("CustomerNotificationManager: Notification inserted successfully");
            } else {
                error_log("CustomerNotificationManager: Failed to insert notification");
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tạo thông báo nội bộ (private method for internal use)
     */
    private function createInternalNotification($userId, $orderId, $type, $title, $message)
    {
        return $this->createNotification($userId, $title, $message, $type, $orderId);
    }

    /**
     * Lấy thông báo của user
     */
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false)
    {
        try {
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];

            if ($unreadOnly) {
                $whereClause .= " AND is_read = 0";
            }

            // Ensure limit is integer and safe
            $limit = (int)$limit;
            if ($limit <= 0) $limit = 20;
            if ($limit > 100) $limit = 100;

            $sql = "SELECT * FROM customer_notifications
                    $whereClause
                    ORDER BY created_at DESC
                    LIMIT $limit";

            // Debug log
            error_log("getUserNotifications SQL: " . $sql);
            error_log("getUserNotifications params: " . json_encode($params));

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug log
            error_log("getUserNotifications result count: " . count($result));

            return $result;
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Đếm thông báo chưa đọc
     */
    public function getUnreadCount($userId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM customer_notifications 
                    WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error counting unread notifications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead($notificationId, $userId)
    {
        try {
            $sql = "UPDATE customer_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    public function markAllAsRead($userId)
    {
        try {
            $sql = "UPDATE customer_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa tất cả thông báo đã đọc của user
     */
    public function deleteReadNotifications($userId)
    {
        try {
            $sql = "DELETE FROM customer_notifications 
                    WHERE user_id = ? AND is_read = 1";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId]);
            error_log("deleteReadNotifications: user=$userId, result=" . ($result ? 'success' : 'failed'));
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting read notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa một thông báo cụ thể của user
     */
    public function deleteNotification($notificationId, $userId)
    {
        try {
            $sql = "DELETE FROM customer_notifications 
                    WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$notificationId, $userId]);
            error_log("deleteNotification: id=$notificationId, user=$userId, result=" . ($result ? 'success' : 'failed'));
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi email thông báo
     * LUÔN lấy email mới nhất từ database để đảm bảo gửi đúng email hiện tại
     */
    private function sendEmailNotification($orderId, $userId, $type, $reason = '')
    {
        try {
            // QUAN TRỌNG: Luôn lấy email MỚI NHẤT từ database
            // Không cache email vì user có thể thay đổi email bất cứ lúc nào
            $sql = "SELECT email, hoten FROM user WHERE username = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                error_log("CustomerNotificationManager: User not found - $userId");
                return false;
            }
            
            // Kiểm tra email
            if (empty($user['email'])) {
                error_log("CustomerNotificationManager: No email for user $userId ({$user['hoten']})");
                error_log("CustomerNotificationManager: User needs to update email in profile");
                return false;
            }
            
            // Validate email format
            if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                error_log("CustomerNotificationManager: Invalid email format for user $userId: {$user['email']}");
                return false;
            }
            
            error_log("CustomerNotificationManager: Sending email to {$user['email']} (User: $userId, Type: $type)");
            
            // Load EmailService
            require_once __DIR__ . '/EmailService.php';
            $emailService = new EmailService();
            
            // Gửi email theo loại
            $result = false;
            switch ($type) {
                case 'approved':
                    $result = $emailService->sendOrderApprovedEmail($orderId, $user['email']);
                    break;
                case 'cancelled':
                    $result = $emailService->sendOrderCancelledEmail($orderId, $user['email'], $reason);
                    break;
                case 'payment':
                    $result = $emailService->sendPaymentConfirmedEmail($orderId, $user['email']);
                    break;
                case 'success':
                    $result = $emailService->sendOrderSuccessEmail($orderId, $user['email']);
                    break;
                default:
                    error_log("CustomerNotificationManager: Unknown email type: $type");
                    $result = false;
            }
            
            if ($result) {
                error_log("CustomerNotificationManager: ✅ Email sent successfully - Type: $type, Order: $orderId, To: {$user['email']}");
            } else {
                error_log("CustomerNotificationManager: ❌ Failed to send email - Type: $type, Order: $orderId, To: {$user['email']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("CustomerNotificationManager: Error sending email - " . $e->getMessage());
            error_log("CustomerNotificationManager: Stack trace - " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Gửi thông báo đơn hàng thành công (khi vừa đặt hàng)
     */
    public function notifyOrderSuccess($orderId, $userId)
    {
        $order = $this->getOrderInfo($orderId);
        if (!$order) return false;

        $title = "✅ Đơn hàng #{$orderId} đã được đặt thành công";
        $message = "Đơn hàng #{$order['ma_don_hang_text']} của bạn đã được tiếp nhận. " .
            "Tổng tiền: " . number_format($order['tong_tien'], 0, ',', '.') . " đ. " .
            "Chúng tôi sẽ xử lý đơn hàng trong thời gian sớm nhất.";

        $result = $this->createInternalNotification($userId, $orderId, 'order_success', $title, $message);
        
        // Gửi email thông báo
        $this->sendEmailNotification($orderId, $userId, 'success');
        
        return $result;
    }
    
    /**
     * Lấy thông tin đơn hàng
     */
    private function getOrderInfo($orderId)
    {
        try {
            $sql = "SELECT * FROM don_hang WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting order info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kiểm tra đơn hàng có thể hủy không
     */
    public function canCancelOrder($orderId, $userId)
    {
        try {
            $sql = "SELECT * FROM don_hang 
                    WHERE id = ? AND ma_nguoi_dung = ? 
                    AND trang_thai = 'pending' 
                    AND (cancel_deadline IS NULL OR cancel_deadline > NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Exception $e) {
            error_log("Error checking cancel permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hủy đơn hàng với lý do
     */
    public function cancelOrderWithReason($orderId, $userId, $reasonCode, $reasonText, $customReason = '')
    {
        try {
            $this->db->beginTransaction();

            // Kiểm tra quyền hủy
            if (!$this->canCancelOrder($orderId, $userId)) {
                throw new Exception("Không thể hủy đơn hàng này");
            }

            // Cập nhật trạng thái đơn hàng
            $updateOrderSql = "UPDATE don_hang SET trang_thai = 'cancelled' WHERE id = ?";
            $stmt = $this->db->prepare($updateOrderSql);
            $stmt->execute([$orderId]);

            // Lưu lý do hủy
            $insertReasonSql = "INSERT INTO order_cancel_reasons 
                               (order_id, user_id, reason_code, reason_text, custom_reason) 
                               VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($insertReasonSql);
            $stmt->execute([$orderId, $userId, $reasonCode, $reasonText, $customReason]);

            // Gửi thông báo
            $this->notifyOrderCancelled($orderId, $userId, $reasonText);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error cancelling order: " . $e->getMessage());
            return false;
        }
    }
}
