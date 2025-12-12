<?php
/**
 * Webhook xử lý thông báo thanh toán từ ngân hàng
 * File này nhận thông báo từ ngân hàng khi có giao dịch chuyển khoản thành công
 */

// Tắt hiển thị lỗi
ini_set('display_errors', 0);
error_reporting(0);

// Log tất cả request
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'raw_input' => file_get_contents('php://input')
];

error_log('Bank Notify Request: ' . json_encode($logData));

try {
    // Lấy dữ liệu từ request
    $data = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            $data = $_POST;
        }
    } else {
        $data = $_GET;
    }
    
    // Kiểm tra dữ liệu cần thiết
    $requiredFields = ['order_id', 'amount', 'transaction_id', 'status'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $orderId = $data['order_id'];
    $amount = (float)$data['amount'];
    $transactionId = $data['transaction_id'];
    $status = $data['status'];
    $bankCode = $data['bank_code'] ?? 'UNKNOWN';
    
    // Verify signature nếu có
    if (isset($data['signature'])) {
        $expectedSignature = generateBankSignature($data);
        if ($data['signature'] !== $expectedSignature) {
            throw new Exception('Invalid signature');
        }
    }
    
    error_log("Bank Payment Notification: OrderID=$orderId, Amount=$amount, Status=$status, TransID=$transactionId");
    
    if (strtoupper($status) === 'SUCCESS' || strtoupper($status) === 'COMPLETED') {
        // Thanh toán thành công
        error_log("Bank Payment Success: OrderID=$orderId, TransID=$transactionId");
        
        // Cập nhật trạng thái đơn hàng trong database
        try {
            require_once '../administrator/elements_LQA/mod/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Cập nhật trạng thái thanh toán thành 'completed'
            $updateSql = "UPDATE don_hang SET
                         trang_thai_thanh_toan = 'completed',
                         phuong_thuc_thanh_toan = 'bank_transfer',
                         ngay_cap_nhat = NOW()
                         WHERE ma_don_hang_text = ?";
            
            $stmt = $conn->prepare($updateSql);
            $result = $stmt->execute([$orderId]);
            
            if ($result) {
                error_log("Order payment status updated successfully: $orderId");
                
                // Lấy thông tin đơn hàng
                $orderSql = "SELECT * FROM don_hang WHERE ma_don_hang_text = ?";
                $orderStmt = $conn->prepare($orderSql);
                $orderStmt->execute([$orderId]);
                $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    // Tự động duyệt đơn hàng ngay lập tức
                    require_once '../administrator/elements_LQA/mod/AutoOrderProcessor.php';
                    $processor = new AutoOrderProcessor();
                    
                    // Duyệt đơn hàng cụ thể này
                    $approveResult = $processor->approveSpecificOrder($order['id'], true);
                    
                    if ($approveResult['success']) {
                        error_log("Auto approved order #{$order['id']} after bank payment");
                        
                        // Gửi thông báo đặt hàng thành công và duyệt đơn hàng
                        require_once '../administrator/elements_LQA/mod/CustomerNotificationManager.php';
                        $notificationManager = new CustomerNotificationManager();
                        
                        if ($order['ma_nguoi_dung']) {
                            // Gửi email đặt hàng thành công
                            $notificationManager->notifyOrderSuccess($order['id'], $order['ma_nguoi_dung']);
                            error_log("Order success notification sent for order: {$order['id']}");
                            
                            // Gửi email xác nhận thanh toán
                            $notificationManager->notifyPaymentConfirmed($order['id'], $order['ma_nguoi_dung']);
                            error_log("Payment confirmed notification sent for order: {$order['id']}");
                            
                            // Gửi email đơn hàng đã được duyệt
                            $notificationManager->notifyOrderApproved($order['id'], $order['ma_nguoi_dung']);
                            error_log("Order approved notification sent for order: {$order['id']}");
                        }
                    } else {
                        error_log("Failed to auto approve order #{$order['id']}: " . $approveResult['message']);
                    }
                }
            } else {
                error_log("Failed to update order payment status: $orderId");
            }
        } catch (Exception $e) {
            error_log("Error updating order status: " . $e->getMessage());
        }
        
        // Response thành công
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment processed successfully'
        ]);
    } else {
        // Thanh toán thất bại
        error_log("Bank Payment Failed: OrderID=$orderId, Status=$status, TransID=$transactionId");
        
        // Response thành công (đã nhận được thông báo)
        http_response_code(200);
        echo json_encode([
            'status' => 'received',
            'message' => 'Payment failure notification received'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Bank Notify Error: ' . $e->getMessage());
    
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Tạo signature cho xác thực từ ngân hàng
 */
function generateBankSignature($data) {
    // Thay đổi secret key này theo cấu hình thực tế của ngân hàng
    $secretKey = 'YOUR_BANK_SECRET_KEY';
    
    // Tạo string để ký
    $signString = $data['order_id'] . '|' . $data['amount'] . '|' . $data['transaction_id'] . '|' . $data['status'];
    
    // Tạo signature
    return hash_hmac('sha256', $signString, $secretKey);
}

/**
 * Lấy tất cả headers
 */
function getallheaders() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}
?>
