<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== Tất cả các bảng trong database ===\n\n";
$tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

foreach($tables as $table) {
    echo "- $table\n";
    
    // Nếu là bảng sản phẩm, hiển thị cấu trúc
    if(stripos($table, 'san') !== false || stripos($table, 'product') !== false) {
        echo "  >>> Đây có thể là bảng sản phẩm!\n";
        $cols = $conn->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $col) {
            echo "      - {$col['Field']} ({$col['Type']})\n";
        }
    }
}

echo "\n=== Kiểm tra bảng chi_tiet_don_hang ===\n";
$cols = $conn->query("SHOW COLUMNS FROM chi_tiet_don_hang")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}
?>
