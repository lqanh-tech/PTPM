<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();
$cols = $db->query('SHOW COLUMNS FROM shipping_fees')->fetchAll(PDO::FETCH_ASSOC);

echo "Các cột trong bảng shipping_fees:\n\n";
foreach ($cols as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}

echo "\n\nCác cột cần thiết:\n";
$required = ['base_fee', 'fee_per_kg', 'weight_from', 'weight_to', 'min_order_free_ship', 'priority'];
foreach ($required as $req) {
    $found = false;
    foreach ($cols as $col) {
        if ($col['Field'] === $req) {
            $found = true;
            break;
        }
    }
    echo "- $req: " . ($found ? '✅ CÓ' : '❌ THIẾU') . "\n";
}
