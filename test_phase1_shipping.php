<?php
/**
 * TEST PHASE 1: Hệ thống Quản lý Khu Vực Giao Hàng
 * 
 * Kiểm tra:
 * 1. Database tables (provinces, districts, wards, shipping_zones)
 * 2. Dữ liệu địa chỉ Việt Nam đã được import
 * 3. Address selector component
 * 4. API endpoints
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

// HTML Header
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Phase 1 - Shipping System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-info { background: #17a2b8; color: white; }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }
        .summary h2 {
            color: white;
            background: transparent;
            border: none;
            margin: 0 0 20px 0;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 TEST PHASE 1: Hệ thống Quản lý Khu Vực Giao Hàng</h1>
        <p style='color: #7f8c8d; margin-bottom: 30px;'>Kiểm tra toàn diện các thành phần của Phase 1</p>";

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

try {
    $db = Database::getInstance()->getConnection();
    
    // ============================================
    // TEST 1: Kiểm tra Database Tables
    // ============================================
    echo "<h2>📊 TEST 1: Kiểm tra Database Tables</h2>";
    echo "<div class='test-section'>";
    
    $requiredTables = [
        'provinces' => 'Bảng Tỉnh/Thành phố',
        'districts' => 'Bảng Quận/Huyện',
        'wards' => 'Bảng Phường/Xã',
        'shipping_zones' => 'Bảng Khu vực giao hàng'
    ];
    
    foreach ($requiredTables as $table => $description) {
        $testResults['total']++;
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                echo "<div class='test-result test-pass'>";
                echo "<span class='icon'>✅</span>";
                echo "<div><strong>$description</strong><br><code>$table</code> - Tồn tại</div>";
                echo "</div>";
                $testResults['passed']++;
            } else {
                echo "<div class='test-result test-fail'>";
                echo "<span class='icon'>❌</span>";
                echo "<div><strong>$description</strong><br><code>$table</code> - Không tồn tại</div>";
                echo "</div>";
                $testResults['failed']++;
            }
        } catch (PDOException $e) {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>$description</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "</div>";
            $testResults['failed']++;
        }
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 2: Kiểm tra Cấu trúc Bảng
    // ============================================
    echo "<h2>🔧 TEST 2: Kiểm tra Cấu trúc Bảng</h2>";
    echo "<div class='test-section'>";
    
    // Check provinces table structure
    $testResults['total']++;
    try {
        $stmt = $db->query("DESCRIBE provinces");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'code', 'name', 'region', 'is_active'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Bảng provinces</strong><br>Có đầy đủ các cột: " . implode(', ', $requiredColumns) . "</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Bảng provinces</strong><br>Thiếu cột: " . implode(', ', $missingColumns) . "</div>";
            echo "</div>";
            $testResults['failed']++;
        }
    } catch (PDOException $e) {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Bảng provinces</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 3: Kiểm tra Dữ liệu Địa chỉ Việt Nam
    // ============================================
    echo "<h2>🗺️ TEST 3: Kiểm tra Dữ liệu Địa chỉ Việt Nam</h2>";
    echo "<div class='test-section'>";
    
    // Check provinces data
    $testResults['total']++;
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM provinces WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $provinceCount = $result['count'];
        
        if ($provinceCount >= 63) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Dữ liệu Tỉnh/Thành phố</strong><br>Có <strong>$provinceCount</strong> tỉnh/thành (đủ 63 tỉnh/thành VN)</div>";
            echo "</div>";
            $testResults['passed']++;
        } else if ($provinceCount > 0) {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Dữ liệu Tỉnh/Thành phố</strong><br>Chỉ có <strong>$provinceCount</strong> tỉnh/thành (thiếu so với 63 tỉnh/thành VN)</div>";
            echo "</div>";
            $testResults['warnings']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>Dữ liệu Tỉnh/Thành phố</strong><br>Chưa có dữ liệu</div>";
            echo "</div>";
            $testResults['failed']++;
        }
        
        // Show sample data
        if ($provinceCount > 0) {
            echo "<h3 style='margin-top: 20px;'>📋 Dữ liệu mẫu (10 tỉnh/thành đầu tiên):</h3>";
            $stmt = $db->query("SELECT * FROM provinces ORDER BY region, name LIMIT 10");
            $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Mã</th><th>Tên</th><th>Miền</th><th>Trạng thái</th></tr>";
            foreach ($provinces as $province) {
                $badge = $province['is_active'] ? 'badge-success' : 'badge-danger';
                $status = $province['is_active'] ? 'Hoạt động' : 'Không hoạt động';
                echo "<tr>";
                echo "<td><code>{$province['code']}</code></td>";
                echo "<td>{$province['name']}</td>";
                echo "<td>{$province['region']}</td>";
                echo "<td><span class='badge $badge'>$status</span></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Dữ liệu Tỉnh/Thành phố</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    // Check districts data
    $testResults['total']++;
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM districts");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $districtCount = $result['count'];
        
        if ($districtCount > 0) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Dữ liệu Quận/Huyện</strong><br>Có <strong>$districtCount</strong> quận/huyện</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Dữ liệu Quận/Huyện</strong><br>Chưa có dữ liệu (cần import)</div>";
            echo "</div>";
            $testResults['warnings']++;
        }
    } catch (PDOException $e) {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Dữ liệu Quận/Huyện</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    // Check wards data
    $testResults['total']++;
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM wards");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $wardCount = $result['count'];
        
        if ($wardCount > 0) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>Dữ liệu Phường/Xã</strong><br>Có <strong>$wardCount</strong> phường/xã</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-warning'>";
            echo "<span class='icon'>⚠️</span>";
            echo "<div><strong>Dữ liệu Phường/Xã</strong><br>Chưa có dữ liệu (cần import)</div>";
            echo "</div>";
            $testResults['warnings']++;
        }
    } catch (PDOException $e) {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Dữ liệu Phường/Xã</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 4: Kiểm tra Address Selector Component
    // ============================================
    echo "<h2>🎨 TEST 4: Kiểm tra Address Selector Component</h2>";
    echo "<div class='test-section'>";
    
    $testResults['total']++;
    $componentPath = 'lequocanh/administrator/elements_LQA/mgiohang/address_selector_component.php';
    if (file_exists($componentPath)) {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>✅</span>";
        echo "<div><strong>Address Selector Component</strong><br>File tồn tại: <code>$componentPath</code></div>";
        echo "</div>";
        $testResults['passed']++;
        
        // Check component content
        $content = file_get_contents($componentPath);
        $hasProvinceSelect = strpos($content, 'province-select') !== false;
        $hasDistrictSelect = strpos($content, 'district-select') !== false;
        $hasWardSelect = strpos($content, 'ward-select') !== false;
        $hasJavaScript = strpos($content, 'AddressSelector') !== false;
        
        echo "<div class='test-result test-info'>";
        echo "<span class='icon'>ℹ️</span>";
        echo "<div><strong>Nội dung Component</strong><br>";
        echo "Province selector: " . ($hasProvinceSelect ? '✅' : '❌') . "<br>";
        echo "District selector: " . ($hasDistrictSelect ? '✅' : '❌') . "<br>";
        echo "Ward selector: " . ($hasWardSelect ? '✅' : '❌') . "<br>";
        echo "JavaScript module: " . ($hasJavaScript ? '✅' : '❌');
        echo "</div></div>";
    } else {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Address Selector Component</strong><br>File không tồn tại: <code>$componentPath</code></div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 5: Kiểm tra API Endpoints
    // ============================================
    echo "<h2>🔌 TEST 5: Kiểm tra API Endpoints</h2>";
    echo "<div class='test-section'>";
    
    $apiFiles = [
        'lequocanh/administrator/elements_LQA/mgiohang/get_address_data.php' => 'API lấy dữ liệu địa chỉ',
        'lequocanh/administrator/elements_LQA/mgiohang/calculate_shipping_api.php' => 'API tính phí vận chuyển',
    ];
    
    foreach ($apiFiles as $file => $description) {
        $testResults['total']++;
        if (file_exists($file)) {
            echo "<div class='test-result test-pass'>";
            echo "<span class='icon'>✅</span>";
            echo "<div><strong>$description</strong><br><code>$file</code> - Tồn tại</div>";
            echo "</div>";
            $testResults['passed']++;
        } else {
            echo "<div class='test-result test-fail'>";
            echo "<span class='icon'>❌</span>";
            echo "<div><strong>$description</strong><br><code>$file</code> - Không tồn tại</div>";
            echo "</div>";
            $testResults['failed']++;
        }
    }
    
    echo "</div>";
    
    // ============================================
    // TEST 6: Kiểm tra Bảng don_hang
    // ============================================
    echo "<h2>📦 TEST 6: Kiểm tra Bảng don_hang (Tích hợp)</h2>";
    echo "<div class='test-section'>";
    
    $requiredOrderColumns = [
        'province_id' => 'ID Tỉnh/Thành',
        'district_id' => 'ID Quận/Huyện',
        'ward_id' => 'ID Phường/Xã',
        'dia_chi_giao_hang' => 'Địa chỉ giao hàng'
    ];
    
    try {
        $stmt = $db->query("DESCRIBE don_hang");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredOrderColumns as $col => $desc) {
            $testResults['total']++;
            if (in_array($col, $columns)) {
                echo "<div class='test-result test-pass'>";
                echo "<span class='icon'>✅</span>";
                echo "<div><strong>$desc</strong><br>Cột <code>$col</code> đã tồn tại trong bảng don_hang</div>";
                echo "</div>";
                $testResults['passed']++;
            } else {
                echo "<div class='test-result test-warning'>";
                echo "<span class='icon'>⚠️</span>";
                echo "<div><strong>$desc</strong><br>Cột <code>$col</code> chưa có trong bảng don_hang (cần migration)</div>";
                echo "</div>";
                $testResults['warnings']++;
            }
        }
    } catch (PDOException $e) {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Bảng don_hang</strong><br>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
        $testResults['failed']++;
    }
    
    echo "</div>";
    
    // ============================================
    // SUMMARY
    // ============================================
    $passRate = $testResults['total'] > 0 ? ($testResults['passed'] / $testResults['total']) * 100 : 0;
    
    echo "<div class='summary'>";
    echo "<h2>📊 KẾT QUẢ TỔNG HỢP</h2>";
    
    echo "<div class='progress-bar'>";
    echo "<div class='progress-fill' style='width: {$passRate}%'>" . round($passRate, 1) . "%</div>";
    echo "</div>";
    
    echo "<div class='stats'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>Tổng số test</div>";
    echo "<div class='stat-number'>{$testResults['total']}</div>";
    echo "</div>";
    
    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>✅ Passed</div>";
    echo "<div class='stat-number'>{$testResults['passed']}</div>";
    echo "</div>";
    
    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>❌ Failed</div>";
    echo "<div class='stat-number'>{$testResults['failed']}</div>";
    echo "</div>";
    
    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>⚠️ Warnings</div>";
    echo "<div class='stat-number'>{$testResults['warnings']}</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    
    // Recommendations
    echo "<div class='test-section'>";
    echo "<h2>💡 KHUYẾN NGHỊ</h2>";
    
    if ($testResults['failed'] > 0) {
        echo "<div class='test-result test-fail'>";
        echo "<span class='icon'>❌</span>";
        echo "<div><strong>Phase 1 CHƯA HOÀN THÀNH</strong><br>";
        echo "Có {$testResults['failed']} test thất bại. Cần khắc phục trước khi chuyển sang Phase 2.";
        echo "</div></div>";
        
        echo "<h3>Các bước cần thực hiện:</h3>";
        echo "<ol>";
        echo "<li>Chạy <code>setup_shipping_system.php</code> để tạo database schema</li>";
        echo "<li>Import dữ liệu địa chỉ Việt Nam (provinces, districts, wards)</li>";
        echo "<li>Tạo các API endpoint còn thiếu</li>";
        echo "<li>Chạy lại test này để xác nhận</li>";
        echo "</ol>";
    } else if ($testResults['warnings'] > 0) {
        echo "<div class='test-result test-warning'>";
        echo "<span class='icon'>⚠️</span>";
        echo "<div><strong>Phase 1 CƠ BẢN ĐÃ HOÀN THÀNH</strong><br>";
        echo "Có {$testResults['warnings']} cảnh báo. Nên hoàn thiện trước khi chuyển sang Phase 2.";
        echo "</div></div>";
        
        echo "<h3>Các cải thiện đề xuất:</h3>";
        echo "<ul>";
        if ($districtCount == 0) echo "<li>Import dữ liệu quận/huyện</li>";
        if ($wardCount == 0) echo "<li>Import dữ liệu phường/xã</li>";
        echo "<li>Thêm các cột địa chỉ vào bảng don_hang</li>";
        echo "<li>Test tích hợp với form checkout</li>";
        echo "</ul>";
    } else {
        echo "<div class='test-result test-pass'>";
        echo "<span class='icon'>🎉</span>";
        echo "<div><strong>Phase 1 ĐÃ HOÀN THÀNH XUẤT SẮC!</strong><br>";
        echo "Tất cả các test đều passed. Sẵn sàng chuyển sang Phase 2.";
        echo "</div></div>";
        
        echo "<h3>Bước tiếp theo - Phase 2:</h3>";
        echo "<ul>";
        echo "<li>✅ Tạo bảng shipping_fees, shipping_methods</li>";
        echo "<li>✅ Module quản lý cấu hình phí</li>";
        echo "<li>✅ API tính phí tự động</li>";
        echo "<li>✅ Tích hợp vào checkout</li>";
        echo "</ul>";
    }
    
    echo "</div>";
    
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='check_shipping_system.php' style='display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);'>🔍 Xem Chi Tiết Hệ Thống</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'>";
    echo "<span class='icon'>❌</span>";
    echo "<div><strong>LỖI NGHIÊM TRỌNG</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "<pre style='margin-top: 10px; background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div></div>";
}

echo "</div></body></html>";
?>
