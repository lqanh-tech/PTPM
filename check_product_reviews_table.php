<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== Columns in product_reviews ===\n";
$cols = $conn->query("SHOW COLUMNS FROM product_reviews")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}
