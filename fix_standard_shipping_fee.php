<?php
/**
 * Sửa phí Standard để hiển thị đúng 25,000đ
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Đang sửa phí Standard...\n\n";

// Lấy ID của Standard
$stmt = $db->prepare("SELECT id FROM shipping_methods WHERE code = 'standard'");
$stmt->execute();
$standardId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

// Xem tất cả cấu hình phí của Standard
echo "📋 Các cấu hình phí hiện tại của Standard:\n";
$stmt = $db->prepare("SELECT * FROM shipping_fees WHERE shipping_method_id = ? ORDER BY priority DESC");
$stmt->execute([$standardId]);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($fees as $f) {
    echo "  ID {$f['id']}: {$f['name']} - Base: " . number_format($f['base_fee'], 0) . "đ, ";
    echo "Per kg: " . number_format($f['fee_per_kg'], 0) . "đ, Priority: {$f['priority']}\n";
}

// Vấn đề: Có nhiều cấu hình và function đang chọn cái có priority cao nhất
// Với giỏ hàng 2.5kg, function chọn "Phí theo trọng lượng 1-5kg" (priority 8)
// Phí = 30,000 + (2.5 * 10,000) = 55,000đ

// Giải pháp: Tăng priority của "Phí cơ bản nội thành" lên cao nhất
echo "\n🔧 Tăng priority của 'Phí cơ bản nội thành' lên 100...\n";
$stmt = $db->prepare("
    UPDATE shipping_fees 
    SET priority = 100
    WHERE shipping_method_id = ? AND name = 'Phí cơ bản nội thành'
");
$stmt->execute([$standardId]);
echo "   ✅ Đã cập nhật\n";

// Hoặc: Vô hiệu hóa các cấu hình khác
echo "\n🔧 Vô hiệu hóa các cấu hình phí khác của Standard...\n";
$stmt = $db->prepare("
    UPDATE shipping_fees 
    SET is_active = 0
    WHERE shipping_method_id = ? AND name != 'Phí cơ bản nội thành'
");
$stmt->execute([$standardId]);
echo "   ✅ Đã vô hiệu hóa\n";

// Test lại
echo "\n🧪 Test lại:\n";
$stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, 2.5, 500000) as fee");
$stmt->execute([$standardId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$fee = $result['fee'];

echo "  Standard với giỏ 2.5kg, 500,000đ: " . number_format($fee, 0, ',', '.') . " đ\n";

if ($fee == 0) {
    echo "  ✅ Miễn phí (vì đơn hàng >= 500,000đ)\n";
} elseif ($fee == 25000) {
    echo "  ✅ Đúng 25,000đ\n";
} else {
    echo "  ⚠️  Vẫn chưa đúng, đang là " . number_format($fee, 0, ',', '.') . " đ\n";
}

echo "\n✅ HOÀN THÀNH!\n";
