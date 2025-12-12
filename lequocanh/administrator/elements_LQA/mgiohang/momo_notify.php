<?php

/**
 * MoMo IPN Handler
 * Xử lý thông báo thanh toán từ MoMo (Instant Payment Notification)
 * Dựa trên official MoMo PHP SDK - ipn_momo.php
 */

require_once __DIR__ . '/../mod/database.php';

// MoMo configuration
$partnerCode = 'MOMO';
$accessKey = 'F8BBA842ECF85';
$secretKey = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';

// Log all incoming data
$logData = [
    'GET' => $_GET,
    'POST' => $_POST,
    'RAW_INPUT' => file_get_contents('php://input')
];
logMoMoTransaction('IPN_RECEIVED', $logData);

// Get IPN data from POST
$partnerCode = $_POST["partnerCode"] ?? '';
$orderId = $_POST["orderId"] ?? '';
$requestId = $_POST["requestId"] ?? '';
$amount = $_POST["amount"] ?? '';
$orderInfo = $_POST["orderInfo"] ?? '';
$orderType = $_POST["orderType"] ?? '';
$transId = $_POST["transId"] ?? '';
$resultCode = $_POST["resultCode"] ?? '';
$message = $_POST["message"] ?? '';
$payType = $_POST["payType"] ?? '';
$responseTime = $_POST["responseTime"] ?? '';
$extraData = $_POST["extraData"] ?? '';
$signature = $_POST["signature"] ?? '';

// Verify signature
$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;

$partnerSignature = hash_hmac("sha256", $rawHash, $secretKey);

$response = [
    'partnerCode' => $partnerCode,
    'requestId' => $requestId,
    'orderId' => $orderId,
    'resultCode' => 1,
    'message' => 'Signature verification failed'
];

if ($signature == $partnerSignature) {
    // Signature valid, process the payment
    $response['resultCode'] = 0;
    $response['message'] = 'Success';

    // Update order status in database
    try {
        $db = Database::getInstance()->getConnection();

        if ($resultCode == 0) {
            // Payment successful - Kiểm tra xem đơn hàng đã tồn tại chưa
            $checkOrderSql = "SELECT id, ma_nguoi_dung FROM don_hang WHERE ma_don_hang_text = ?";
            $checkStmt = $db->prepare($checkOrderSql);
            $checkStmt->execute([$orderId]);
            $existingOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingOrder) {
                // Cập nhật đơn hàng đã tồn tại
                $updateSql = "UPDATE don_hang SET
                             trang_thai_thanh_toan = 'paid',
                             trang_thai = 'approved',
                             phuong_thuc_thanh_toan = 'momo',
                             ngay_cap_nhat = NOW()
                             WHERE ma_don_hang_text = ?";

                $stmt = $db->prepare($updateSql);
                $result = $stmt->execute([$orderId]);
                
                // GỬI THÔNG BÁO ĐẾN KHÁCH HÀNG
                try {
                    require_once __DIR__ . '/../mod/CustomerNotificationManager.php';
                    $notificationManager = new CustomerNotificationManager();
                    $notificationManager->notifyPaymentConfirmed($existingOrder['id'], $existingOrder['ma_nguoi_dung']);
                    error_log("MoMo IPN - Notification sent for order " . $existingOrder['id']);
                } catch (Exception $notifError) {
                    error_log("MoMo IPN - Error sending notification: " . $notifError->getMessage());
                }
            } else {
                // Tạo đơn hàng mới từ session hoặc extraData
                $extraData = json_decode(base64_decode($_POST['extraData'] ?? ''), true);
                $userId = $extraData['user_id'] ?? 'guest';
                $shippingAddress = $extraData['shipping_address'] ?? 'Không có địa chỉ';

                $insertSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, dia_chi_giao_hang,
                             tong_tien, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan,
                             ngay_tao, ngay_cap_nhat)
                             VALUES (?, ?, ?, ?, 'pending', 'momo', 'paid', NOW(), NOW())";

                $stmt = $db->prepare($insertSql);
                $result = $stmt->execute([$orderId, $userId, $shippingAddress, $amount]);
            }

            logMoMoTransaction('PAYMENT_SUCCESS', [
                'orderId' => $orderId,
                'transId' => $transId,
                'amount' => $amount,
                'database_update' => $result,
                'order_created' => !$existingOrder
            ]);

            // Send notification email if needed
            sendPaymentNotification($orderId, $transId, $amount);
        } else {
            // Payment failed
            $updateSql = "UPDATE don_hang SET
                         trang_thai_thanh_toan = 'failed',
                         phuong_thuc_thanh_toan = 'momo',
                         ngay_cap_nhat = NOW()
                         WHERE ma_don_hang_text = ?";

            $stmt = $db->prepare($updateSql);
            $result = $stmt->execute([$orderId]);

            logMoMoTransaction('PAYMENT_FAILED', [
                'orderId' => $orderId,
                'resultCode' => $resultCode,
                'message' => $message,
                'database_update' => $result
            ]);
        }
    } catch (Exception $e) {
        logMoMoTransaction('DATABASE_ERROR', [
            'error' => $e->getMessage(),
            'orderId' => $orderId
        ]);
    }
} else {
    logMoMoTransaction('SIGNATURE_MISMATCH', [
        'received_signature' => $signature,
        'calculated_signature' => $partnerSignature,
        'raw_hash' => $rawHash
    ]);
}

