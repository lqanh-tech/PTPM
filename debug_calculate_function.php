<?php
/**
 * Debug function calculate_shipping_fee
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔍 Debug function calculate_shipping_fee\n\n";

// Test với Standard
$standardId = 1;
$weight = 2.5;
$value = 300000;

echo "Test: Standard (ID: {$standardId}), {$weight}kg, " . number_format($value, 0, ',', '.') . " đ\n\n";

// Kiểm tra dữ liệu trong bảng
echo "📋 Dữ liệu trong shipping_fees:\n";
$stmt = $db->prepare("
    SELECT * FROM shipping_fees 
    WHERE shipping_method_id = ? AND is_active = 1
");
$stmt->execute([$standardId]);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($fees as $f) {
    echo "  ID {$f['id']}: {$f['name']}\n";
    echo "    base_fee: {$f['base_fee']}\n";
    echo "    fee_per_kg: {$f['fee_per_kg']}\n";
    echo "    min_order_free_ship: " . ($f['min_order_free_ship'] ?? 'NULL') . "\n";
    echo "    priority: {$f['priority']}\n";
    echo "    weight_from: " . ($f['weight_from'] ?? 'NULL') . "\n";
    echo "    weight_to: " . ($f['weight_to'] ?? 'NULL') . "\n\n";
}

// Kiểm tra price_multiplier
echo "📋 Price multiplier:\n";
$stmt = $db->prepare("SELECT price_multiplier FROM shipping_methods WHERE id = ?");
$stmt->execute([$standardId]);
$multiplier = $stmt->fetch(PDO::FETCH_ASSOC)['price_multiplier'];
echo "  Standard: {$multiplier}x\n\n";

// Gọi function
echo "🧪 Gọi function:\n";
$stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, ?, ?) as fee");
$stmt->execute([$standardId, $weight, $value]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$fee = $result['fee'];
echo "  Kết quả: " . number_format($fee, 0, ',', '.') . " đ\n\n";

// Tính manual
echo "🧮 Tính manual:\n";
$baseFee = 25000;
$feePerKg = 0;
$manualFee = $baseFee + ($weight * $feePerKg);
$manualFee *= $multiplier;
echo "  ({$baseFee} + ({$weight} * {$feePerKg})) * {$multiplier} = " . number_format($manualFee, 0, ',', '.') . " đ\n\n";

if ($fee != $manualFee) {
    echo "❌ Function trả về kết quả khác với tính manual!\n";
    echo "   Function: " . number_format($fee, 0, ',', '.') . " đ\n";
    echo "   Manual: " . number_format($manualFee, 0, ',', '.') . " đ\n";
} else {
    echo "✅ Function hoạt động đúng!\n";
}
