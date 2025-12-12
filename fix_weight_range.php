<?php
/**
 * Sửa lỗi weight_to quá nhỏ
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Đang sửa lỗi weight range...\n\n";

// Cập nhật weight_to = NULL (không giới hạn) cho tất cả phí cơ bản
echo "📦 Cập nhật weight range:\n";

$stmt = $db->exec("
    UPDATE shipping_fees 
    SET weight_from = 0, weight_to = NULL
    WHERE name LIKE '%cơ bản%' OR name LIKE '%GHN%' OR name LIKE '%nhanh%' OR name LIKE '%cửa hàng%'
");

echo "   ✅ Đã cập nhật {$stmt} dòng\n\n";

// Test lại
echo "🧪 Test lại:\n\n";

$stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tests = [
    ['weight' => 2.5, 'value' => 300000],
    ['weight' => 2.5, 'value' => 500000]
];

foreach ($tests as $test) {
    echo "Giỏ hàng: {$test['weight']}kg, " . number_format($test['value'], 0, ',', '.') . " đ\n";
    
    foreach ($methods as $m) {
        $stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, ?, ?) as fee");
        $stmt->execute([$m['id'], $test['weight'], $test['value']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $fee = $result['fee'];
        
        echo "  {$m['name']}: " . number_format($fee, 0, ',', '.') . " đ\n";
    }
    echo "\n";
}

echo "✅ HOÀN THÀNH!\n";
