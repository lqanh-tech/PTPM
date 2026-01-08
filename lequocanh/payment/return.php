<?php

require_once '../administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

$orderId = $_GET['orderId'] ?? '';
$resultCode = $_GET['resultCode'] ?? '';
$message = $_GET['message'] ?? '';

error_log('MoMo Return: orderId=' . $orderId . ', resultCode=' . $resultCode);

if ($resultCode == '0') {

    try {
        require_once '../administrator/elements_LQA/mod/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $extraData = $_GET['extraData'] ?? '';
        $extraDataDecoded = json_decode(urldecode($extraData), true);
        
        error_log("MoMo Return - Extra Data: " . urldecode($extraData));
        error_log("MoMo Return - Decoded Extra Data: " . print_r($extraDataDecoded, true));
        
        $order = null;
        
        $sql = "SELECT id, ma_nguoi_dung FROM don_hang WHERE ma_don_hang_text = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order && isset($extraDataDecoded['order_code'])) {
            $originalOrderCode = $extraDataDecoded['order_code'];
            error_log("MoMo Return - Trying with original order code: $originalOrderCode");
            
            $sql = "SELECT id, ma_nguoi_dung FROM don_hang WHERE ma_don_hang_text = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$originalOrderCode]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
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
            
            $updateSql = "UPDATE don_hang SET 
                          trang_thai_thanh_toan = 'paid',
                          trang_thai = 'approved',
                          ma_don_hang_text = ?,
                          ngay_cap_nhat = NOW()
                          WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->execute([$orderId, $dbOrderId]);
            
            try {
                $deleteSql = "DELETE FROM tbl_giohang WHERE user_id = ?";
                $stmt = $conn->prepare($deleteSql);
                $stmt->execute([$userId]);
                error_log("Cart cleared for user: $userId");
            } catch (Exception $e) {
                error_log("Failed to clear cart: " . $e->getMessage());
            }
            
            error_log("Order processed: $dbOrderId, MoMo orderId: $orderId");
            
            SessionManager::set('payment_success', true);
            SessionManager::set('order_id', $dbOrderId);
            
            try {
                require_once '../administrator/elements_LQA/mod/CustomerNotificationManager.php';
                $notificationManager = new CustomerNotificationManager();
                
                $notificationManager->notifyOrderSuccess($dbOrderId, $userId);
                error_log("Order success notification sent for order: $dbOrderId, user: $userId");
                
                $notificationManager->notifyPaymentConfirmed($dbOrderId, $userId);
                error_log("Payment confirmation notification sent for order: $dbOrderId, user: $userId");
                
                $notificationManager->notifyOrderApproved($dbOrderId, $userId);
                error_log("Order approved notification sent for order: $dbOrderId, user: $userId");
                
            } catch (Exception $e) {
                error_log("Failed to send payment notification: " . $e->getMessage());
            }
            
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

error_log("MoMo Return: Payment failed or error occurred, redirecting to cart");
header('Location: ../administrator/elements_LQA/mgiohang/giohangView.php');
exit();
