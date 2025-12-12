<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== KIỂM TRA BẢNG shipping_methods ===\n\n";

// Kiểm tra tất cả database có bảng shipping_methods
$stmt = $db->query("
    SELECT TABLE_SCHEMA, TABLE_NAME, TABLE_TYPE
    FROM information_schema.TABLES 
    WHERE TABLE_NAME = 'shipping_methods'
");

$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Danh sách bảng/view shipping_methods:\n";
echo str_repeat("-", 80) . "\n";
foreach ($tables as $table) {
    echo "Database: {$table['TABLE_SCHEMA']}\n";
    echo "Name: {$table['TABLE_NAME']}\n";
    echo "Type: {$table['TABLE_TYPE']}\n";
    echo str_repeat("-", 80) . "\n";
}

// Kiểm tra cấu trúc bảng hiện tại
echo "\nCấu trúc bảng shipping_methods trong database hiện tại:\n";
echo str_repeat("=", 80) . "\n";
$stmt = $db->query("SHOW COLUMNS FROM shipping_methods");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo sprintf("%-30s %-20s %s\n", $col['Field'], $col['Type'], $col['Null']);
}

// Đếm số bản ghi
echo "\n" . str_repeat("=", 80) . "\n";
$stmt = $db->query("SELECT COUNT(*) as count FROM shipping_methods");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Tổng số bản ghi: {$count}\n";

// Hiển thị tất cả bản ghi
echo "\nDanh sách tất cả bản ghi:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $db->query("SELECT id, code, name, is_active, sort_order FROM shipping_methods ORDER BY id");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($methods as $method) {
    echo "ID: {$method['id']} | Code: {$method['code']} | Name: {$method['name']} | Active: {$method['is_active']} | Sort: {$method['sort_order']}\n";
}
