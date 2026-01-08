<?php

ini_set('display_errors', 0);
error_reporting(0);

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
    
    if (isset($data['signature'])) {
        $expectedSignature = generateBankSignature($data);
        if ($data['signature'] !== $expectedSignature) {
            throw new Exception('Invalid signature');
        }
    }
    
    error_log("Bank Payment Notification: OrderID=$orderId, Amount=$amount, Status=$status, TransID=$transactionId");
    
    if (strtoupper($status) === 'SUCCESS' || strtoupper($status) === 'COMPLETED') {

        error_log("Bank Payment Success: OrderID=$orderId, TransID=$transactionId");
        
        try {
            require_once '../administrator/elements_LQA/mod/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $updateSql = "UPDATE don_hang SET
                         trang_thai_thanh_toan = 'completed',
                         phuong_thuc_thanh_toan = 'bank_transfer',
                         ngay_cap_nhat = NOW()
                         WHERE ma_don_hang_text = ?";
            
            $stmt = $conn->prepare($updateSql);
            $result = $stmt->execute([$orderId]);
            
            if ($result) {
                error_log("Order payment status updated successfully: $orderId");
                
                $orderSql = "SELECT * FROM don_hang WHERE ma_don_hang_text = ?";
                $orderStmt = $conn->prepare($orderSql);
                $orderStmt->execute([$orderId]);
                $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {

                    require_once '../administrator/elements_LQA/mod/AutoOrderProcessor.php';
                    $processor = new AutoOrderProcessor();
                    
                    $approveResult = $processor->approveSpecificOrder($order['id'], true);
                    
                    if ($approveResult['success']) {
                        error_log("Auto approved order #{$order['id']} after bank payment");
                        
                        require_once '../administrator/elements_LQA/mod/CustomerNotificationManager.php';
                        $notificationManager = new CustomerNotificationManager();
                        
                        if ($order['ma_nguoi_dung']) {

                            $notificationManager->notifyOrderSuccess($order['id'], $order['ma_nguoi_dung']);
                            error_log("Order success notification sent for order: {$order['id']}");
                            
                            $notificationManager->notifyPaymentConfirmed($order['id'], $order['ma_nguoi_dung']);
                            error_log("Payment confirmed notification sent for order: {$order['id']}");
                            
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
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment processed successfully'
        ]);
    } else {

        error_log("Bank Payment Failed: OrderID=$orderId, Status=$status, TransID=$transactionId");
        
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

function generateBankSignature($data) {

    $secretKey = 'YOUR_BANK_SECRET_KEY';
    
    $signString = $data['order_id'] . '|' . $data['amount'] . '|' . $data['transaction_id'] . '|' . $data['status'];
    
    return hash_hmac('sha256', $signString, $secretKey);
}

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
