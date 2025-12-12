<?php
/**
 * GHN Webhook Handler
 * 
 * Nhận webhook từ GHN khi có cập nhật trạng thái vận chuyển
 * Tự động cập nhật database và gửi thông báo cho khách hàng
 */

require_once '../mod/database.php';
require_once '../mod/EmailService.php';

// Log webhook request
$logFile = __DIR__ . '/../../../../logs/ghn_webhook.log';
$requestBody = file_get_contents('php://input');
$requestHeaders = getallheaders();

// Log for debugging
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook received\n", FILE_APPEND);
file_put_contents($logFile, "Headers: " . json_encode($requestHeaders) . "\n", FILE_APPEND);
file_put_contents($logFile, "Body: " . $requestBody . "\n\n", FILE_APPEND);

// Parse webhook data
$webhookData = json_decode($requestBody, true);

if (!$webhookData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Extract data from webhook
    $orderCode = $webhookData['OrderCode'] ?? '';
    $status = $webhookData['Status'] ?? '';
    $statusDescription = $webhookData['Description'] ?? '';
    $location = $webhookData['CurrentWarehouse'] ?? '';
    $updatedDate = $webhookData['UpdatedDate'] ?? date('Y-m-d H:i:s');
    
    if (empty($orderCode)) {
        throw new Exception('Order code is required');
    }
    
    // Map GHN status to our status
    $statusMap = [
        'ready_to_pick' => 'pending',
        'picking' => 'picking',
        'picked' => 'picking',
        'storing' => 'shipping',
        'transporting' => 'shipping',
        'delivering' => 'shipping',
        'delivered' => 'delivered',
        'delivery_fail' => 'failed',
        'return' => 'returned',
        'returned' => 'returned',
        'cancel' => 'cancelled'
    ];
    
    $ourStatus = $statusMap[$status] ?? 'pending';
    
    // Find order by tracking code
    $stmt = $db->prepare("
        SELECT id, ma_don_hang, ten_khach_hang, email 
        FROM don_hang 
        WHERE tracking_code = ?
    ");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found with tracking code: ' . $orderCode);
    }
    
    // Update order status
    $stmt = $db->prepare("
        UPDATE don_hang 
        SET shipping_status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$ourStatus, $order['id']]);
    
    // Insert tracking history
    $stmt = $db->prepare("
        INSERT INTO shipment_tracking 
        (order_id, tracking_code, carrier, status, status_description, location, created_at)
        VALUES (?, ?, 'GHN', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order['id'],
        $orderCode,
        $ourStatus,
        $statusDescription,
        $location,
        $updatedDate
    ]);
    
    // Send email notification if customer has email
    if (!empty($order['email'])) {
        try {
            $emailService = new EmailService();
            $emailService->sendShippingUpdateEmail(
                $order['email'],
                $order['ten_khach_hang'],
                $order['ma_don_hang'],
                $ourStatus,
                $statusDescription,
                $orderCode
            );
        } catch (Exception $e) {
            // Log email error but don't fail webhook
            file_put_contents($logFile, "Email error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully',
        'order_id' => $order['id'],
        'status' => $ourStatus
    ]);
    
    file_put_contents($logFile, "Success: Order {$order['ma_don_hang']} updated to {$ourStatus}\n\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
