<?php
/**
 * Test GHN Service
 * 
 * This will test both Mock and Real GHN API (if configured)
 */

require_once 'lequocanh/administrator/elements_LQA/mod/GHNService.php';

// HTML Header
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test GHN Service</title>
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
        .test-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-mock {
            background: #ffc107;
            color: #000;
        }
        .badge-real {
            background: #28a745;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test GHN Service</h1>";

try {
    $ghn = new GHNService();
    
    // Check if using mock
    $usingMock = $ghn->isUsingMock();
    
    echo "<div class='test-info'>";
    echo "<strong>Service Mode:</strong> ";
    if ($usingMock) {
        echo "<span class='badge badge-mock'>MOCK MODE</span>";
        echo "<p style='margin-top: 10px;'>Đang sử dụng Mock Service (không cần API token thật)</p>";
    } else {
        echo "<span class='badge badge-real'>REAL API</span>";
        echo "<p style='margin-top: 10px;'>Đang sử dụng GHN API thật</p>";
    }
    echo "</div>";
    
    // Test 1: Get Provinces
    echo "<h2>📍 Test 1: Get Provinces</h2>";
    echo "<div class='test-section'>";
    
    $provinces = $ghn->getProvinces();
    
    if ($provinces['code'] === 200 && !empty($provinces['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<strong>✅ SUCCESS</strong><br>";
        echo "Lấy được " . count($provinces['data']) . " tỉnh/thành phố";
        echo "</div>";
        
        echo "<strong>Dữ liệu mẫu (3 tỉnh đầu):</strong>";
        echo "<pre>" . json_encode(array_slice($provinces['data'], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<strong>❌ FAILED</strong><br>";
        echo "Message: " . ($provinces['message'] ?? 'Unknown error');
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test 2: Get Districts
    echo "<h2>🏘️ Test 2: Get Districts (Hanoi - ID: 201)</h2>";
    echo "<div class='test-section'>";
    
    $districts = $ghn->getDistricts(201);
    
    if ($districts['code'] === 200 && !empty($districts['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<strong>✅ SUCCESS</strong><br>";
        echo "Lấy được " . count($districts['data']) . " quận/huyện";
        echo "</div>";
        
        echo "<strong>Dữ liệu mẫu:</strong>";
        echo "<pre>" . json_encode(array_slice($districts['data'], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<strong>❌ FAILED</strong><br>";
        echo "Message: " . ($districts['message'] ?? 'Unknown error');
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test 3: Get Wards
    echo "<h2>🏠 Test 3: Get Wards (Ba Dinh - ID: 1001)</h2>";
    echo "<div class='test-section'>";
    
    $wards = $ghn->getWards(1001);
    
    if ($wards['code'] === 200 && !empty($wards['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<strong>✅ SUCCESS</strong><br>";
        echo "Lấy được " . count($wards['data']) . " phường/xã";
        echo "</div>";
        
        echo "<strong>Dữ liệu mẫu:</strong>";
        echo "<pre>" . json_encode($wards['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<strong>❌ FAILED</strong><br>";
        echo "Message: " . ($wards['message'] ?? 'Unknown error');
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test 4: Calculate Shipping Fee
    echo "<h2>💰 Test 4: Calculate Shipping Fee</h2>";
    echo "<div class='test-section'>";
    
    $feeParams = [
        'to_district_id' => 1001,
        'to_ward_code' => '10001',
        'weight' => 2000, // 2kg
        'insurance_value' => 500000 // 500k VND
    ];
    
    echo "<strong>Tham số:</strong>";
    echo "<pre>" . json_encode($feeParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    $feeResult = $ghn->calculateShippingComplete($feeParams);
    
    if ($feeResult['success']) {
        echo "<div class='test-result test-pass'>";
        echo "<strong>✅ SUCCESS</strong><br>";
        echo "Phí vận chuyển: " . number_format($feeResult['shipping_fee'], 0, ',', '.') . " ₫<br>";
        echo "Phương thức: {$feeResult['method_name']}<br>";
        echo "Thời gian dự kiến: {$feeResult['estimated_days']} ngày";
        echo "</div>";
        
        echo "<strong>Chi tiết:</strong>";
        echo "<pre>" . json_encode($feeResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<strong>❌ FAILED</strong><br>";
        echo "Message: " . ($feeResult['message'] ?? 'Unknown error');
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test 5: Get Available Services
    echo "<h2>🚚 Test 5: Get Available Services</h2>";
    echo "<div class='test-section'>";
    
    $services = $ghn->getAvailableServices(1001);
    
    if ($services['code'] === 200 && !empty($services['data'])) {
        echo "<div class='test-result test-pass'>";
        echo "<strong>✅ SUCCESS</strong><br>";
        echo "Có " . count($services['data']) . " dịch vụ khả dụng";
        echo "</div>";
        
        echo "<strong>Danh sách dịch vụ:</strong>";
        echo "<pre>" . json_encode($services['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<strong>❌ FAILED</strong><br>";
        echo "Message: " . ($services['message'] ?? 'Unknown error');
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test 6: Create Order (Mock only)
    if ($usingMock) {
        echo "<h2>📦 Test 6: Create Shipping Order (Mock)</h2>";
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
            'insurance_value' => 500000,
            'items' => [
                [
                    'name' => 'Áo thun',
                    'quantity' => 2,
                    'price' => 250000
                ]
            ]
        ];
        
        echo "<strong>Thông tin đơn hàng:</strong>";
        echo "<pre>" . json_encode($orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        $orderResult = $ghn->createShippingOrder($orderData);
        
        if ($orderResult['code'] === 200 && !empty($orderResult['data'])) {
            echo "<div class='test-result test-pass'>";
            echo "<strong>✅ SUCCESS</strong><br>";
            echo "Mã đơn hàng: " . $orderResult['data']['order_code'] . "<br>";
            echo "Phí vận chuyển: " . number_format($orderResult['data']['total_fee'], 0, ',', '.') . " ₫";
            echo "</div>";
            
            echo "<strong>Chi tiết đơn hàng:</strong>";
            echo "<pre>" . json_encode($orderResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<strong>❌ FAILED</strong><br>";
            echo "Message: " . ($orderResult['message'] ?? 'Unknown error');
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    // Summary
    echo "<h2>📊 Tổng kết</h2>";
    echo "<div class='test-section'>";
    
    if ($usingMock) {
        echo "<div class='test-result test-warning'>";
        echo "<strong>⚠️ ĐANG SỬ DỤNG MOCK SERVICE</strong><br>";
        echo "<p style='margin-top: 10px;'>Để sử dụng GHN API thật, bạn cần:</p>";
        echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
        echo "<li>Đăng ký tài khoản tại: <a href='https://khachhang.ghn.vn/' target='_blank'>https://khachhang.ghn.vn/</a></li>";
        echo "<li>Lấy API Token từ phần Cài đặt > API</li>";
        echo "<li>Lấy Shop ID từ danh sách cửa hàng</li>";
        echo "<li>Cập nhật file .env:</li>";
        echo "</ol>";
        echo "<pre style='margin-top: 10px;'>GHN_API_TOKEN=your_real_token_here
GHN_SHOP_ID=your_real_shop_id_here</pre>";
        echo "</div>";
    } else {
        echo "<div class='test-result test-pass'>";
        echo "<strong>✅ ĐANG SỬ DỤNG GHN API THẬT</strong><br>";
        echo "<p style='margin-top: 10px;'>Hệ thống đã kết nối thành công với GHN API!</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'>";
    echo "<strong>❌ LỖI NGHIÊM TRỌNG</strong><br>";
    echo "Message: " . $e->getMessage();
    echo "</div>";
}

echo "</div>
</body>
</html>";