// Return response to MoMo
header('Content-Type: application/json');
echo json_encode($response);

/**
 * Log MoMo transaction
 */
function logMoMoTransaction($type, $data)
{
    $logFile = __DIR__ . '/../logs/momo_transactions.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'data' => $data
    ];

    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Gửi thông báo thanh toán thành công
 */
function sendPaymentNotification($orderId, $transId, $amount)
{
    try {
        $db = Database::getInstance()->getConnection();

        // Lấy thông tin đơn hàng
        $orderSql = "SELECT dh.*, u.email, u.ten as customer_name 
                    FROM don_hang dh 
                    LEFT JOIN users u ON dh.ma_nguoi_dung = u.username 
                    WHERE dh.ma_don_hang_text = ?";
        $orderStmt = $db->prepare($orderSql);
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if ($order && !empty($order['email'])) {
            // Tạo nội dung email
            $subject = 'Xác nhận thanh toán đơn hàng #' . $order['id'];
            $message = "
            <h2>Thanh toán thành công!</h2>
            <p>Xin chào {$order['customer_name']},</p>
            <p>Chúng tôi đã nhận được thanh toán cho đơn hàng của bạn:</p>
            <ul>
                <li><strong>Mã đơn hàng:</strong> #{$order['id']}</li>
                <li><strong>Mã tham chiếu:</strong> {$order['ma_don_hang_text']}</li>
                <li><strong>Số tiền:</strong> " . number_format($order['tong_tien'], 0, ',', '.') . " VNĐ</li>
                <li><strong>Mã giao dịch MoMo:</strong> {$transId}</li>
            </ul>
            <p>Đơn hàng của bạn đang được xử lý và sẽ được giao sớm nhất có thể.</p>
            <p>Cảm ơn bạn đã mua hàng!</p>
            ";

            // Gửi email (cần cấu hình mail server)
            // mail($order['email'], $subject, $message, 'Content-Type: text/html; charset=UTF-8');

            // Log email notification
            logMoMoTransaction('EMAIL_NOTIFICATION_SENT', [
                'orderId' => $orderId,
                'email' => $order['email'],
                'transId' => $transId
            ]);
        }
    } catch (Exception $e) {
        logMoMoTransaction('EMAIL_NOTIFICATION_ERROR', [
            'error' => $e->getMessage(),
            'orderId' => $orderId
        ]);
    }
}
