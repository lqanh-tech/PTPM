<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== VIEW: v_shipping_methods_with_fees ===\n\n";
    
    $stmt = $db->query("SHOW CREATE VIEW v_shipping_methods_with_fees");
    $viewDef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewDef) {
        echo "Definition:\n";
        echo "----------------------------------------\n";

        $sql = $viewDef['Create View'];
        $sql = preg_replace('/SELECT/', "\nSELECT\n  ", $sql);
        $sql = preg_replace('/,/', ",\n  ", $sql);
        $sql = preg_replace('/FROM/', "\nFROM\n  ", $sql);
        $sql = preg_replace('/LEFT JOIN/', "\nLEFT JOIN\n  ", $sql);
        $sql = preg_replace('/WHERE/', "\nWHERE\n  ", $sql);
        $sql = preg_replace('/GROUP BY/', "\nGROUP BY\n  ", $sql);
        
        echo $sql . "\n\n";
    }
    
    echo "\n\n=== Test VIEW Query ===\n\n";
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total rows: " . count($rows) . "\n\n";
    
    foreach ($rows as $row) {
        echo sprintf(
            "ID: %d | Code: %s | Name: %s | Sort: %d | Fee Count: %d | Min Base Fee: %s\n",
            $row['id'],
            $row['code'],
            $row['name'],
            $row['sort_order'],
            $row['fee_config_count'] ?? 0,
            number_format($row['min_base_fee'] ?? 0)
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
