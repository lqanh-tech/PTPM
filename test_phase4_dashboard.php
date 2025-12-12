<?php
/**
 * TEST PHASE 4: Dashboard & Tracking
 * 
 * Kiểm tra:
 * 1. Shipping Dashboard
 * 2. Shipping Report
 * 3. Tracking Page
 * 4. Menu integration
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
    <title>Test Phase 4 - Dashboard & Tracking</title>
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 TEST PHASE 4: Dashboard & Tracking</h1>
        <p style='color: #7f8c8d; margin-bottom: 30px;'>
            <strong>Mục tiêu:</strong> Kiểm tra Dashboard, Báo cáo và Tracking
        </p>";

// Test 1: Check files exist
echo "<h2>📁 TEST 1: Kiểm tra Files</h2>";
echo "<div class='test-section'>";

$files = [
    'lequocanh/administrator/elements_LQA/madmin/shipping_dashboard.php' => 'Dashboard Vận Chuyển',
    'lequocanh/administrator/elements_LQA/madmin/shipping_report.php' => 'Báo Cáo Vận Chuyển',
    'lequocanh/track_order.php' => 'Trang Tracking Công Khai'
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

// Test 2: Check menu integration
echo "<h2>📋 TEST 2: Kiểm tra Menu Integration</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
$leftFile = 'lequocanh/administrator/elements_LQA/left.php';
if (file_exists($leftFile)) {
    $content = file_get_contents($leftFile);
    $hasShippingDashboard = strpos($content, 'shipping_dashboard') !== false;
    $hasShippingReport = strpos($content, 'shipping_report') !== false;
    $hasShippingConfig = strpos($content, 'shipping_config') !== false;
    
    if ($hasShippingDashboard && $hasShippingReport && $hasShippingConfig) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Menu Integration</strong><br>Đã thêm 3 menu items vào left.php</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Menu Integration</strong><br>Thiếu một số menu items</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} else {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Menu Integration</strong><br>File left.php không tồn tại</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Test 3: Check routing
echo "<h2>🔀 TEST 3: Kiểm tra Routing</h2>";
echo "<div class='test-section'>";

$testResults['total']++;
$centerFile = 'lequocanh/administrator/elements_LQA/center.php';
if (file_exists($centerFile)) {
    $content = file_get_contents($centerFile);
    $hasRoutes = strpos($content, "case 'shipping_dashboard'") !== false &&
                 strpos($content, "case 'shipping_report'") !== false &&
                 strpos($content, "case 'shipping_config'") !== false;
    
    if ($hasRoutes) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Routing</strong><br>Đã thêm routes vào center.php</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Routing</strong><br>Thiếu routes trong center.php</div>";
        echo "</div>";
        $testResults['failed']++;
    }
} else {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Routing</strong><br>File center.php không tồn tại</div>";
    echo "</div>";
    $testResults['failed']++;
}

echo "</div>";

// Test 4: Check features
echo "<h2>✨ TEST 4: Kiểm tra Tính Năng</h2>";
echo "<div class='test-section'>";

$features = [
    'shipping_dashboard.php' => ['chart.js', 'bootstrap', 'fa-'],
    'shipping_report.php' => ['form method', 'xlsx', 'window.print'],
    'track_order.php' => ['timeline', 'GET', 'orderCode']
];

foreach ($features as $file => $checks) {
    $testResults['total']++;
    $fullPath = strpos($file, 'track_order') !== false ? 'lequocanh/' . $file : 'lequocanh/administrator/elements_LQA/madmin/' . $file;
    
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $allFound = true;
        
        foreach ($checks as $check) {
            if (stripos($content, $check) === false) {
                $allFound = false;
                break;
            }
        }
        
        if ($allFound) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>$file</strong><br>Có đầy đủ tính năng: " . implode(', ', $checks) . "</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>$file</strong><br>Thiếu một số tính năng</div>";
            echo "</div>";
            $testResults['failed']++;
        }
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>$file</strong><br>File không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
}

echo "</div>";

// Summary
$passRate = $testResults['total'] > 0 ? ($testResults['passed'] / $testResults['total']) * 100 : 0;

echo "<div class='summary'>";
echo "<h2>📊 KẾT QUẢ TỔNG HỢP - PHASE 4</h2>";
echo "<div class='stats'>";
echo "<div><span class='stat-number'>{$testResults['total']}</span><br>Tổng số test</div>";
echo "<div><span class='stat-number' style='color: #2ecc71;'>{$testResults['passed']}</span><br>✅ Passed</div>";
echo "<div><span class='stat-number' style='color: #e74c3c;'>{$testResults['failed']}</span><br>❌ Failed</div>";
echo "<div><span class='stat-number'>" . number_format($passRate, 1) . "%</span><br>Hoàn thành</div>";
echo "</div>";

if ($testResults['failed'] === 0) {
    echo "<div class='test-result test-pass' style='margin-top: 20px;'>";
    echo "<span class='icon'>🎉</span>";
    echo "<div><strong>Phase 4 ĐÃ HOÀN THÀNH XUẤT SẮC!</strong><br>";
    echo "Tất cả các test đều passed. Dashboard & Tracking đã sẵn sàng!";
    echo "</div></div>";
} else {
    echo "<div class='test-result test-fail' style='margin-top: 20px;'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Phase 4 CHƯA HOÀN THÀNH</strong><br>";
    echo "Có {$testResults['failed']} test thất bại.";
    echo "</div></div>";
}

echo "</div>";

echo "</div>
</body>
</html>";
