<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== Cấu trúc bảng hanghoa ===\n";
$cols = $conn->query('SHOW COLUMNS FROM hanghoa')->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $col) {
    echo "{$col['Field']} ({$col['Type']})\n";
}

echo "\n=== Lấy mẫu dữ liệu ===\n";
$sample = $conn->query('SELECT * FROM hanghoa LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
foreach($sample as $row) {
    echo "ID: {$row['ma_hang_hoa']}\n";
    foreach($row as $key => $val) {
        if(stripos($key, 'ten') !== false) {
            echo "  $key: $val\n";
        }
    }
    echo "\n";
}
?>
