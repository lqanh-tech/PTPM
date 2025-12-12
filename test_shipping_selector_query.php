<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== TEST QUERY TRONG shipping_method_selector_v2.php ===\n\n";

// Giả lập session
$cartWeight = 1.0;
$cartValue = 300000;
$provinceId = 1;
$districtId = 1;

// Query giống y hệt trong file
$stmt = $db->query("
    SELECT 
        sm.*,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee,
        MIN(sf.min_order_free_ship) as min_free_ship_threshold
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Số bản ghi trả về: " . count($shippingMethods) . "\n\n";

echo "Danh sách phương thức:\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s %-15s %-30s %-15s %-15s\n", "ID", "CODE", "NAME", "SORT_ORDER", "FEE_COUNT");
echo str_repeat("-", 100) . "\n";

foreach ($shippingMethods as $method) {
    printf("%-5s %-15s %-30s %-15s %-15s\n", 
        $method['id'],
        $method['code'],
        $method['name'],
        $method['sort_order'],
        $method['fee_config_count']
    );
}

echo str_repeat("-", 100) . "\n";

// Kiểm tra xem có duplicate code không
$codes = array_column($shippingMethods, 'code');
$uniqueCodes = array_unique($codes);

if (count($codes) !== count($uniqueCodes)) {
    echo "\n⚠️ PHÁT HIỆN DUPLICATE CODE!\n";
    $duplicates = array_diff_assoc($codes, $uniqueCodes);
    echo "Các code bị trùng: " . implode(', ', $duplicates) . "\n";
} else {
    echo "\n✅ Không có duplicate code\n";
}

// Kiểm tra xem có duplicate id không
$ids = array_column($shippingMethods, 'id');
$uniqueIds = array_unique($ids);

if (count($ids) !== count($uniqueIds)) {
    echo "\n⚠️ PHÁT HIỆN DUPLICATE ID!\n";
    $duplicates = array_diff_assoc($ids, $uniqueIds);
    echo "Các ID bị trùng: " . implode(', ', $duplicates) . "\n";
} else {
    echo "\n✅ Không có duplicate ID\n";
}
