<?php
/**
 * Thêm cấu hình phí cho phương thức GHN
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Đang thêm cấu hình phí cho GHN...\n\n";

// Lấy ID của phương thức GHN
$stmt = $db->prepare("SELECT id FROM shipping_methods WHERE code = 'ghn'");
$stmt->execute();
$ghnMethod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ghnMethod) {
    echo "❌ Không tìm thấy phương thức GHN!\n";
    exit(1);
}

$ghnId = $ghnMethod['id'];
echo "✅ Tìm thấy phương thức GHN (ID: {$ghnId})\n\n";

// Kiểm tra xem đã có cấu hình phí chưa
$stmt = $db->prepare("SELECT COUNT(*) as count FROM shipping_fees WHERE shipping_method_id = ?");
$stmt->execute([$ghnId]);
$existingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($existingCount > 0) {
    echo "⚠️  Phương thức GHN đã có {$existingCount} cấu hình phí. Bỏ qua...\n";
    exit(0);
}

// Thêm cấu hình phí cho GHN
// GHN thường tính phí dựa trên API, nhưng ta cần có fallback
$stmt = $db->prepare("
    INSERT INTO shipping_fees 
    (name, shipping_method_id, base_fee, fee_per_kg, min_order_free_ship, priority, is_active)
    VALUES 
    ('Phí GHN cơ bản', ?, 30000, 5000, 0, 10, 1)
");

try {
    $stmt->execute([$ghnId]);
    echo "✅ Đã thêm cấu hình phí cho GHN:\n";
    echo "   - Phí cơ bản: 30,000đ\n";
    echo "   - Phí theo trọng lượng: 5,000đ/kg\n";
    echo "   - Không miễn phí ship\n";
    echo "   - Priority: 10\n\n";
    
    echo "✅ HOÀN THÀNH!\n";
} catch (Exception $e) {
    echo "❌ Lỗi khi thêm cấu hình: " . $e->getMessage() . "\n";
    exit(1);
}
