<?php
/**
 * DETAILED TEST - ALL PHASES
 * Test chi tiết từng tính năng của 5 phases
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

// Load required classes
$classFiles = [
    'lequocanh/administrator/elements_LQA/mod/GHNService.php',
    'lequocanh/administrator/elements_LQA/mod/GHNMockService.php',
    'lequocanh/administrator/elements_LQA/mod/CacheService.php',
    'lequocanh/administrator/elements_LQA/mod/EmailService.php'
];

foreach ($classFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

$allTests = [];
$db = Database::getInstance()->getConnection();

function test($name, $callback) {
    global $allTests;
    try {
        $result = $callback();
        $allTests[] = [
            'name' => $name,
            'status' => $result ? 'PASS' : 'FAIL',
            'message' => ''
        ];
        echo ($result ? '✅' : '❌') . " $name\n";
        return $result;
    } catch (Exception $e) {
        $allTests[] = [
            'name' => $name,
            'status' => 'FAIL',
            'message' => $e->getMessage()
        ];
        echo "❌ $name: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         DETAILED TEST - ALL 5 PHASES                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ============================================
// PHASE 1: QUẢN LÝ KHU VỰC
// ============================================
echo "┌─ PHASE 1: Quản lý khu vực ─────────────────────────────────┐\n";

test("1.1. Bảng provinces tồn tại", function() use ($db) {
    $stmt = $db->query("SHOW TABLES LIKE 'provinces'");
    return $stmt->rowCount() > 0;
});

test("1.2. Dữ liệu provinces đầy đủ (>= 63)", function() use ($db) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM provinces");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   → Có $count tỉnh/thành\n";
    return $count >= 63;
});

test("1.3. Bảng districts tồn tại và có dữ liệu", function() use ($db) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM districts");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   → Có $count quận/huyện\n";
    return $count > 0;
});

test("1.4. Bảng wards tồn tại và có dữ liệu", function() use ($db) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM wards");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   → Có $count phường/xã\n";
    return $count > 0;
});

test("1.5. Foreign key provinces -> districts hoạt động", function() use ($db) {
    $stmt = $db->query("SELECT d.* FROM districts d JOIN provinces p ON d.province_id = p.id LIMIT 1");
    return $stmt->rowCount() > 0;
});

test("1.6. Foreign key districts -> wards hoạt động", function() use ($db) {
    $stmt = $db->query("SELECT w.* FROM wards w JOIN districts d ON w.district_id = d.id LIMIT 1");
    return $stmt->rowCount() > 0;
});

test("1.7. File address_selector_component.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mgiohang/address_selector_component.php');
});

test("1.8. File get_address_data.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mgiohang/get_address_data.php');
});

echo "└────────────────────────────────────────────────────────────┘\n\n";

// ============================================
// PHASE 2: CẤU HÌNH PHÍ VẬN CHUYỂN
// ============================================
echo "┌─ PHASE 2: Cấu hình phí vận chuyển ─────────────────────────┐\n";

test("2.1. Bảng shipping_methods tồn tại", function() use ($db) {
    $stmt = $db->query("SHOW TABLES LIKE 'shipping_methods'");
    return $stmt->rowCount() > 0;
});

test("2.2. Có ít nhất 3 phương thức vận chuyển", function() use ($db) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_methods WHERE is_active = 1");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   → Có $count phương thức\n";
    return $count >= 3;
});

test("2.3. Bảng shipping_fees tồn tại", function() use ($db) {
    $stmt = $db->query("SHOW TABLES LIKE 'shipping_fees'");
    return $stmt->rowCount() > 0;
});

test("2.4. Bảng shipping_fees có đầy đủ cột", function() use ($db) {
    $stmt = $db->query("SHOW COLUMNS FROM shipping_fees");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['base_fee', 'fee_per_kg', 'weight_from', 'weight_to', 'priority'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            echo "   → Thiếu cột: $col\n";
            return false;
        }
    }
    return true;
});

test("2.5. Có cấu hình phí mẫu", function() use ($db) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_fees");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   → Có $count cấu hình\n";
    return $count > 0;
});

test("2.6. File shipping_config.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/madmin/shipping_config.php');
});

test("2.7. File calculate_shipping_api.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mgiohang/calculate_shipping_api.php');
});

test("2.8. View v_shipping_fees_detail tồn tại", function() use ($db) {
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_sales_management = 'v_shipping_fees_detail'");
    return $stmt->rowCount() > 0;
});

echo "└────────────────────────────────────────────────────────────┘\n\n";

// ============================================
// PHASE 3: TÍCH HỢP GHN API
// ============================================
echo "┌─ PHASE 3: Tích hợp GHN API ────────────────────────────────┐\n";

test("3.1. File GHNService.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mod/GHNService.php');
});

test("3.2. File GHNMockService.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mod/GHNMockService.php');
});

test("3.3. Class GHNService tồn tại", function() {
    return class_exists('GHNService');
});

test("3.4. GHNService::getProvinces() hoạt động", function() {
    $ghn = new GHNService();
    $result = $ghn->getProvinces();
    echo "   → Code: {$result['code']}, Data count: " . count($result['data']) . "\n";
    return $result['code'] === 200;
});

test("3.5. GHNService::getDistricts() hoạt động", function() {
    $ghn = new GHNService();
    $result = $ghn->getDistricts(201); // Hà Nội
    echo "   → Code: {$result['code']}\n";
    return $result['code'] === 200;
});

test("3.6. GHNService::calculateShippingComplete() hoạt động", function() {
    $ghn = new GHNService();
    $result = $ghn->calculateShippingComplete([
        'to_district_id' => 1001,
        'to_ward_code' => '10001',
        'weight' => 1000,
        'insurance_value' => 100000
    ]);
    echo "   → Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    if ($result['success'] && isset($result['data']['total'])) {
        echo "   → Total fee: " . number_format($result['data']['total']) . " VNĐ\n";
    }
    return $result['success'];
});

test("3.7. GHNService::createShippingOrder() hoạt động", function() {
    $ghn = new GHNService();
    $result = $ghn->createShippingOrder([
        'to_name' => 'Test User',
        'to_phone' => '0123456789',
        'to_address' => 'Test Address',
        'to_ward_code' => '10001',
        'to_district_id' => 1001,
        'weight' => 1000,
        'cod_amount' => 100000,
        'items' => [
            ['name' => 'Test Product', 'quantity' => 1, 'price' => 100000]
        ]
    ]);
    echo "   → Code: {$result['code']}\n";
    return $result['code'] === 200;
});

echo "└────────────────────────────────────────────────────────────┘\n\n";

// ============================================
// PHASE 4: DASHBOARD & TRACKING
// ============================================
echo "┌─ PHASE 4: Dashboard & Tracking ────────────────────────────┐\n";

test("4.1. File shipping_dashboard.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/madmin/shipping_dashboard.php');
});

test("4.2. Dashboard có biểu đồ (chart.js)", function() {
    $content = file_get_contents('lequocanh/administrator/elements_LQA/madmin/shipping_dashboard.php');
    return stripos($content, 'chart.js') !== false;
});

test("4.3. File shipping_report.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/madmin/shipping_report.php');
});

test("4.4. Report có xuất Excel", function() {
    $content = file_get_contents('lequocanh/administrator/elements_LQA/madmin/shipping_report.php');
    return stripos($content, 'xlsx') !== false || stripos($content, 'excel') !== false;
});

test("4.5. File track_order.php tồn tại", function() {
    return file_exists('lequocanh/track_order.php');
});

test("4.6. Tracking page có timeline", function() {
    $content = file_get_contents('lequocanh/track_order.php');
    return stripos($content, 'timeline') !== false;
});

test("4.7. Menu admin có tích hợp shipping", function() {
    $content = file_get_contents('lequocanh/administrator/elements_LQA/left.php');
    return stripos($content, 'shipping_dashboard') !== false;
});

echo "└────────────────────────────────────────────────────────────┘\n\n";

// ============================================
// PHASE 5: TỐI ƯU & MỞ RỘNG
// ============================================
echo "┌─ PHASE 5: Tối ưu & Mở rộng ────────────────────────────────┐\n";

test("5.1. File CacheService.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mod/CacheService.php');
});

test("5.2. Class CacheService tồn tại", function() {
    return class_exists('CacheService');
});

test("5.3. CacheService::set() hoạt động", function() {
    $cache = new CacheService();
    $key = 'test_' . time();
    $value = ['test' => 'data', 'time' => time()];
    $cache->set($key, $value, 60);
    $retrieved = $cache->get($key);
    $cache->delete($key);
    return $retrieved === $value;
});

test("5.4. File EmailService.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mod/EmailService.php');
});

test("5.5. Class EmailService tồn tại", function() {
    return class_exists('EmailService');
});

test("5.6. File ghn_webhook.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/mgiohang/ghn_webhook.php');
});

test("5.7. File batch_shipping_operations.php tồn tại", function() {
    return file_exists('lequocanh/administrator/elements_LQA/madmin/batch_shipping_operations.php');
});

echo "└────────────────────────────────────────────────────────────┘\n\n";

// ============================================
// SUMMARY
// ============================================
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    SUMMARY                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$phases = [
    'PHASE 1' => [1, 8],
    'PHASE 2' => [9, 16],
    'PHASE 3' => [17, 23],
    'PHASE 4' => [24, 30],
    'PHASE 5' => [31, 37]
];

$totalPassed = 0;
$totalTests = count($allTests);

foreach ($phases as $phaseName => $range) {
    $passed = 0;
    $total = 0;
    
    for ($i = $range[0] - 1; $i < $range[1] && $i < $totalTests; $i++) {
        $total++;
        if ($allTests[$i]['status'] === 'PASS') {
            $passed++;
            $totalPassed++;
        }
    }
    
    $percentage = $total > 0 ? ($passed / $total) * 100 : 0;
    $icon = $passed === $total ? '✅' : '⚠️';
    
    echo "$icon $phaseName: $passed/$total (" . number_format($percentage, 1) . "%)\n";
}

$overallPercentage = ($totalPassed / $totalTests) * 100;

echo "\n" . str_repeat("─", 64) . "\n";
echo "OVERALL: $totalPassed/$totalTests (" . number_format($overallPercentage, 1) . "%)\n";
echo str_repeat("─", 64) . "\n\n";

if ($totalPassed === $totalTests) {
    echo "🎉🎉🎉 ALL TESTS PASSED! SYSTEM READY! 🎉🎉🎉\n";
} else {
    $failed = $totalTests - $totalPassed;
    echo "⚠️  $failed test(s) failed. Please review above.\n";
}

echo "\n";
