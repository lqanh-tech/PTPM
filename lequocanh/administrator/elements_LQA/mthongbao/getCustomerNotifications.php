<?php

/**
 * API lấy thông báo cho khách hàng
 */

header('Content-Type: application/json; charset=utf-8');

// Allow requests from the same origin (including ngrok)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Allow from any origin for ngrok compatibility
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

// Tắt hiển thị lỗi
ini_set('display_errors', 0);
error_reporting(0);

// Bắt đầu output buffering
ob_start();

try {
    require_once '../mod/sessionManager.php';
    require_once '../mod/CustomerNotificationManager.php';

    // Start session safely
    SessionManager::start();

    // Lấy tham số trước
    $action = $_GET['action'] ?? 'list';

    // Kiểm tra đăng nhập
    $userId = isset($_SESSION['USER']) ? $_SESSION['USER'] : '';

    // Debug log
    error_log("getCustomerNotifications: Session USER = " . $userId);
    error_log("getCustomerNotifications: Action = " . $action);
    error_log("getCustomerNotifications: Session ID = " . session_id());
    error_log("getCustomerNotifications: All session data = " . json_encode($_SESSION));

    if (empty($userId)) {
        throw new Exception('Chưa đăng nhập');
    }

    $notificationManager = new CustomerNotificationManager();
    $limit = (int)($_GET['limit'] ?? 20);
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';

    switch ($action) {
        case 'list':
            // Lấy danh sách thông báo
            $notifications = $notificationManager->getUserNotifications($userId, $limit, $unreadOnly);
            $unreadCount = $notificationManager->getUnreadCount($userId);

            // Format thông báo
            $formattedNotifications = [];
            foreach ($notifications as $notification) {
                $formattedNotifications[] = [
                    'id' => $notification['id'],
                    'order_id' => $notification['order_id'],
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'is_read' => (bool)$notification['is_read'],
                    'created_at' => date('d/m/Y H:i', strtotime($notification['created_at'])),
                    'icon' => getNotificationIcon($notification['type']),
                    'color' => getNotificationColor($notification['type'])
                ];
            }

            $response = [
                'success' => true,
                'notifications' => $formattedNotifications,
                'unread_count' => $unreadCount,
                'total' => count($formattedNotifications)
            ];
            break;

        case 'mark_read':
            // Đánh dấu thông báo đã đọc
            $notificationId = (int)($_POST['notification_id'] ?? 0);

            if ($notificationId > 0) {
                $success = $notificationManager->markAsRead($notificationId, $userId);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Đã đánh dấu đã đọc' : 'Có lỗi xảy ra'
                ];
            } else {
                throw new Exception('ID thông báo không hợp lệ');
            }
            break;

        case 'mark_all_read':
            // Đánh dấu tất cả thông báo đã đọc
            $success = $notificationManager->markAllAsRead($userId);
            $response = [
                'success' => $success,
                'message' => $success ? 'Đã đánh dấu tất cả đã đọc' : 'Có lỗi xảy ra'
            ];
            break;

        case 'count':
            // Chỉ lấy số lượng thông báo chưa đọc
            $unreadCount = $notificationManager->getUnreadCount($userId);
            $response = [
                'success' => true,
                'unread_count' => $unreadCount
            ];
            break;
            
        case 'delete_read':
            // Xóa tất cả thông báo đã đọc
            $success = $notificationManager->deleteReadNotifications($userId);
            $response = [
                'success' => $success,
                'message' => $success ? 'Đã xóa thông báo đã đọc' : 'Có lỗi xảy ra'
            ];
            break;
            
        case 'delete_single':
            // Xóa một thông báo cụ thể
            $notificationId = (int)($_POST['notification_id'] ?? 0);

            if ($notificationId > 0) {
                $success = $notificationManager->deleteNotification($notificationId, $userId);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Đã xóa thông báo' : 'Có lỗi xảy ra'
                ];
            } else {
                throw new Exception('ID thông báo không hợp lệ');
            }
            break;

        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Xóa output buffer và gửi JSON
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Lấy icon cho loại thông báo
 */
function getNotificationIcon($type)
{
    switch ($type) {
        case 'order_approved':
            return 'check-circle';
        case 'order_cancelled':
            return 'times-circle';
        case 'order_shipped':
            return 'truck';
        case 'order_delivered':
            return 'box-open';
        case 'payment_confirmed':
            return 'money-bill-wave';
        case 'order_created':
            return 'shopping-cart';
        case 'payment_pending':
            return 'clock';
        case 'payment_rejected':
            return 'exclamation-triangle';
        default:
            return 'bell';
    }
}

/**
 * Lấy màu cho loại thông báo
 */
function getNotificationColor($type)
{
    switch ($type) {
        case 'order_approved':
            return 'success';
        case 'order_cancelled':
            return 'danger';
        case 'order_shipped':
            return 'info';
        case 'order_delivered':
            return 'primary';
        case 'payment_confirmed':
            return 'success';
        case 'order_created':
            return 'primary';
        case 'payment_pending':
            return 'warning';
        case 'payment_rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}
