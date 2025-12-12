<?php
require 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->query('SHOW COLUMNS FROM user');
echo "Columns in 'user' table:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['Field'] . "\n";
}
