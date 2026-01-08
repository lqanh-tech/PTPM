<?php

header('Content-Type: application/json');

try {
    require_once '../../administrator/elements_LQA/mPDO.php';
    $pdo = new mPDO();
    
    $today = date('Y-m-d');
    
    $revenueQuery = "SELECT COALESCE(SUM(amount), 0) as revenue 
                     FROM momo_transactions 
                     WHERE DATE(created_at) = ? AND status = 'SUCCESS'";
    $revenueResult = $pdo->executeS($revenueQuery, [$today]);
    
    $statusQuery = "SELECT status, COUNT(*) as count 
                    FROM momo_transactions 
                    WHERE DATE(created_at) = ? 
                    GROUP BY status";
    $statusResults = $pdo->executeS($statusQuery, [$today], true) ?: [];
    
    $data = [
        'revenue' => $revenueResult['revenue'] ?? 0,
        'success_count' => 0,
        'failed_count' => 0,
        'pending_count' => 0,
        'cancelled_count' => 0
    ];
    
    foreach ($statusResults as $status) {
        switch ($status['status']) {
            case 'SUCCESS':
                $data['success_count'] = $status['count'];
                break;
            case 'FAILED':
                $data['failed_count'] = $status['count'];
                break;
            case 'PENDING':
                $data['pending_count'] = $status['count'];
                break;
            case 'CANCELLED':
                $data['cancelled_count'] = $status['count'];
                break;
        }
    }
    
    $weekQuery = "SELECT COALESCE(SUM(amount), 0) as revenue, COUNT(*) as count 
                  FROM momo_transactions 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'SUCCESS'";
    $weekResult = $pdo->executeS($weekQuery);
    
    $monthQuery = "SELECT COALESCE(SUM(amount), 0) as revenue, COUNT(*) as count 
                   FROM momo_transactions 
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'SUCCESS'";
    $monthResult = $pdo->executeS($monthQuery);
    
    $data['week_revenue'] = $weekResult['revenue'] ?? 0;
    $data['week_count'] = $weekResult['count'] ?? 0;
    $data['month_revenue'] = $monthResult['revenue'] ?? 0;
    $data['month_count'] = $monthResult['count'] ?? 0;
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

?>
