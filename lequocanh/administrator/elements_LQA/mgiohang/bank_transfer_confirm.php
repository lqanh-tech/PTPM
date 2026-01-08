<?php

session_start();
require_once '../mod/database.php';
require_once '../mod/CustomerNotificationManager.php';

header('Content-Type: application/json');

try {

    if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['STAFF'])) {
        throw new Exception('Không có quyền truy cập');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        $data = $_POST;
    }

    $orderId = $data['order_id'] ?? null;
    $action = $data['action'] ?? null;

    if (!$orderId || !$action) {
        throw new Exception('Thiếu thông tin đơn hàng hoặc hành động');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $orderSql = "SELECT * FROM don_hang WHERE id = ? OR ma_don_hang_text = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId, $orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng');
    }

    $conn->beginTransaction();

    if ($action === 'confirm_payment') {

        $updateSql = "UPDATE don_hang SET 
                     trang_thai_thanh_toan = 'completed',
                     ngay_cap_nhat = NOW()
                     WHERE id = ?";
        
        $stmt = $conn->prepare($updateSql);
        $result = $stmt->execute([$order['id']]);

        if ($result) {

            $notificationManager = new CustomerNotificationManager();
            if ($order['ma_nguoi_dung']) {
                $notificationManager->notifyPaymentConfirmed($order['id'], $order['ma_nguoi_dung']);
            }

            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xác nhận thanh toán chuyển khoản thành công',
                'order_id' => $order['ma_don_hang_text']
            ]);
        } else {
            throw new Exception('Không thể cập nhật trạng thái thanh toán');
        }

    } elseif ($action === 'reject_payment') {

        $updateSql = "UPDATE don_hang SET 
                     trang_thai_thanh_toan = 'failed',
                     ngay_cap_nhat = NOW()
                     WHERE id = ?";
        
        $stmt = $conn->prepare($updateSql);
        $result = $stmt->execute([$order['id']]);

        if ($result) {

            $notificationManager = new CustomerNotificationManager();
            if ($order['ma_nguoi_dung']) {
                $title = "❌ Thanh toán bị từ chối";
                $message = "Thanh toán cho đơn hàng #{$order['ma_don_hang_text']} bị từ chối. " .
                          "Vui lòng liên hệ để được hỗ trợ.";
                $notificationManager->createNotification($order['ma_nguoi_dung'], $title, $message, 'payment_rejected');
            }

            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã từ chối thanh toán',
                'order_id' => $order['ma_don_hang_text']
            ]);
        } else {
            throw new Exception('Không thể cập nhật trạng thái thanh toán');
        }

    } else {
        throw new Exception('Hành động không hợp lệ');
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    error_log("Bank Transfer Confirm Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
