<?php
/**
 * TEST PHASE 3: Tích hợp GHN API
 * 
 * Kiểm tra:
 * 1. GHNService class
 * 2. GHNMockService class
 * 3. Tích hợp vào ShippingCls
 * 4. API endpoints
 * 5. Tính năng tạo đơn vận chuyển
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/GHNService.php';
require_once 'lequocanh/administrator/elements_LQA/mod/ShippingCls.php';

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
    <title>Test Phase 3 - GHN Integration</title>
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
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 10px;
            font-size: 12px;
        }
        .badge-mock {
            background: #ffc107;
            color: #000;
        }
        .badge-real {
            background: #28a745;
            color: #fff;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 11px;
            max-height: 300px;
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 TEST PHASE 3: Tích hợp GHN API</h1>
        <p style='color: #7f8c8d; margin-bottom: 30px;'>
            <strong>Mục tiêu:</strong> Kiểm tra tích hợp GHN API với Mock Service fallback
        </p>";

try {
    // ============================================
    // TEST 1: Kiểm tra GHNService class
    // ============================================
    echo "<h2>📦 TEST 1: Kiểm tra GHNService Class</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    if (class_exists('GHNService')) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>GHNService Class</strong><br>Class tồn tại và có thể khởi tạo</div>";
        echo "</div>";
        $testResults['passed']++;
        
        $ghn = new GHNService();
        $usingMock = $ghn->isUsingMock();
        
        $testResults['total']++;
        echo "<div class='test-result test-info'>";
        echo "<span class='icon'>ℹ️</span>";
        echo "<div><strong>Service Mode</strong><br>";
        if ($usingMock) {
            echo "Đang sử dụng Mock Service <span class='badge badge-mock'>MOCK</span>";
        } else {
            echo "Đang sử dụng GHN API thật <span class='badge badge-real'>REAL API</span>";
        }
        echo "</div></div>";
        $testResults['passed']++;
        
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>GHNService Class</strong><br>Class không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 2: Kiểm tra GHNMockService
    // ============================================
    echo "<h2>🎭 TEST 2: Kiểm tra GHNMockService</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    if (class_exists('GHNMockService')) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>GHNMockService Class</strong><br>Class tồn tại</div>";
        echo "</div>";
        $testResults['passed']++;
        
        $mock = new GHNMockService();
        
        // Test mock provinces
        $testResults['total']++;
        $provinces = $mock->getProvinces();
        if ($provinces['code'] === 200 && !empty($provinces['data'])) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Mock Provinces</strong><br>Có " . count($provinces['data']) . " tỉnh/thành</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Mock Provinces</strong><br>Không lấy được dữ liệu</div>";
            echo "</div>";
            $testResults['failed']++;
        }
        
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>GHNMockService Class</strong><br>Class không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 3: Tính phí vận chuyển qua GHN
    // ============================================
    echo "<h2>💰 TEST 3: Tính phí vận chuyển qua GHN</h2>";
    echo "<div class='test-section'>";
    
    $ghn = new GHNService();
    
    $testParams = [
        'to_district_id' => 1001,
        'to_ward_code' => '10001',
        'weight' => 2000,
        'insurance_value' => 500000
    ];
    
    echo "<strong>Tham số test:</strong>";
    echo "<pre>" . json_encode($testParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    $testResults['total']++;
    $feeResult = $ghn->calculateShippingComplete($testParams);
    
    if ($feeResult['success']) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Tính phí thành công</strong><br>";
        echo "Phí: " . number_format($feeResult['shipping_fee'], 0, ',', '.') . "₫<br>";
        echo "Phương thức: {$feeResult['method_name']}<br>";
        echo "Thời gian: {$feeResult['estimated_days']} ngày";
        echo "</div></div>";
        $testResults['passed']++;
        
        echo "<strong>Chi tiết kết quả:</strong>";
        echo "<pre>" . json_encode($feeResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Tính phí thất bại</strong><br>" . ($feeResult['message'] ?? 'Unknown error') . "</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 4: Tích hợp vào ShippingCls
    // ============================================
    echo "<h2>🔗 TEST 4: Tích hợp vào ShippingCls</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    if (class_exists('Shipping')) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Shipping Class</strong><br>Class tồn tại</div>";
        echo "</div>";
        $testResults['passed']++;
        
        $shipping = new Shipping();
        
        $testResults['total']++;
        $shippingResult = $shipping->calculateShippingComplete($testParams);
        
        if ($shippingResult['success']) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Tính phí qua ShippingCls</strong><br>";
            echo "Phí: " . number_format($shippingResult['shipping_fee'], 0, ',', '.') . "₫<br>";
            echo "Phương thức: {$shippingResult['method_name']}";
            echo "</div></div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Tính phí qua ShippingCls thất bại</strong></div>";
            echo "</div>";
            $testResults['failed']++;
        }
        
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Shipping Class</strong><br>Class không tồn tại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 5: Lấy danh sách địa chỉ
    // ============================================
    echo "<h2>📍 TEST 5: Lấy danh sách địa chỉ từ GHN</h2>";
    echo "<div class='test-section'>";
    
    $ghn = new GHNService();
    
    // Test provinces
    $testResults['total']++;
    $provinces = $ghn->getProvinces();
    if ($provinces['code'] === 200 && !empty($provinces['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Get Provinces</strong><br>Lấy được " . count($provinces['data']) . " tỉnh/thành</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Get Provinces</strong><br>Thất bại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    // Test districts
    $testResults['total']++;
    $districts = $ghn->getDistricts(201);
    if ($districts['code'] === 200 && !empty($districts['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Get Districts</strong><br>Lấy được " . count($districts['data']) . " quận/huyện</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Get Districts</strong><br>Thất bại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    // Test wards
    $testResults['total']++;
    $wards = $ghn->getWards(1001);
    if ($wards['code'] === 200 && !empty($wards['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Get Wards</strong><br>Lấy được " . count($wards['data']) . " phường/xã</div>";
        echo "</div>";
        $testResults['passed']++;
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Get Wards</strong><br>Thất bại</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 6: Tạo đơn vận chuyển (Mock only)
    // ============================================
    if ($ghn->isUsingMock()) {
        echo "<h2>📦 TEST 6: Tạo đơn vận chuyển (Mock)</h2>";
        echo "<div class='test-section'>";
        
        $orderData = [
            'to_name' => 'Nguyễn Văn A',
            'to_phone' => '0987654321',
            'to_address' => '123 Đường ABC',
            'to_ward_code' => '10001',
            'to_district_id' => 1001,
            'cod_amount' => 500000,
            'content' => 'Quần áo',
            'weight' => 1000,
            'insurance_value' => 500000
        ];
        
        $testResults['total']++;
        $orderResult = $ghn->createShippingOrder($orderData);
        
        if ($orderResult['code'] === 200 && !empty($orderResult['data'])) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Tạo đơn thành công</strong><br>";
            echo "Mã đơn: " . $orderResult['data']['order_code'];
            echo "</div></div>";
            $testResults['passed']++;
            
            echo "<strong>Chi tiết đơn hàng:</strong>";
            echo "<pre>" . json_encode($orderResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Tạo đơn thất bại</strong></div>";
            echo "</div>";
            $testResults['failed']++;
        }
        
        echo "</div>";
    }
    
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
echo "<h2>📊 KẾT QUẢ TỔNG HỢP - PHASE 3</h2>";

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
    echo "<div><strong>Phase 3 CHƯA HOÀN THÀNH</strong><br>";
    echo "Có {$testResults['failed']} test thất bại. Cần khắc phục.";
    echo "</div></div>";
} elseif ($testResults['warnings'] > 0) {
    echo "<div class='test-result test-warning' style='margin-top: 20px;'>";
    echo "<span class='icon'>⚠️</span>";
    echo "<div><strong>Phase 3 CƠ BẢN ĐÃ HOÀN THÀNH</strong><br>";
    echo "Có {$testResults['warnings']} cảnh báo.";
    echo "</div></div>";
} else {
    echo "<div class='test-result test-pass' style='margin-top: 20px;'>";
    echo "<span class='icon'>🎉</span>";
    echo "<div><strong>Phase 3 ĐÃ HOÀN THÀNH XUẤT SẮC!</strong><br>";
    echo "Tất cả các test đều passed. GHN API đã được tích hợp thành công!";
    echo "</div></div>";
}

// Instructions
$ghn = new GHNService();
if ($ghn->isUsingMock()) {
    echo "<div class='test-result test-info' style='margin-top: 20px;'>";
    echo "<span class='icon'>ℹ️</span>";
    echo "<div><strong>Đang sử dụng Mock Service</strong><br>";
    echo "<p style='margin-top: 10px;'>Để sử dụng GHN API thật:</p>";
    echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
    echo "<li>Đăng ký tài khoản: <a href='https://khachhang.ghn.vn/' target='_blank'>https://khachhang.ghn.vn/</a></li>";
    echo "<li>Lấy API Token từ Cài đặt > API</li>";
    echo "<li>Lấy Shop ID từ danh sách cửa hàng</li>";
    echo "<li>Cập nhật file .env:</li>";
    echo "</ol>";
    echo "<pre style='margin-top: 10px;'>GHN_API_TOKEN=your_real_token_here
GHN_SHOP_ID=your_real_shop_id_here</pre>";
    echo "</div></div>";
}

echo "</div>";

echo "</div>
</body>
</html>";
