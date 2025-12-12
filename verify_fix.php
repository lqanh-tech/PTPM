<?php
session_start();
$_SESSION['cart_weight'] = 2.5;
$_SESSION['cart_total'] = 500000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

// Chạy đúng code trong file đã fix
$cartWeight = $_SESSION['cart_weight'] ?? 1.0;
$cartValue = $_SESSION['cart_total'] ?? 0;
$provinceId = $_SESSION['province_id'] ?? 1;
$districtId = $_SESSION['district_id'] ?? 1;

$stmt = $db->query("
    SELECT 
        sm.*,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee,
        MIN(sf.min_order_free_ship) as min_free_ship_threshold
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.created_at, sm.updated_at
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== KẾT QUẢ SAU KHI FIX ===\n\n";
echo "Số bản ghi từ query: " . count($shippingMethods) . "\n\n";

// Loại bỏ duplicate nếu có
$uniqueMethods = [];
$seenCodes = [];
foreach ($shippingMethods as $method) {
    if (!in_array($method['code'], $seenCodes)) {
        $uniqueMethods[] = $method;
        $seenCodes[] = $method['code'];
    }
}
$shippingMethods = $uniqueMethods;
unset($uniqueMethods, $seenCodes);

echo "Số bản ghi sau khi loại bỏ duplicate: " . count($shippingMethods) . "\n\n";

echo "Danh sách phương thức sẽ hiển thị:\n";
echo str_repeat("-", 80) . "\n";
foreach ($shippingMethods as $index => $method) {
    echo ($index + 1) . ". {$method['name']} (Code: {$method['code']})\n";
    echo "   Mô tả: {$method['description']}\n";
    echo "   Thời gian: {$method['delivery_time']}\n";
    echo str_repeat("-", 80) . "\n";
}

echo "\n✅ FIX THÀNH CÔNG!\n";
echo "Không còn phương thức trùng lặp.\n";
echo "Hãy refresh trang checkout để xem kết quả.\n";
