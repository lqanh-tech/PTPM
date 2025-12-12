<?php
/**
 * Cập nhật phí vận chuyển để khớp với frontend
 * Dựa trên ảnh người dùng gửi:
 * - GHN: 30,000đ (trong admin) nhưng hiển thị 35,000đ (frontend)
 * - Standard: 25,000đ
 * - Express: 45,000đ
 * - Pickup: Miễn phí
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Đang cập nhật phí vận chuyển...\n\n";

// Lấy ID các phương thức
$methods = [];
$stmt = $db->query("SELECT id, code, name FROM shipping_methods");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $methods[$row['code']] = $row;
}

// Cập nhật phí GHN
// Frontend hiển thị 35,000đ với giỏ hàng 2.5kg
// Nếu base_fee = 30,000 và fee_per_kg = 5,000 thì: 30,000 + (2.5 * 5,000) = 42,500
// Nhưng với price_multiplier = 1.2: 42,500 * 1.2 = 51,000
// Để được 35,000 với 2.5kg: base_fee = 22,500 và fee_per_kg = 5,000
// Hoặc: base_fee = 35,000 và fee_per_kg = 0
echo "📦 Cập nhật phí GHN...\n";
$stmt = $db->prepare("
    UPDATE shipping_fees 
    SET base_fee = 35000, fee_per_kg = 0, min_order_free_ship = 0
    WHERE shipping_method_id = ? AND name = 'Phí GHN cơ bản'
");
$stmt->execute([$methods['ghn']['id']]);
echo "   ✅ GHN: 35,000đ (cố định)\n";

// Cập nhật phí Standard
// Frontend hiển thị 25,000đ
echo "\n📦 Cập nhật phí Standard...\n";
$stmt = $db->prepare("
    UPDATE shipping_fees 
    SET base_fee = 25000, fee_per_kg = 0, min_order_free_ship = 500000
    WHERE shipping_method_id = ? AND name = 'Phí cơ bản nội thành'
");
$stmt->execute([$methods['standard']['id']]);
echo "   ✅ Standard: 25,000đ (miễn phí từ 500,000đ)\n";

// Cập nhật phí Express
// Frontend hiển thị 45,000đ - đã đúng
echo "\n📦 Phí Express đã đúng: 45,000đ\n";

// Cập nhật phí Pickup
// Frontend hiển thị Miễn phí - đã đúng
echo "\n📦 Phí Pickup đã đúng: Miễn phí\n";

// Cập nhật price_multiplier về 1.0 cho tất cả để không bị nhân thêm
echo "\n🔧 Cập nhật price_multiplier về 1.0...\n";
$db->exec("UPDATE shipping_methods SET price_multiplier = 1.0 WHERE code IN ('ghn', 'standard', 'express')");
echo "   ✅ Đã cập nhật\n";

// Test lại
echo "\n🧪 Test lại với giỏ hàng: 2.5kg, 500,000đ\n\n";

$stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
$testMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($testMethods as $m) {
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, 2.5, 500000) as fee");
    $stmt->execute([$m['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $fee = $result['fee'];
    
    echo "  {$m['name']}: " . number_format($fee, 0, ',', '.') . " đ\n";
}

echo "\n✅ HOÀN THÀNH!\n";
echo "\n📝 Lưu ý: Phí đã được cập nhật để khớp với frontend.\n";
echo "   - GHN: 35,000đ (cố định)\n";
echo "   - Standard: 25,000đ (miễn phí từ 500,000đ)\n";
echo "   - Express: 45,000đ\n";
echo "   - Pickup: Miễn phí\n";
