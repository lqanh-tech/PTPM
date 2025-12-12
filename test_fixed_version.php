<?php
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

ob_start();
include 'lequocanh/administrator/elements_LQA/mgiohang/shipping_method_selector_FIXED.php';
$html = ob_get_clean();

echo "=== TEST FIXED VERSION ===\n\n";

// Đếm số radio buttons
preg_match_all('/name="shipping_method"[^>]*value="([^"]+)"/', $html, $matches);
$values = $matches[1];

echo "Số radio buttons: " . count($values) . "\n";
echo "Danh sách values:\n";
foreach ($values as $idx => $val) {
    echo "  " . ($idx + 1) . ". {$val}\n";
}

// Kiểm tra duplicate
$uniqueValues = array_unique($values);
if (count($values) === count($uniqueValues)) {
    echo "\n✅ THÀNH CÔNG! Không còn duplicate!\n";
    echo "Hãy refresh trang checkout để xem kết quả.\n";
} else {
    echo "\n❌ Vẫn còn duplicate:\n";
    $duplicates = array_diff_assoc($values, $uniqueValues);
    print_r($duplicates);
}
