<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== Checking user tables ===\n";
$tables = $conn->query("SHOW TABLES LIKE '%user%'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

if (!empty($tables)) {
    echo "\n=== Columns in first user table ===\n";
    $cols = $conn->query("SHOW COLUMNS FROM " . $tables[0])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
}
