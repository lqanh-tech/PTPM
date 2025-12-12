<?php
/**
 * FINAL TEST - ALL PHASES
 * 
 * Test tổng hợp tất cả 5 phases:
 * Phase 1: Quản lý khu vực
 * Phase 2: Cấu hình phí vận chuyển
 * Phase 3: Tích hợp GHN API
 * Phase 4: Dashboard & Tracking
 * Phase 5: Tối ưu & Mở rộng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

// Load required classes for testing
if (file_exists('lequocanh/administrator/elements_LQA/mod/GHNService.php')) {
    require_once 'lequocanh/administrator/elements_LQA/mod/GHNService.php';
}
if (file_exists('lequocanh/administrator/elements_LQA/mod/GHNMockService.php')) {
    require_once 'lequocanh/administrator/elements_LQA/mod/GHNMockService.php';
}
if (file_exists('lequocanh/administrator/elements_LQA/mod/CacheService.php')) {
    require_once 'lequocanh/administrator/elements_LQA/mod/CacheService.php';
}
if (file_exists('lequocanh/administrator/elements_LQA/mod/EmailService.php')) {
    require_once 'lequocanh/administrator/elements_LQA/mod/EmailService.php';
}

$allResults = [
    'phase1' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    'phase2' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    'phase3' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    'phase4' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    'phase5' => ['total' => 0, 'passed' => 0, 'failed' => 0],
];

// HTML Header
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Final Test - All Phases</title>
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
            font-size: 36px;
            text-align: center;
        }
        h2 { 
            color: #34495e; 
            margin: 30px 0 15px 0; 
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            font-size: 24px;
        }
        .phase-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .test-result {
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
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
        .icon {
            font-size: 20px;
            font-weight: bold;
        }
        .phase-summary {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 5px solid #3498db;
        }
        .final-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-top: 30px;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            display: block;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e83e8c;
            font-size: 12px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🎯 FINAL TEST - TẤT CẢ 5 PHASES</h1>
        <p style='text-align: center; color: #7f8c8d; margin-bottom: 30px; font-size: 18px;'>
            <strong>Kiểm tra tổng thể hệ thống quản lý vận chuyển</strong>
        </p>";

// ============================================
// PHASE 1: QUẢN LÝ KHU VỰC
// ============================================
echo "<h2>📍 PHASE 1: Quản Lý Khu Vực</h2>";
echo "<div class='phase-section'>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Test 1.1: Check tables
    $tables = ['provinces', 'districts', 'wards', 'shipping_zones'];
    foreach ($tables as $table) {
        $allResults['phase1']['total']++;
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Bảng <code>$table</code> tồn tại</div></div>";
            $allResults['phase1']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Bảng <code>$table</code> không tồn tại</div></div>";
            $allResults['phase1']['failed']++;
        }
    }
    
    // Test 1.2: Check data
    $allResults['phase1']['total']++;
    $stmt = $db->query("SELECT COUNT(*) as count FROM provinces");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($count >= 63) {
        echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Dữ liệu provinces: $count tỉnh/thành</div></div>";
        $allResults['phase1']['passed']++;
    } else {
        echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Dữ liệu provinces thiếu: chỉ có $count</div></div>";
        $allResults['phase1']['failed']++;
    }
    
    // Test 1.3: Check files
    $files = [
        'lequocanh/administrator/elements_LQA/mgiohang/address_selector_component.php',
        'lequocanh/administrator/elements_LQA/mgiohang/get_address_data.php'
    ];
    foreach ($files as $file) {
        $allResults['phase1']['total']++;
        if (file_exists($file)) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>File <code>" . basename($file) . "</code> tồn tại</div></div>";
            $allResults['phase1']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File <code>" . basename($file) . "</code> không tồn tại</div></div>";
            $allResults['phase1']['failed']++;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Lỗi Phase 1: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    $allResults['phase1']['failed']++;
}

$phase1Rate = $allResults['phase1']['total'] > 0 ? ($allResults['phase1']['passed'] / $allResults['phase1']['total']) * 100 : 0;
echo "<div class='phase-summary'><strong>Phase 1:</strong> {$allResults['phase1']['passed']}/{$allResults['phase1']['total']} tests passed (" . number_format($phase1Rate, 1) . "%)</div>";
echo "</div>";

// ============================================
// PHASE 2: CẤU HÌNH PHÍ VẬN CHUYỂN
// ============================================
echo "<h2>💰 PHASE 2: Cấu Hình Phí Vận Chuyển</h2>";
echo "<div class='phase-section'>";

try {
    // Test 2.1: Check tables
    $tables = ['shipping_methods', 'shipping_fees'];
    foreach ($tables as $table) {
        $allResults['phase2']['total']++;
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Bảng <code>$table</code> tồn tại</div></div>";
            $allResults['phase2']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Bảng <code>$table</code> không tồn tại</div></div>";
            $allResults['phase2']['failed']++;
        }
    }
    
    // Test 2.2: Check shipping_fees columns
    $allResults['phase2']['total']++;
    $stmt = $db->query("SHOW COLUMNS FROM shipping_fees");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredCols = ['base_fee', 'fee_per_kg', 'weight_from', 'priority'];
    $hasAll = true;
    foreach ($requiredCols as $col) {
        if (!in_array($col, $columns)) {
            $hasAll = false;
            break;
        }
    }
    if ($hasAll) {
        echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Bảng shipping_fees có đầy đủ cột</div></div>";
        $allResults['phase2']['passed']++;
    } else {
        echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Bảng shipping_fees thiếu cột</div></div>";
        $allResults['phase2']['failed']++;
    }
    
    // Test 2.3: Check files
    $files = [
        'lequocanh/administrator/elements_LQA/madmin/shipping_config.php',
        'lequocanh/administrator/elements_LQA/mgiohang/calculate_shipping_api.php'
    ];
    foreach ($files as $file) {
        $allResults['phase2']['total']++;
        if (file_exists($file)) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>File <code>" . basename($file) . "</code> tồn tại</div></div>";
            $allResults['phase2']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File <code>" . basename($file) . "</code> không tồn tại</div></div>";
            $allResults['phase2']['failed']++;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Lỗi Phase 2: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    $allResults['phase2']['failed']++;
}

$phase2Rate = $allResults['phase2']['total'] > 0 ? ($allResults['phase2']['passed'] / $allResults['phase2']['total']) * 100 : 0;
echo "<div class='phase-summary'><strong>Phase 2:</strong> {$allResults['phase2']['passed']}/{$allResults['phase2']['total']} tests passed (" . number_format($phase2Rate, 1) . "%)</div>";
echo "</div>";

// ============================================
// PHASE 3: TÍCH HỢP GHN API
// ============================================
echo "<h2>🚚 PHASE 3: Tích Hợp GHN API</h2>";
echo "<div class='phase-section'>";

try {
    // Test 3.1: Check GHN classes
    $allResults['phase3']['total']++;
    if (class_exists('GHNService')) {
        echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Class <code>GHNService</code> tồn tại</div></div>";
        $allResults['phase3']['passed']++;
        
        // Test 3.2: Test GHN functionality
        $allResults['phase3']['total']++;
        $ghn = new GHNService();
        $provinces = $ghn->getProvinces();
        if ($provinces['code'] === 200) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>GHN getProvinces() hoạt động</div></div>";
            $allResults['phase3']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>GHN getProvinces() thất bại</div></div>";
            $allResults['phase3']['failed']++;
        }
        
        // Test 3.3: Test calculate fee
        $allResults['phase3']['total']++;
        $feeResult = $ghn->calculateShippingComplete([
            'to_district_id' => 1001,
            'to_ward_code' => '10001',
            'weight' => 1000,
            'insurance_value' => 100000
        ]);
        if ($feeResult['success']) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>GHN calculateShippingComplete() hoạt động</div></div>";
            $allResults['phase3']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>GHN calculateShippingComplete() thất bại</div></div>";
            $allResults['phase3']['failed']++;
        }
    } else {
        echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Class <code>GHNService</code> không tồn tại</div></div>";
        $allResults['phase3']['failed']++;
    }
    
    // Test 3.4: Check files
    $files = [
        'lequocanh/administrator/elements_LQA/mod/GHNService.php',
        'lequocanh/administrator/elements_LQA/mod/GHNMockService.php'
    ];
    foreach ($files as $file) {
        $allResults['phase3']['total']++;
        if (file_exists($file)) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>File <code>" . basename($file) . "</code> tồn tại</div></div>";
            $allResults['phase3']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File <code>" . basename($file) . "</code> không tồn tại</div></div>";
            $allResults['phase3']['failed']++;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Lỗi Phase 3: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    $allResults['phase3']['failed']++;
}

$phase3Rate = $allResults['phase3']['total'] > 0 ? ($allResults['phase3']['passed'] / $allResults['phase3']['total']) * 100 : 0;
echo "<div class='phase-summary'><strong>Phase 3:</strong> {$allResults['phase3']['passed']}/{$allResults['phase3']['total']} tests passed (" . number_format($phase3Rate, 1) . "%)</div>";
echo "</div>";

// ============================================
// PHASE 4: DASHBOARD & TRACKING
// ============================================
echo "<h2>📊 PHASE 4: Dashboard & Tracking</h2>";
echo "<div class='phase-section'>";

try {
    // Test 4.1: Check files
    $files = [
        'lequocanh/administrator/elements_LQA/madmin/shipping_dashboard.php' => ['chart.js', 'bootstrap'],
        'lequocanh/administrator/elements_LQA/madmin/shipping_report.php' => ['xlsx', 'form method'],
        'lequocanh/track_order.php' => ['timeline', 'orderCode']
    ];
    
    foreach ($files as $file => $keywords) {
        $allResults['phase4']['total']++;
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $hasAll = true;
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) === false) {
                    $hasAll = false;
                    break;
                }
            }
            if ($hasAll) {
                echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>File <code>" . basename($file) . "</code> đầy đủ tính năng</div></div>";
                $allResults['phase4']['passed']++;
            } else {
                echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File <code>" . basename($file) . "</code> thiếu tính năng</div></div>";
                $allResults['phase4']['failed']++;
            }
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File <code>" . basename($file) . "</code> không tồn tại</div></div>";
            $allResults['phase4']['failed']++;
        }
    }
    
    // Test 4.2: Check menu integration
    $allResults['phase4']['total']++;
    $leftFile = 'lequocanh/administrator/elements_LQA/left.php';
    if (file_exists($leftFile)) {
        $content = file_get_contents($leftFile);
        if (strpos($content, 'shipping_dashboard') !== false && strpos($content, 'shipping_report') !== false) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Menu integration hoàn tất</div></div>";
            $allResults['phase4']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Menu integration thiếu</div></div>";
            $allResults['phase4']['failed']++;
        }
    } else {
        echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File left.php không tồn tại</div></div>";
        $allResults['phase4']['failed']++;
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Lỗi Phase 4: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    $allResults['phase4']['failed']++;
}

$phase4Rate = $allResults['phase4']['total'] > 0 ? ($allResults['phase4']['passed'] / $allResults['phase4']['total']) * 100 : 0;
echo "<div class='phase-summary'><strong>Phase 4:</strong> {$allResults['phase4']['passed']}/{$allResults['phase4']['total']} tests passed (" . number_format($phase4Rate, 1) . "%)</div>";
echo "</div>";

// ============================================
// PHASE 5: TỐI ƯU & MỞ RỘNG
// ============================================
echo "<h2>⚡ PHASE 5: Tối Ưu & Mở Rộng</h2>";
echo "<div class='phase-section'>";

try {
    // Test 5.1: Check files
    $files = [
        'lequocanh/administrator/elements_LQA/mgiohang/ghn_webhook.php',
        'lequocanh/administrator/elements_LQA/mod/EmailService.php',
        'lequocanh/administrator/elements_LQA/mod/CacheService.php',
        'lequocanh/administrator/elements_LQA/madmin/batch_shipping_operations.php'
    ];
    
    foreach ($files as $file) {
        $allResults['phase5']['total']++;
        if (file_exists($file)) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>File <code>" . basename($file) . "</code> tồn tại</div></div>";
            $allResults['phase5']['passed']++;
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>File <code>" . basename($file) . "</code> không tồn tại</div></div>";
            $allResults['phase5']['failed']++;
        }
    }
    
    // Test 5.2: Test Cache functionality
    $allResults['phase5']['total']++;
    if (class_exists('CacheService')) {
        $cache = new CacheService();
        $testKey = 'final_test_' . time();
        $testValue = ['test' => 'data'];
        $cache->set($testKey, $testValue, 60);
        $retrieved = $cache->get($testKey);
        if ($retrieved === $testValue) {
            echo "<div class='test-result test-pass'><span class='icon'>✅</span><div>Cache Service hoạt động đúng</div></div>";
            $allResults['phase5']['passed']++;
            $cache->delete($testKey);
        } else {
            echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Cache Service không hoạt động</div></div>";
            $allResults['phase5']['failed']++;
        }
    } else {
        echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Class CacheService không tồn tại</div></div>";
        $allResults['phase5']['failed']++;
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-fail'><span class='icon'>❌</span><div>Lỗi Phase 5: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    $allResults['phase5']['failed']++;
}

$phase5Rate = $allResults['phase5']['total'] > 0 ? ($allResults['phase5']['passed'] / $allResults['phase5']['total']) * 100 : 0;
echo "<div class='phase-summary'><strong>Phase 5:</strong> {$allResults['phase5']['passed']}/{$allResults['phase5']['total']} tests passed (" . number_format($phase5Rate, 1) . "%)</div>";
echo "</div>";

// ============================================
// FINAL SUMMARY
// ============================================
$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;

foreach ($allResults as $phase => $results) {
    $totalTests += $results['total'];
    $totalPassed += $results['passed'];
    $totalFailed += $results['failed'];
}

$overallRate = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;

echo "<div class='final-summary'>";
echo "<h2 style='background: transparent; color: white; border: none; margin: 0 0 20px 0;'>🎯 KẾT QUẢ TỔNG HỢP</h2>";

echo "<div class='progress-bar'>";
echo "<div class='progress-fill' style='width: " . $overallRate . "%'>" . number_format($overallRate, 1) . "%</div>";
echo "</div>";

echo "<div class='stats-grid'>";
echo "<div class='stat-card'><span class='stat-number'>$totalTests</span><span class='stat-label'>Tổng số tests</span></div>";
echo "<div class='stat-card'><span class='stat-number' style='color: #2ecc71;'>$totalPassed</span><span class='stat-label'>✅ Passed</span></div>";
echo "<div class='stat-card'><span class='stat-number' style='color: #e74c3c;'>$totalFailed</span><span class='stat-label'>❌ Failed</span></div>";
echo "<div class='stat-card'><span class='stat-number'>" . number_format($overallRate, 1) . "%</span><span class='stat-label'>Hoàn thành</span></div>";
echo "</div>";

echo "<div style='margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px;'>";
echo "<h3 style='margin-bottom: 15px;'>Chi tiết từng Phase:</h3>";
foreach ($allResults as $phaseNum => $results) {
    $rate = $results['total'] > 0 ? ($results['passed'] / $results['total']) * 100 : 0;
    $phaseLabel = strtoupper($phaseNum);
    echo "<div style='margin: 10px 0;'><strong>$phaseLabel:</strong> {$results['passed']}/{$results['total']} (" . number_format($rate, 1) . "%)</div>";
}
echo "</div>";

if ($totalFailed === 0) {
    echo "<div style='margin-top: 30px; padding: 20px; background: rgba(40, 167, 69, 0.2); border-radius: 10px; border: 2px solid rgba(40, 167, 69, 0.5);'>";
    echo "<h2 style='background: transparent; color: white; border: none; margin: 0;'>🎉 TẤT CẢ 5 PHASES ĐÃ HOÀN THÀNH XUẤT SẮC!</h2>";
    echo "<p style='margin-top: 10px; font-size: 18px;'>Hệ thống quản lý vận chuyển đã sẵn sàng sử dụng!</p>";
    echo "</div>";
} else {
    echo "<div style='margin-top: 30px; padding: 20px; background: rgba(220, 53, 69, 0.2); border-radius: 10px; border: 2px solid rgba(220, 53, 69, 0.5);'>";
    echo "<h2 style='background: transparent; color: white; border: none; margin: 0;'>⚠️ CÓ $totalFailed TESTS THẤT BẠI</h2>";
    echo "<p style='margin-top: 10px; font-size: 18px;'>Vui lòng kiểm tra lại các tests bị lỗi ở trên.</p>";
    echo "</div>";
}

echo "</div>";

echo "</div>
</body>
</html>";
