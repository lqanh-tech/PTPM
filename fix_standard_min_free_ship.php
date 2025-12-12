<?php
/**
 * Sửa lỗi Standard luôn miễn phí
 * Vấn đề: min_order_free_ship = 500,000 nhưng vẫn miễn phí với đơn 300,000
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Đang sửa lỗi Standard...\n\n";

// Lấy ID của Standard
$stmt = $db->prepare("SELECT id FROM shipping_methods WHERE code = 'standard'");
$stmt->execute();
$standardId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

// Cập nhật: Không miễn phí, chỉ tính phí cố định 25,000đ
echo "📦 Cập nhật cấu hình Standard:\n";
echo "   - Phí cơ bản: 25,000đ\n";
echo "   - Không miễn phí ship\n\n";

$stmt = $db->prepare("
    UPDATE shipping_fees 
    SET base_fee = 25000, fee_per_kg = 0, min_order_free_ship = NULL
    WHERE shipping_method_id = ? AND name = 'Phí cơ bản nội thành'
");
$stmt->execute([$standardId]);

echo "✅ Đã cập nhật\n\n";

// Test lại
echo "🧪 Test lại:\n";

$tests = [
    ['weight' => 2.5, 'value' => 300000],
    ['weight' => 2.5, 'value' => 500000],
    ['weight' => 5.0, 'value' => 1000000]
];

foreach ($tests as $test) {
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, ?, ?) as fee");
    $stmt->execute([$standardId, $test['weight'], $test['value']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $fee = $result['fee'];
    
    echo "  Giỏ hàng: {$test['weight']}kg, " . number_format($test['value'], 0, ',', '.') . " đ";
    echo " → Phí: " . number_format($fee, 0, ',', '.') . " đ\n";
}

echo "\n✅ HOÀN THÀNH!\n";
