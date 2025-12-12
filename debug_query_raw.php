<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

echo "=== DEBUG QUERY RAW ===\n\n";

$sql = "
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
";

echo "SQL:\n" . $sql . "\n\n";

$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Số bản ghi trả về: " . count($results) . "\n\n";

foreach ($results as $idx => $row) {
    echo "[$idx] ID:{$row['id']} Code:{$row['code']} Name:{$row['name']} Sort:{$row['sort_order']}\n";
}

// Kiểm tra duplicate code
$codes = array_column($results, 'code');
$uniqueCodes = array_unique($codes);

echo "\nTổng codes: " . count($codes) . "\n";
echo "Unique codes: " . count($uniqueCodes) . "\n";

if (count($codes) !== count($uniqueCodes)) {
    echo "\n⚠️ QUERY TRẢ VỀ DUPLICATE!\n";
    $codeCounts = array_count_values($codes);
    foreach ($codeCounts as $code => $count) {
        if ($count > 1) {
            echo "  - Code '{$code}' xuất hiện {$count} lần\n";
        }
    }
}
