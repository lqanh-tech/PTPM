<?php

header('Content-Type: application/json');

try {
    require_once '../../administrator/elements_LQA/mPDO.php';
    $pdo = new mPDO();
    
    $query = "SELECT order_id, amount, order_info, status, trans_id, message, created_at 
              FROM momo_transactions 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
              ORDER BY created_at DESC 
              LIMIT 20";
    
    $transactions = $pdo->executeS($query, [], true) ?: [];
    
    $notifications = [];
    
    foreach ($transactions as $transaction) {
        $notification = [
            'id' => $transaction['order_id'],
            'type' => $transaction['status'],
            'time' => date('H:i:s d/m/Y', strtotime($transaction['created_at']))
        ];
        
        if ($transaction['status'] === 'SUCCESS') {
            $notification['title'] = '💰 Thanh toán thành công';
            $notification['message'] = 'Nhận được ' . number_format($transaction['amount']) . ' VND - ' . $transaction['order_info'];
            $notification['icon'] = 'fa-check-circle';
            $notification['color'] = 'success';
        } elseif ($transaction['status'] === 'FAILED') {
            $notification['title'] = '❌ Thanh toán thất bại';
            $notification['message'] = $transaction['order_info'] . ' - ' . ($transaction['message'] ?: 'Lỗi không xác định');
            $notification['icon'] = 'fa-times-circle';
            $notification['color'] = 'danger';
        } elseif ($transaction['status'] === 'PENDING') {
            $notification['title'] = '⏳ Đang xử lý';
            $notification['message'] = $transaction['order_info'] . ' - ' . number_format($transaction['amount']) . ' VND';
            $notification['icon'] = 'fa-clock';
            $notification['color'] = 'warning';
        } else {
            $notification['title'] = '🚫 Đã hủy';
            $notification['message'] = $transaction['order_info'];
            $notification['icon'] = 'fa-ban';
            $notification['color'] = 'secondary';
        }
        
        $notifications[] = $notification;
    }
    
    echo json_encode($notifications);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

?>
