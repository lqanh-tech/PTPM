<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$tables = ['hanghoa', 'banners', 'khuyen_mai', 'loaihang'];
foreach ($tables as $table) {
    echo "=== $table ===\n";
    try {
        $stmt = $conn->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . "\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
