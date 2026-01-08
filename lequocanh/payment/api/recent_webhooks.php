<?php

header('Content-Type: application/json');

try {
    require_once '../../administrator/elements_LQA/mPDO.php';
    $pdo = new mPDO();
    
    $query = "SELECT order_id, amount, order_info, status, created_at 
              FROM momo_transactions 
              ORDER BY created_at DESC 
              LIMIT 10";
    
    $transactions = $pdo->executeS($query, [], true) ?: [];
    
    $webhooks = [];
    
    foreach ($transactions as $transaction) {
        $webhooks[] = [
            'order_id' => $transaction['order_id'],
            'amount' => (float)$transaction['amount'],
            'order_info' => $transaction['order_info'],
            'status' => $transaction['status'],
            'time' => date('H:i d/m', strtotime($transaction['created_at']))
        ];
    }
    
    echo json_encode($webhooks);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

?>
