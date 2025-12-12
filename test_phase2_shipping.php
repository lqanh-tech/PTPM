<?php
/**
 * TEST PHASE 2: Hệ thống Cấu hình Phí Vận Chuyển
 * 
 * Kiểm tra:
 * 1. Bảng shipping_fees và shipping_methods
 * 2. Module quản lý cấu hình phí (admin)
 * 3. API tính phí tự động
 * 4. Tích hợp vào checkout
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

// HTML Header
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Phase 2 - Shipping Fee Configuration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .container { 
            max-width: 1400px; 
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
            font-size: 32px;
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
            border: 2px solid #e9ecef;
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
        .test-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #3498db;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: center;
        }
        .summary h2 {
            color: white;
            background: transparent;
            border: none;
            margin: 0 0 20px 0;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 TEST PHASE 2: Cấu hình Phí Vận Chuyển</h1>
        <p style='color: #7f8c8d; margin-bottom: 30px;'>
            <strong>Mục tiêu:</strong> Kiểm tra hệ thống cấu hình phí vận chuyển linh hoạt
        </p>";

try {
    $db = Database::getInstance()->getConnection();
    
    // ============================================
    // TEST 1: Kiểm tra bảng shipping_methods
    // ============================================
    echo "<h2>📋 TEST 1: Kiểm tra bảng shipping_methods</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $stmt = $db->query("SHOW TABLES LIKE 'shipping_methods'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Bảng shipping_methods</strong><br>Đã tồn tại trong database</div>";
        echo "</div>";
        $testResults['passed']++;
        
        // Check data
        $methods = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        if (count($methods) > 0) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Dữ liệu phương thức vận chuyển</strong><br>Có " . count($methods) . " phương thức</div>";
            echo "</div>";
            $testResults['passed']++;
            $testResults['total']++;
            
            echo "<table>";
            echo "<tr><th>Mã</th><th>Tên</th><th>Thời gian giao</th><th>Hệ số giá</th><th>Trạng thái</th></tr>";
            foreach ($methods as $method) {
                echo "<tr>";
                echo "<td><code>" . htmlspecialchars($method['code']) . "</code></td>";
                echo "<td>" . htmlspecialchars($method['name']) . "</td>";
                echo "<td>" . htmlspecialchars($method['delivery_time']) . "</td>";
                echo "<td>" . $method['price_multiplier'] . "x</td>";
                echo "<td>" . ($method['is_active'] ? '✅ Hoạt động' : '❌ Tắt') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Dữ liệu phương thức vận chuyển</strong><br>Chưa có dữ liệu</div>";
            echo "</div>";
            $testResults['warnings']++;
            $testResults['total']++;
        }
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Bảng shipping_methods</strong><br>Không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 2: Kiểm tra bảng shipping_fees
    // ============================================
    echo "<h2>💰 TEST 2: Kiểm tra bảng shipping_fees</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $stmt = $db->query("SHOW TABLES LIKE 'shipping_fees'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Bảng shipping_fees</strong><br>Đã tồn tại trong database</div>";
        echo "</div>";
        $testResults['passed']++;
        
        // Check columns
        $columns = $db->query("SHOW COLUMNS FROM shipping_fees")->fetchAll(PDO::FETCH_ASSOC);
        $requiredColumns = ['base_fee', 'fee_per_kg', 'weight_from', 'weight_to', 'min_order_free_ship', 'priority'];
        $hasAllColumns = true;
        
        foreach ($requiredColumns as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $col) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $hasAllColumns = false;
                break;
            }
        }
        
        $testResults['total']++;
        if ($hasAllColumns) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Cấu trúc bảng</strong><br>Có đầy đủ các cột cần thiết</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Cấu trúc bảng</strong><br>Thiếu một số cột</div>";
            echo "</div>";
            $testResults['failed']++;
        }
        
        // Check data
        $fees = $db->query("SELECT * FROM shipping_fees WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
        $testResults['total']++;
        if (count($fees) > 0) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Dữ liệu cấu hình phí</strong><br>Có " . count($fees) . " cấu hình</div>";
            echo "</div>";
            $testResults['passed']++;
            
            echo "<table>";
            echo "<tr><th>Tên</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Ưu tiên</th></tr>";
            foreach ($fees as $fee) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($fee['name']) . "</td>";
                echo "<td>" . number_format($fee['base_fee'], 0, ',', '.') . "₫</td>";
                echo "<td>" . number_format($fee['fee_per_kg'], 0, ',', '.') . "₫</td>";
                echo "<td>" . ($fee['min_order_free_ship'] ? number_format($fee['min_order_free_ship'], 0, ',', '.') . "₫" : '-') . "</td>";
                echo "<td>" . $fee['priority'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Dữ liệu cấu hình phí</strong><br>Chưa có dữ liệu</div>";
            echo "</div>";
            $testResults['warnings']++;
        }
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Bảng shipping_fees</strong><br>Không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 3: Kiểm tra Module Admin
    // ============================================
    echo "<h2>⚙️ TEST 3: Kiểm tra Module Quản lý</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $adminModulePath = 'lequocanh/administrator/elements_LQA/madmin/shipping_config.php';
    if (file_exists($adminModulePath)) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Module Admin</strong><br>File tồn tại: <code>$adminModulePath</code></div>";
        echo "</div>";
        $testResults['passed']++;
        
        // Check content
        $content = file_get_contents($adminModulePath);
        $hasAddMethod = strpos($content, 'add_shipping_method') !== false;
        $hasAddFee = strpos($content, 'add_shipping_fee') !== false;
        $hasUpdateFee = strpos($content, 'update_shipping_fee') !== false;
        
        $testResults['total']++;
        if ($hasAddMethod && $hasAddFee && $hasUpdateFee) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Chức năng Module</strong><br>Có đầy đủ CRUD operations</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Chức năng Module</strong><br>Thiếu một số chức năng</div>";
            echo "</div>";
            $testResults['warnings']++;
        }
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Module Admin</strong><br>File không tồn tại: <code>$adminModulePath</code></div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 4: Kiểm tra API tính phí
    // ============================================
    echo "<h2>🔌 TEST 4: Kiểm tra API tính phí</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $apiPath = 'lequocanh/administrator/elements_LQA/mgiohang/calculate_shipping_api.php';
    if (file_exists($apiPath)) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>API Calculate Shipping</strong><br>File tồn tại: <code>$apiPath</code></div>";
        echo "</div>";
        $testResults['passed']++;
        
        // Check API content
        $content = file_get_contents($apiPath);
        $hasShippingClass = strpos($content, 'ShippingCls') !== false || strpos($content, 'Shipping') !== false;
        $hasCalculate = strpos($content, 'calculateShipping') !== false;
        $hasJSON = strpos($content, 'json_encode') !== false;
        
        $testResults['total']++;
        if ($hasShippingClass && $hasCalculate && $hasJSON) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Nội dung API</strong><br>Có logic tính phí và trả về JSON</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Nội dung API</strong><br>Có thể thiếu một số logic</div>";
            echo "</div>";
            $testResults['warnings']++;
        }
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>API Calculate Shipping</strong><br>File không tồn tại: <code>$apiPath</code></div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 5: Kiểm tra tích hợp vào Checkout
    // ============================================
    echo "<h2>🛒 TEST 5: Kiểm tra tích hợp Checkout</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $checkoutPath = 'lequocanh/administrator/elements_LQA/mgiohang/checkout.php';
    if (file_exists($checkoutPath)) {
        $content = file_get_contents($checkoutPath);
        $hasCalculateShipping = strpos($content, 'calculate_shipping_api') !== false || 
                               strpos($content, 'calculateShipping') !== false;
        
        if ($hasCalculateShipping) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Tích hợp Checkout</strong><br>Đã tích hợp API tính phí vào checkout</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Tích hợp Checkout</strong><br>Chưa thấy tích hợp API tính phí</div>";
            echo "</div>";
            $testResults['warnings']++;
        }
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Tích hợp Checkout</strong><br>File checkout không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 6: Kiểm tra View
    // ============================================
    echo "<h2>👁️ TEST 6: Kiểm tra View</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_sales_management = 'v_shipping_fees_detail'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>View v_shipping_fees_detail</strong><br>Đã tồn tại</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-warning'>";
        echo "<span class='icon'>⚠️</span>";
        echo "<div><strong>View v_shipping_fees_detail</strong><br>Chưa tồn tại (không bắt buộc)</div>";
        echo "</div>";
        $testResults['warnings']++;
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Lỗi nghiêm trọng</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
    $testResults['failed']++;
}

// ============================================
// SUMMARY
// ============================================
echo "<div class='summary'>";
echo "<h2>📊 KẾT QUẢ TỔNG HỢP - PHASE 2</h2>";

$passRate = $testResults['total'] > 0 ? ($testResults['passed'] / $testResults['total']) * 100 : 0;

echo "<div class='stats'>";
echo "<div class='stat-item'>";
echo "<span class='stat-number'>{$testResults['total']}</span>";
echo "<span class='stat-label'>Tổng số test</span>";
echo "</div>";
echo "<div class='stat-item'>";
echo "<span class='stat-number' style='color: #2ecc71;'>{$testResults['passed']}</span>";
echo "<span class='stat-label'>✅ Passed</span>";
echo "</div>";
echo "<div class='stat-item'>";
echo "<span class='stat-number' style='color: #f39c12;'>{$testResults['warnings']}</span>";
echo "<span class='stat-label'>⚠️ Warnings</span>";
echo "</div>";
echo "<div class='stat-item'>";
echo "<span class='stat-number' style='color: #e74c3c;'>{$testResults['failed']}</span>";
echo "<span class='stat-label'>❌ Failed</span>";
echo "</div>";
echo "<div class='stat-item'>";
echo "<span class='stat-number'>" . number_format($passRate, 1) . "%</span>";
echo "<span class='stat-label'>Tỷ lệ hoàn thành</span>";
echo "</div>";
echo "</div>";

if ($testResults['failed'] > 0) {
    echo "<div class='test-result test-fail' style='margin-top: 20px;'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>Phase 2 CHƯA HOÀN THÀNH</strong><br>";
    echo "Có {$testResults['failed']} test thất bại. Cần khắc phục trước khi chuyển sang Phase 3.";
    echo "</div></div>";
} elseif ($testResults['warnings'] > 0) {
    echo "<div class='test-result test-warning' style='margin-top: 20px;'>";
    echo "<span class='icon'>⚠️</span>";
    echo "<div><strong>Phase 2 CƠ BẢN ĐÃ HOÀN THÀNH</strong><br>";
    echo "Có {$testResults['warnings']} cảnh báo. Nên hoàn thiện trước khi chuyển sang Phase 3.";
    echo "</div></div>";
} else {
    echo "<div class='test-result test-pass' style='margin-top: 20px;'>";
    echo "<span class='icon'>🎉</span>";
    echo "<div><strong>Phase 2 ĐÃ HOÀN THÀNH XUẤT SẮC!</strong><br>";
    echo "Tất cả các test đều passed. Sẵn sàng chuyển sang Phase 3.";
    echo "</div></div>";
}

echo "</div>";

echo "</div>
</body>
</html>";
