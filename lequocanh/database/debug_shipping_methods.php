<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Shipping Methods - Database Data</h2>\n";
    echo "<pre>\n";
    
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== ALL SHIPPING METHODS ===\n";
    foreach ($methods as $method) {
        echo sprintf(
            "ID: %d | Code: %s | Name: %s | Active: %s | Sort: %d\n",
            $method['id'],
            $method['code'],
            $method['name'],
            $method['is_active'] ? 'YES' : 'NO',
            $method['sort_order']
        );
    }
    
    echo "\n=== ACTIVE SHIPPING METHODS (user will see) ===\n";
    $stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
    $activeMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($activeMethods as $method) {
        echo sprintf(
            "ID: %d | Code: %s | Name: %s | Sort: %d\n",
            $method['id'],
            $method['code'],
            $method['name'],
            $method['sort_order']
        );
    }
    
    echo "\n=== VIEW DATA (v_shipping_methods_with_fees) ===\n";
    try {
        $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
        $viewMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($viewMethods as $method) {
            echo sprintf(
                "ID: %d | Code: %s | Name: %s | Sort: %d | Fee Count: %d\n",
                $method['id'],
                $method['code'],
                $method['name'],
                $method['sort_order'],
                $method['fee_config_count'] ?? 0
            );
        }
    } catch (Exception $e) {
        echo "Error getting view data: " . $e->getMessage() . "\n";
    }
    
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
