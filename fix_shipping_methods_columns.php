<?php
/**
 * Fix Shipping Methods Table - Add missing columns
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Fix Shipping Methods Table</h1>";
echo "<hr>";

try {
    // Check current columns
    echo "<h2>Current Columns:</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM shipping_methods");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingColumns = [];
    foreach ($columns as $col) {
        $existingColumns[] = $col['Field'];
        echo "- {$col['Field']} ({$col['Type']})<br>";
    }
    
    echo "<hr>";
    
    // Add missing columns
    $columnsToAdd = [
        'delivery_time' => "VARCHAR(100) DEFAULT NULL COMMENT 'Thời gian giao hàng dự kiến'",
        'description' => "TEXT DEFAULT NULL COMMENT 'Mô tả phương thức'"
    ];
    
    echo "<h2>Adding Missing Columns:</h2>";
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE shipping_methods ADD COLUMN $columnName $columnDef";
            echo "Adding column: <code>$columnName</code>...<br>";
            $db->exec($sql);
            echo "✅ Added successfully!<br>";
        } else {
            echo "✓ Column <code>$columnName</code> already exists<br>";
        }
    }
    
    echo "<hr>";
    
    // Update existing records with default values
    echo "<h2>Updating Existing Records:</h2>";
    
    $updates = [
        "UPDATE shipping_methods SET delivery_time = '2-3 ngày' WHERE code = 'standard' AND delivery_time IS NULL",
        "UPDATE shipping_methods SET delivery_time = '1-2 ngày' WHERE code = 'express' AND delivery_time IS NULL",
        "UPDATE shipping_methods SET delivery_time = '3-5 ngày' WHERE code = 'economy' AND delivery_time IS NULL",
        "UPDATE shipping_methods SET delivery_time = '4-7 ngày' WHERE code = 'super_saver' AND delivery_time IS NULL",
        
        "UPDATE shipping_methods SET description = 'Giao hàng tiêu chuẩn' WHERE code = 'standard' AND description IS NULL",
        "UPDATE shipping_methods SET description = 'Giao hàng nhanh' WHERE code = 'express' AND description IS NULL",
        "UPDATE shipping_methods SET description = 'Giao hàng tiết kiệm' WHERE code = 'economy' AND description IS NULL",
        "UPDATE shipping_methods SET description = 'Giao hàng siêu tiết kiệm' WHERE code = 'super_saver' AND description IS NULL"
    ];
    
    foreach ($updates as $sql) {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            echo "✅ Updated $affected record(s)<br>";
        }
    }
    
    echo "<hr>";
    
    // Show final data
    echo "<h2>Final Data:</h2>";
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr>";
    echo "<th>Code</th>";
    echo "<th>Name</th>";
    echo "<th>Description</th>";
    echo "<th>Delivery Time</th>";
    echo "<th>Price Multiplier</th>";
    echo "<th>Active</th>";
    echo "</tr>";
    
    foreach ($methods as $method) {
        echo "<tr>";
        echo "<td>{$method['code']}</td>";
        echo "<td>{$method['name']}</td>";
        echo "<td>" . ($method['description'] ?? '<em>null</em>') . "</td>";
        echo "<td>" . ($method['delivery_time'] ?? '<em>null</em>') . "</td>";
        echo "<td>{$method['price_multiplier']}</td>";
        echo "<td>" . ($method['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Fix completed successfully!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
