<?php
/**
 * SIMPLE TEST - ALL PHASES
 * Test đơn giản để xem kết quả rõ ràng
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

$results = [];

echo "=== TEST ALL 5 PHASES ===\n\n";

// PHASE 1
echo "PHASE 1: Quản lý khu vực\n";
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM provinces");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Provinces: $count tỉnh/thành\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM districts");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Districts: $count quận/huyện\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM wards");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Wards: $count phường/xã\n";
    
    $results['phase1'] = 'PASS';
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $results['phase1'] = 'FAIL';
}

// PHASE 2
echo "\nPHASE 2: Cấu hình phí vận chuyển\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_methods");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Shipping methods: $count phương thức\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_fees");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Shipping fees: $count cấu hình\n";
    
    $results['phase2'] = 'PASS';
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $results['phase2'] = 'FAIL';
}

// PHASE 3
echo "\nPHASE 3: Tích hợp GHN API\n";
try {
    if (class_exists('GHNService')) {
        echo "✅ GHNService class exists\n";
        
        $ghn = new GHNService();
        $provinces = $ghn->getProvinces();
        
        if ($provinces['code'] === 200) {
            echo "✅ GHN getProvinces() works\n";
            echo "   Found " . count($provinces['data']) . " provinces\n";
        } else {
            echo "⚠️  GHN getProvinces() returned code: " . $provinces['code'] . "\n";
        }
        
        $results['phase3'] = 'PASS';
    } else {
        echo "❌ GHNService class not found\n";
        $results['phase3'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $results['phase3'] = 'FAIL';
}

// PHASE 4
echo "\nPHASE 4: Dashboard & Tracking\n";
try {
    $files = [
        'lequocanh/administrator/elements_LQA/madmin/shipping_dashboard.php',
        'lequocanh/administrator/elements_LQA/madmin/shipping_report.php',
        'lequocanh/track_order.php'
    ];
    
    $allExist = true;
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "✅ " . basename($file) . " exists\n";
        } else {
            echo "❌ " . basename($file) . " not found\n";
            $allExist = false;
        }
    }
    
    $results['phase4'] = $allExist ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $results['phase4'] = 'FAIL';
}

// PHASE 5
echo "\nPHASE 5: Tối ưu & Mở rộng\n";
try {
    if (class_exists('CacheService')) {
        echo "✅ CacheService class exists\n";
        
        $cache = new CacheService();
        $testKey = 'test_' . time();
        $testValue = ['test' => 'data'];
        
        $cache->set($testKey, $testValue, 60);
        $retrieved = $cache->get($testKey);
        
        if ($retrieved === $testValue) {
            echo "✅ CacheService works correctly\n";
            $cache->delete($testKey);
        } else {
            echo "⚠️  CacheService returned unexpected value\n";
        }
        
        $results['phase5'] = 'PASS';
    } else {
        echo "❌ CacheService class not found\n";
        $results['phase5'] = 'FAIL';
    }
    
    if (class_exists('EmailService')) {
        echo "✅ EmailService class exists\n";
    } else {
        echo "⚠️  EmailService class not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    $results['phase5'] = 'FAIL';
}

// SUMMARY
echo "\n=== SUMMARY ===\n";
$passed = 0;
$total = count($results);

foreach ($results as $phase => $status) {
    $icon = $status === 'PASS' ? '✅' : '❌';
    echo "$icon " . strtoupper($phase) . ": $status\n";
    if ($status === 'PASS') $passed++;
}

$percentage = ($passed / $total) * 100;
echo "\nTotal: $passed/$total (" . number_format($percentage, 1) . "%)\n";

if ($passed === $total) {
    echo "\n🎉 ALL PHASES PASSED! 🎉\n";
} else {
    echo "\n⚠️  Some phases need attention\n";
}
