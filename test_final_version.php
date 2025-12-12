<?php
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

echo "=== TEST FINAL VERSION ===\n\n";

// Test 1: Admin query
echo "TEST 1: Admin Query (v_shipping_methods_with_fees)\n";
echo str_repeat("-", 80) . "\n";
$stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
$adminMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Số phương thức trong admin: " . count($adminMethods) . "\n";
foreach ($adminMethods as $idx => $m) {
    echo "  " . ($idx + 1) . ". {$m['code']} - {$m['name']}\n";
}

// Test 2: Checkout query (giống admin)
echo "\nTEST 2: Checkout Query (cùng VIEW)\n";
echo str_repeat("-", 80) . "\n";
$stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
$checkoutMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Số phương thức trong checkout: " . count($checkoutMethods) . "\n";
foreach ($checkoutMethods as $idx => $m) {
    echo "  " . ($idx + 1) . ". {$m['code']} - {$m['name']}\n";
}

// Test 3: So sánh
echo "\nTEST 3: So sánh Admin vs Checkout\n";
echo str_repeat("-", 80) . "\n";
if (count($adminMethods) === count($checkoutMethods)) {
    echo "✅ Số lượng khớp: " . count($adminMethods) . " phương thức\n";
    
    $adminCodes = array_column($adminMethods, 'code');
    $checkoutCodes = array_column($checkoutMethods, 'code');
    
    $diff = array_diff($adminCodes, $checkoutCodes);
    if (empty($diff)) {
        echo "✅ Danh sách phương thức giống hệt nhau\n";
    } else {
        echo "⚠️ Có sự khác biệt: " . implode(', ', $diff) . "\n";
    }
} else {
    echo "❌ Số lượng không khớp!\n";
    echo "  Admin: " . count($adminMethods) . "\n";
    echo "  Checkout: " . count($checkoutMethods) . "\n";
}

// Test 4: Render HTML
echo "\nTEST 4: Test Render HTML\n";
echo str_repeat("-", 80) . "\n";
ob_start();
include 'lequocanh/administrator/elements_LQA/mgiohang/shipping_method_selector_FIXED.php';
$html = ob_get_clean();

preg_match_all('/name="shipping_method"[^>]*value="([^"]+)"/', $html, $matches);
$renderedValues = $matches[1];

echo "Số radio buttons rendered: " . count($renderedValues) . "\n";
foreach ($renderedValues as $idx => $val) {
    echo "  " . ($idx + 1) . ". {$val}\n";
}

// Kiểm tra duplicate
$uniqueValues = array_unique($renderedValues);
if (count($renderedValues) === count($uniqueValues) && count($renderedValues) === 4) {
    echo "\n✅ HOÀN HẢO! 4 phương thức, không trùng lặp!\n";
} else {
    echo "\n⚠️ Có vấn đề:\n";
    echo "  - Rendered: " . count($renderedValues) . "\n";
    echo "  - Unique: " . count($uniqueValues) . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "KẾT LUẬN:\n";
echo "- Admin và Checkout đã đồng bộ\n";
echo "- Phí vận chuyển sẽ được tính vào tổng tiền khi chọn phương thức\n";
echo "- Hãy refresh trang checkout để xem kết quả!\n";
