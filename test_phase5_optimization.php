<?php
/**
 * TEST PHASE 5: Tối ưu & Mở rộng
 * 
 * Kiểm tra:
 * 1. GHN Webhook Handler
 * 2. Email Service
 * 3. Cache Service
 * 4. Batch Operations
 */

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Phase 5 - Optimization</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
        }
        h1 { 
            color: #2c3e50; 
            border-bottom: 4px solid #3498db; 
            padding-bottom: 15px; 
            margin-bottom: 30px;
        }
        h2 { 
            color: #34495e; 
            margin: 30px 0 15px 0; 
            padding: 10px;
            background: #ecf0f1;
            border-left: 5px solid #3498db;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-pass {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .test-fail {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        .test-info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
        }
        .icon {
            font-size: 24px;
            font-weight: bold;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: center;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 TEST PHASE 5: Tối ưu & Mở rộng</h1>
        <p style='color: #7f8c8d; margin-bottom: 30px;'>
            <strong>Mục tiêu:</strong> Kiểm tra Webhook, Email, Cache và Batch Operations
        </p>";

// Test 1: Check files exist
echo "<h2>📁 TEST 1: Kiểm tra Files</h2>";
echo "<div class='test-section'>";

$files = [
    'lequocanh/administrator/elements_LQA/mgiohang/ghn_webhook.php' => 'GHN Webhook Handler',
    'lequocanh/administrator/elements_LQA/mod/EmailService.php' => 'Email Service',
    'lequocanh/administrator/elements_LQA/mod/CacheService.php' => 'Cache Service',
    'lequocanh/administrator/elements_LQA/madmin/batch_shipping_operations.php' => 'Batch Operations'
];

foreach ($files as $file => $name) {
    $testResults['total']++;
    if (file_exists($file)) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>$name</strong><br>File tồn tại: <code>$file</code></div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>$name</strong><br>File không tồn tại: <code>$file</code></div>";
        echo "</div>";
        $testResults['failed']++;
    }
}

echo "</div>";

