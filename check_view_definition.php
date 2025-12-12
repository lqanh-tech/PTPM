<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== KIỂM TRA VIEW v_shipping_methods_with_fees ===\n\n";

try {
    // Kiểm tra VIEW có tồn tại không
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Danh sách VIEW trong database:\n";
    foreach ($views as $view) {
        echo "  - $view\n";
    }
    echo "\n";
    
    if (in_array('v_shipping_methods_with_fees', $views)) {
        echo "✅ VIEW v_shipping_methods_with_fees TỒN TẠI\n\n";
        
        // Lấy định nghĩa VIEW
        $stmt = $db->query("SHOW CREATE VIEW v_shipping_methods_with_fees");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Định nghĩa VIEW:\n";
        echo str_repeat("=", 80) . "\n";
        echo $result['Create View'] . "\n";
        echo str_repeat("=", 80) . "\n\n";
        
        // Test query VIEW
        echo "Dữ liệu từ VIEW:\n";
        echo str_repeat("-", 80) . "\n";
        $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order DESC");
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($methods as $method) {
            echo "ID: {$method['id']}\n";
            echo "Code: {$method['code']}\n";
            echo "Name: {$method['name']}\n";
            echo "Sort Order: {$method['sort_order']}\n";
            echo "Fee Config Count: " . ($method['fee_config_count'] ?? 'N/A') . "\n";
            echo str_repeat("-", 80) . "\n";
        }
        
        echo "\nTổng số bản ghi: " . count($methods) . "\n";
        
    } else {
        echo "❌ VIEW v_shipping_methods_with_fees KHÔNG TỒN TẠI\n";
    }
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
