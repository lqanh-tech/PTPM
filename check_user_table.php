<?php
require_once './lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "Cấu trúc bảng user:\n\n";

$stmt = $conn->query("DESCRIBE user");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}

echo "\n\nMẫu dữ liệu:\n\n";
$stmt = $conn->query("SELECT * FROM user LIMIT 1");
$sample = $stmt->fetch(PDO::FETCH_ASSOC);

if ($sample) {
    foreach ($sample as $key => $value) {
        echo "$key: $value\n";
    }
}
?>
