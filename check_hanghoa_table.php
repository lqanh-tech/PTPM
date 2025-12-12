<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// Kiểm tra bảng hanghoa
$tables = $conn->query("SHOW TABLES LIKE '%hanghoa%'")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables with 'hanghoa':\n";
print_r($tables);

// Kiểm tra cột trong bảng hanghoa
if (!empty($tables)) {
    foreach ($tables as $table) {
        echo "\n\nColumns in $table:\n";
        $cols = $conn->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
}

// Kiểm tra bảng chi_tiet_don_hang
echo "\n\nColumns in chi_tiet_don_hang:\n";
$cols = $conn->query("SHOW COLUMNS FROM chi_tiet_don_hang")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

// Test query lấy sản phẩm từ đơn hàng
echo "\n\nTest query - Get products from order:\n";
$sql = "SELECT cdh.*, h.* 
        FROM chi_tiet_don_hang cdh
        LEFT JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
        WHERE cdh.ma_don_hang = (SELECT id FROM don_hang ORDER BY id DESC LIMIT 1)";
$stmt = $conn->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($products)) {
    echo "Found " . count($products) . " products\n";
    echo "Sample product:\n";
    print_r($products[0]);
}
