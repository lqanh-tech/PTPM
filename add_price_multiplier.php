<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM shipping_methods LIKE 'price_multiplier'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE shipping_methods ADD COLUMN price_multiplier DECIMAL(5,2) DEFAULT 1.0 COMMENT 'Hệ số nhân giá'");
        echo "✅ Added price_multiplier column\n";
        
        // Update default values
        $db->exec("UPDATE shipping_methods SET price_multiplier = 1.0 WHERE code = 'standard'");
        $db->exec("UPDATE shipping_methods SET price_multiplier = 1.5 WHERE code = 'express'");
        $db->exec("UPDATE shipping_methods SET price_multiplier = 0.8 WHERE code = 'economy'");
        $db->exec("UPDATE shipping_methods SET price_multiplier = 1.2 WHERE code = 'ghn'");
        echo "✅ Updated default values\n";
    } else {
        echo "✓ Column already exists\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
