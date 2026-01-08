<?php

$allowedIPs = ['127.0.0.1', '::1'];
$isNgrok = isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false;

if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs) && !$isNgrok) {
    http_response_code(403);
    die('Access denied');
}

set_time_limit(300);

ini_set('display_errors', 0);
error_reporting(0);

require_once '../mod/AutoOrderProcessor.php';

try {
    $processor = new AutoOrderProcessor();

    echo "[" . date('Y-m-d H:i:s') . "] Starting auto order processing...\n";

    echo "Processing paid orders...\n";
    $result1 = $processor->autoApprovePaymentConfirmedOrders();

    if ($result1['success']) {
        echo "✅ " . $result1['message'] . "\n";
    } else {
        echo "❌ Error: " . $result1['message'] . "\n";
    }

    echo "Processing expired cancel deadlines...\n";
    $result2 = $processor->processExpiredCancelDeadlines();

    if ($result2['success']) {
        echo "✅ " . $result2['message'] . "\n";
    } else {
        echo "❌ Error: " . $result2['message'] . "\n";
    }

    $statsResult = $processor->getOrderStats();
    if ($statsResult['success']) {
        $stats = $statsResult['stats'];
        echo "\n📊 Order Statistics:\n";
        echo "- Pending orders: {$stats['pending_total']}\n";
        echo "- COD pending: {$stats['cod_pending']}\n";
        echo "- Paid pending: {$stats['paid_pending']}\n";
        echo "- Auto approved today: {$stats['auto_approved_today']}\n";
    }

    echo "\n[" . date('Y-m-d H:i:s') . "] Auto order processing completed.\n";
    echo str_repeat("=", 50) . "\n";
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    error_log("Auto order processing error: " . $e->getMessage());
}
