<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM shipping_methods");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Shipping Methods Table Dump</h1>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Active</th></tr>";
    foreach ($methods as $m) {
        echo "<tr>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td>" . $m['code'] . "</td>";
        echo "<td>" . $m['name'] . "</td>";
        echo "<td>" . $m['is_active'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
