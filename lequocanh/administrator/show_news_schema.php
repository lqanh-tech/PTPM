<?php
require_once __DIR__ . '/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== CẤUTRÚC BẢNG NEWS ===\n\n";

$columns = $db->query("DESCRIBE news")->fetchAll(PDO::FETCH_ASSOC);
echo "Tổng cột: " . count($columns) . "\n\n";

foreach ($columns as $col) {
    printf(
        "%-20s | %-15s | Null: %-3s | Key: %-3s | Default: %-15s\n",
        $col['Field'],
        $col['Type'],
        $col['Null'],
        $col['Key'],
        $col['Default'] ?? 'NULL'
    );
}

echo "\n=== SCHEMA SQL ===\n";
$show = $db->query("SHOW CREATE TABLE news")->fetch(PDO::FETCH_ASSOC);
echo $show['Create Table'] . ";\n";
