<?php
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

$cartWeight = $_SESSION['cart_weight'] ?? 1.0;
$cartValue = $_SESSION['cart_total'] ?? 0;
$provinceId = $_SESSION['province_id'] ?? 1;
$districtId = $_SESSION['district_id'] ?? 1;

// Query giống file FIXED
$stmt = $db->query("
    SELECT * FROM v_shipping_methods_with_fees 
    WHERE is_active = 1 
    ORDER BY sort_order DESC
");

$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== DEBUG RENDER LOOP ===\n\n";
echo "Số methods từ query: " . count($shippingMethods) . "\n\n";

// Tính phí
foreach ($shippingMethods as &$method) {
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
    $stmt->execute([$method['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $method['calculated_fee'] = $result['fee'] ?? 0;
    
    $stmt = $db->prepare("
        SELECT base_fee, fee_per_kg, min_order_free_ship
        FROM shipping_fees
        WHERE shipping_method_id = ? AND is_active = 1
        ORDER BY priority DESC
        LIMIT 1
    ");
    $stmt->execute([$method['id']]);
    $feeDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $method['base_fee'] = $feeDetail['base_fee'] ?? 0;
    $method['fee_per_kg'] = $feeDetail['fee_per_kg'] ?? 0;
    $method['min_free_ship'] = $feeDetail['min_order_free_ship'] ?? 0;
    $method['is_free'] = ($method['calculated_fee'] == 0);
}

echo "Sau khi tính phí:\n";
echo str_repeat("-", 80) . "\n";
foreach ($shippingMethods as $idx => $m) {
    echo "[$idx] ID:{$m['id']} Code:{$m['code']} Name:{$m['name']} Fee:{$m['calculated_fee']}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Simulate foreach render:\n";
echo str_repeat("-", 80) . "\n";

$renderCount = 0;
foreach ($shippingMethods as $index => $method) {
    $renderCount++;
    echo "Render #{$renderCount}: Index={$index} Code={$method['code']} Name={$method['name']}\n";
}

echo "\nTổng số lần render: {$renderCount}\n";

if ($renderCount === 4) {
    echo "✅ Render đúng 4 lần\n";
} else {
    echo "❌ Render sai: {$renderCount} lần (expected: 4)\n";
}
