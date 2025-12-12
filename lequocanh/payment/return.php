<?php
/**
 * MoMo Return Handler - Xử lý redirect từ MoMo
 * File này chỉ xử lý logic và redirect, không hiển thị HTML
 */

// Start session
require_once '../administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

// Lấy thông tin từ URL
$orderId = $_GET['orderId'] ?? '';
$resultCode = $_GET['resultCode'] ?? '';
$message = $_GET['message'] ?? '';

// Log
error_log('MoMo Return: orderId=' . $orderId . ', resultCode=' . $resultCode);

// Xử lý theo kết quả
if ($resultCode == '0') {
    // Thanh toán thành công
    try {
        require_once '../administrator/elements_LQA/mod/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Lấy thông tin từ extraData để tìm đơn hàng
        $extraData = $_GET['extraData'] ?? '';
        $extraDataDecoded = json_decode(urldecode($extraData), true);
        
        error_log("MoMo Return - Extra Data: " . urldecode($extraData));
        error_log("MoMo Return - Decoded Extra Data: " . print_r($extraDataDecoded, true));
        
        // Tìm đơn hàng theo MoMo orderId hoặc order_code từ extraData
        $order = null;
        
        // Thử 1: Tìm theo MoMo orderId
        $sql = "SELECT id, ma_nguoi_dung FROM don_hang WHERE ma_don_hang_text = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Thử 2: Nếu không tìm thấy, thử tìm theo order_code từ extraData
        if (!$order && isset($extraDataDecoded['order_code'])) {
            $originalOrderCode = $extraDataDecoded['order_code'];
            error_log("MoMo Return - Trying with original order code: $originalOrderCode");
            
            $sql = "SELECT id, ma_nguoi_dung FROM don_hang WHERE ma_don_hang_text = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$originalOrderCode]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Thử 3: Tìm đơn hàng pending mới nhất của user từ extraData
        if (!$order && isset($extraDataDecoded['user_id'])) {
            $userId = $extraDataDecoded['user_id'];
            error_log("MoMo Return - Trying to find pending order for user: $userId");
            
            $sql = "SELECT id, ma_nguoi_dung FROM don_hang 
                    WHERE ma_nguoi_dung = ? 
                    AND trang_thai_thanh_toan = 'pending' 
                    AND phuong_thuc_thanh_toan = 'momo'
                    ORDER BY ngay_tao DESC 
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($order) {
            $dbOrderId = $order['id'];
            $userId = $order['ma_nguoi_dung'];
            
            error_log("MoMo Return - Found order: $dbOrderId for user: $userId");
            
            // Cập nhật trạng thái và lưu MoMo orderId
            $updateSql = "UPDATE don_hang SET 
                          trang_thai_thanh_toan = 'paid',
                          trang_thai = 'approved',
                          ma_don_hang_text = ?,
                          ngay_cap_nhat = NOW()
                          WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->execute([$orderId, $dbOrderId]);
            
            // Xóa giỏ hàng
            try {
                $deleteSql = "DELETE FROM tbl_giohang WHERE user_id = ?";
                $stmt = $conn->prepare($deleteSql);
                $stmt->execute([$userId]);
                error_log("Cart cleared for user: $userId");
            } catch (Exception $e) {
                error_log("Failed to clear cart: " . $e->getMessage());
            }
            
            error_log("Order processed: $dbOrderId, MoMo orderId: $orderId");
            
            // Set session
            SessionManager::set('payment_success', true);
            SessionManager::set('order_id', $dbOrderId);
            
            // Send notification to user about successful payment and order approval
            try {
                require_once '../administrator/elements_LQA/mod/CustomerNotificationManager.php';
                $notificationManager = new CustomerNotificationManager();
                
                // Gửi email đặt hàng thành công
                $notificationManager->notifyOrderSuccess($dbOrderId, $userId);
                error_log("Order success notification sent for order: $dbOrderId, user: $userId");
                
                // Gửi email xác nhận thanh toán
                $notificationManager->notifyPaymentConfirmed($dbOrderId, $userId);
                error_log("Payment confirmation notification sent for order: $dbOrderId, user: $userId");
                
                // Gửi email đơn hàng đã được duyệt (vì MoMo tự động duyệt)
                $notificationManager->notifyOrderApproved($dbOrderId, $userId);
                error_log("Order approved notification sent for order: $dbOrderId, user: $userId");
                
            } catch (Exception $e) {
                error_log("Failed to send payment notification: " . $e->getMessage());
            }
            
            // Redirect to order success page
            $redirectUrl = '../administrator/elements_LQA/mgiohang/order_success.php?order_id=' . $dbOrderId;
            error_log("MoMo Return: Redirecting to success page: $redirectUrl");
            header('Location: ' . $redirectUrl);
            exit();
        } else {
            error_log("MoMo Return - Order not found for orderId: $orderId");
        }
    } catch (Exception $e) {
        error_log("MoMo Return Error: " . $e->getMessage());
        error_log("MoMo Return Stack Trace: " . $e->getTraceAsString());
    }
}

// Nếu thất bại hoặc có lỗi, về giỏ hàng
error_log("MoMo Return: Payment failed or error occurred, redirecting to cart");
header('Location: ../administrator/elements_LQA/mgiohang/giohangView.php');
exit();