// Test 2: Check Webhook features
echo "<h2>🔔 TEST 2: Kiểm tra Webhook Features</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
$webhookFile = 'lequocanh/administrator/elements_LQA/mgiohang/ghn_webhook.php';
if (file_exists($webhookFile)) {
    $content = file_get_contents($webhookFile);
    $hasWebhookData = stripos($content, 'webhookData') !== false;
    $hasOrderCode = stripos($content, 'OrderCode') !== false;
    $hasStatusMap = stripos($content, 'statusMap') !== false;
    $hasEmailNotif = stripos($content, 'EmailService') !== false;
    
    if ($hasWebhookData && $hasOrderCode && $hasStatusMap && $hasEmailNotif) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Webhook Features</strong><br>Có đầy đủ: Parse data, Status mapping, Email notification</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Webhook Features</strong><br>Thiếu một số tính năng</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} else {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Webhook Features</strong><br>File không tồn tại</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Test 3: Check Email Service
echo "<h2>📧 TEST 3: Kiểm tra Email Service</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
$emailFile = 'lequocanh/administrator/elements_LQA/mod/EmailService.php';
if (file_exists($emailFile)) {
    $content = file_get_contents($emailFile);
    $hasShippingEmail = stripos($content, 'sendShippingUpdateEmail') !== false;
    $hasOrderEmail = stripos($content, 'sendOrderConfirmationEmail') !== false;
    $hasTemplate = stripos($content, 'getEmailTemplate') !== false;
    $hasHtml = stripos($content, 'text/html') !== false;
    
    if ($hasShippingEmail && $hasOrderEmail && $hasTemplate && $hasHtml) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Email Service</strong><br>Có đầy đủ: Shipping update, Order confirmation, HTML template</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Email Service</strong><br>Thiếu một số tính năng</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} else {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Email Service</strong><br>File không tồn tại</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Test 4: Check Cache Service
echo "<h2>💾 TEST 4: Kiểm tra Cache Service</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
$cacheFile = 'lequocanh/administrator/elements_LQA/mod/CacheService.php';
if (file_exists($cacheFile)) {
    $content = file_get_contents($cacheFile);
    $hasGet = stripos($content, 'function get') !== false;
    $hasSet = stripos($content, 'function set') !== false;
    $hasRemember = stripos($content, 'function remember') !== false;
    $hasClear = stripos($content, 'function clear') !== false;
    
    if ($hasGet && $hasSet && $hasRemember && $hasClear) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Cache Service</strong><br>Có đầy đủ: get, set, remember, clear</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Cache Service</strong><br>Thiếu một số method</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} else {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Cache Service</strong><br>File không tồn tại</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Test 5: Check Batch Operations
echo "<h2>⚡ TEST 5: Kiểm tra Batch Operations</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
$batchFile = 'lequocanh/administrator/elements_LQA/madmin/batch_shipping_operations.php';
if (file_exists($batchFile)) {
    $content = file_get_contents($batchFile);
    $hasCreateShipments = stripos($content, 'create_shipments') !== false;
    $hasUpdateStatus = stripos($content, 'update_status') !== false;
    $hasPrintLabels = stripos($content, 'print_labels') !== false;
    $hasCheckbox = stripos($content, 'checkbox') !== false;
    
    if ($hasCreateShipments && $hasUpdateStatus && $hasPrintLabels && $hasCheckbox) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Batch Operations</strong><br>Có đầy đủ: Create shipments, Update status, Print labels, Checkbox selection</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Batch Operations</strong><br>Thiếu một số tính năng</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} else {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Batch Operations</strong><br>File không tồn tại</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Test 6: Test Cache Service functionality
echo "<h2>🧪 TEST 6: Test Cache Functionality</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
try {
    require_once 'lequocanh/administrator/elements_LQA/mod/CacheService.php';
    $cache = new CacheService();
    
    // Test set/get
    $testKey = 'test_key_' . time();
    $testValue = ['data' => 'test', 'timestamp' => time()];
    
    $cache->set($testKey, $testValue, 60);
    $retrieved = $cache->get($testKey);
    
    if ($retrieved === $testValue) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Cache Functionality</strong><br>Set/Get hoạt động đúng</div>";
        echo "</div>";
        $testResults['passed']++;
        
        // Clean up
        $cache->delete($testKey);
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Cache Functionality</strong><br>Set/Get không hoạt động đúng</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} catch (Exception $e) {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Cache Functionality</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Summary
$passRate = $testResults['total'] > 0 ? ($testResults['passed'] / $testResults['total']) * 100 : 0;

echo "<div class='summary'>";
echo "<h2>📊 KẾT QUẢ TỔNG HỢP - PHASE 5</h2>";
echo "<div class='stats'>";
echo "<div><span class='stat-number'>{$testResults['total']}</span><br>Tổng số test</div>";
echo "<div><span class='stat-number' style='color: #2ecc71;'>{$testResults['passed']}</span><br>✅ Passed</div>";
echo "<div><span class='stat-number' style='color: #e74c3c;'>{$testResults['failed']}</span><br>❌ Failed</div>";
echo "<div><span class='stat-number'>" . number_format($passRate, 1) . "%</span><br>Hoàn thành</div>";
echo "</div>";

if ($testResults['failed'] === 0) {
    echo "<div class='test-result test-pass' style='margin-top: 20px;'>";
    echo "<span class='icon'>🎉</span>";
    echo "<div><strong>Phase 5 ĐÃ HOÀN THÀNH XUẤT SẮC!</strong><br>";
    echo "Tất cả các test đều passed. Hệ thống đã được tối ưu và mở rộng!";
    echo "</div></div>";
} else {
    echo "<div class='test-result test-fail' style='margin-top: 20px;'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Phase 5 CHƯA HOÀN THÀNH</strong><br>";
    echo "Có {$testResults['failed']} test thất bại.";
    echo "</div></div>";
}

echo "</div>";

echo "</div>
</body>
</html>";
