<?php

require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/CustomerNotificationManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$isAdmin = isset($_SESSION['ADMIN']);
$username = $isAdmin ? $_SESSION['ADMIN'] : $_SESSION['USER'];

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'ID đơn hàng không hợp lệ';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'giohangView.php'));
    exit();
}

try {

    $sql = "SELECT * FROM don_hang WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng');
    }
    
    if (!$isAdmin && $order['ma_nguoi_dung'] !== $username) {
        throw new Exception('Bạn không có quyền thực hiện thao tác này');
    }
    
    $notificationManager = new CustomerNotificationManager();
    $isCOD = ($order['phuong_thuc_thanh_toan'] === 'cod');
    
    switch ($action) {
        case 'admin_confirm_delivery':

            if (!$isAdmin) {
                throw new Exception('Chỉ admin mới có thể thực hiện thao tác này');
            }
            
            if ($order['trang_thai'] !== 'approved') {
                throw new Exception('Đơn hàng chưa được duyệt, không thể xác nhận giao hàng');
            }
            
            $updateSql = "UPDATE don_hang SET 
                          trang_thai = 'delivered',
                          ngay_giao_hang = NOW(),
                          ngay_cap_nhat = NOW()
                          WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$orderId]);
            
            $title = "📦 Đơn hàng #{$orderId} đã được giao";
            if ($isCOD) {
                $message = "Đơn hàng #{$order['ma_don_hang_text']} đã được giao đến bạn. Vui lòng xác nhận đã nhận hàng và thanh toán.";
            } else {
                $message = "Đơn hàng #{$order['ma_don_hang_text']} đã được giao đến bạn. Vui lòng xác nhận đã nhận hàng.";
            }
            $notificationManager->createNotification($order['ma_nguoi_dung'], $title, $message, 'order_delivered', $orderId);
            
            $_SESSION['success_message'] = "Đã xác nhận giao hàng cho đơn #{$orderId}. Chờ khách hàng xác nhận nhận hàng.";
            break;
            
        case 'customer_confirm_received':

            if ($isAdmin) {
                throw new Exception('Khách hàng cần tự xác nhận nhận hàng');
            }
            
            if ($order['trang_thai'] !== 'delivered' && $order['trang_thai'] !== 'approved') {
                throw new Exception('Đơn hàng chưa được giao, không thể xác nhận nhận hàng');
            }
            
            if ($isCOD) {
                $updateSql = "UPDATE don_hang SET 
                              trang_thai = 'completed',
                              trang_thai_thanh_toan = 'paid',
                              ngay_nhan_hang = NOW(),
                              ngay_cap_nhat = NOW()
                              WHERE id = ?";
            } else {

                $updateSql = "UPDATE don_hang SET 
                              trang_thai = 'completed',
                              ngay_nhan_hang = NOW(),
                              ngay_cap_nhat = NOW()
                              WHERE id = ?";
            }
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$orderId]);
            
            $notificationManager->notifyOrderSuccess($orderId, $order['ma_nguoi_dung']);
            
            $paymentMethod = $isCOD ? 'COD' : ($order['phuong_thuc_thanh_toan'] === 'momo' ? 'MoMo' : 'Chuyển khoản');
            $adminTitle = "✅ Đơn hàng #{$orderId} đã hoàn tất";
            $adminMessage = "Khách hàng {$order['ma_nguoi_dung']} đã xác nhận nhận hàng cho đơn #{$order['ma_don_hang_text']} ({$paymentMethod}).";

            $_SESSION['success_message'] = "Cảm ơn bạn đã xác nhận nhận hàng! Đơn hàng #{$orderId} đã hoàn tất.";
            break;
            
        case 'admin_force_complete':

            if (!$isAdmin) {
                throw new Exception('Chỉ admin mới có thể thực hiện thao tác này');
            }
            
            if (!in_array($order['trang_thai'], ['approved', 'delivered'])) {
                throw new Exception('Đơn hàng không ở trạng thái có thể hoàn tất');
            }
            
            if ($isCOD) {
                $updateSql = "UPDATE don_hang SET 
                              trang_thai = 'completed',
                              trang_thai_thanh_toan = 'paid',
                              ngay_nhan_hang = NOW(),
                              ngay_cap_nhat = NOW(),
                              ghi_chu_admin = CONCAT(IFNULL(ghi_chu_admin, ''), '\n[', NOW(), '] Admin xác nhận hoàn tất đơn hàng')
                              WHERE id = ?";
            } else {
                $updateSql = "UPDATE don_hang SET 
                              trang_thai = 'completed',
                              ngay_nhan_hang = NOW(),
                              ngay_cap_nhat = NOW(),
                              ghi_chu_admin = CONCAT(IFNULL(ghi_chu_admin, ''), '\n[', NOW(), '] Admin xác nhận hoàn tất đơn hàng')
                              WHERE id = ?";
            }
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$orderId]);
            
            $notificationManager->notifyOrderSuccess($orderId, $order['ma_nguoi_dung']);
            
            $_SESSION['success_message'] = "Đã xác nhận hoàn tất đơn hàng #{$orderId}.";
            break;
            
        default:
            throw new Exception('Hành động không hợp lệ');
    }
    
    error_log("COD Delivery: Action=$action, OrderID=$orderId, User=$username, Success");
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    error_log("COD Delivery Error: " . $e->getMessage());
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'giohangView.php';
header('Location: ' . $referer);
exit();
