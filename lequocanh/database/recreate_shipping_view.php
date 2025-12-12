<?php
/**
 * Check VIEW Definition and Recreate if Necessary
 */

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>VIEW Analysis and Fix</h2>\n";
    echo "<style>
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .alert-warning { background-color: #fff3cd; border: 1px solid #ffeeba; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
    </style>\n";
    
    // 1. Check current VIEW definition
    echo "<h3>1. Current VIEW Definition</h3>\n";
    
    $stmt = $db->query("SHOW CREATE VIEW v_shipping_methods_with_fees");
    $viewDef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewDef) {
        echo "<pre>" . htmlspecialchars($viewDef['Create View']) . "</pre>\n";
    }
    
    // 2. Test current VIEW
    echo "<h3>2. Test Current VIEW Output</h3>\n";
    
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order DESC");
    $viewData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>VIEW returns: <strong>" . count($viewData) . "</strong> rows</div>\n";
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Fee Count</th></tr>\n";
    foreach ($viewData as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($row['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['sort_order'] . "</td>";
        echo "<td>" . ($row['fee_config_count'] ?? 'N/A') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 3. Recreate VIEW with proper definition
    echo "<h3>3. Recreate VIEW (Fixed Version)</h3>\n";
    
    $db->exec("DROP VIEW IF EXISTS v_shipping_methods_with_fees");
    
    $createViewSQL = "
    CREATE OR REPLACE VIEW v_shipping_methods_with_fees AS
    SELECT 
        sm.id,
        sm.code,
        sm.name,
        sm.description,
        sm.delivery_time,
        sm.price_multiplier,
        sm.is_active,
        sm.sort_order,
        sm.created_at,
        sm.updated_at,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee,
        MIN(sf.min_order_free_ship) as min_free_ship_threshold
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.created_at, sm.updated_at
    ";
    
    $db->exec($createViewSQL);
    
    echo "<div class='alert alert-success'>✅ VIEW recreated successfully!</div>\n";
    echo "<pre>" . htmlspecialchars($createViewSQL) . "</pre>\n";
    
    // 4. Test new VIEW
    echo "<h3>4. Test NEW VIEW Output</h3>\n";
    
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order DESC");
    $newViewData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>NEW VIEW returns: <strong>" . count($newViewData) . "</strong> rows</div>\n";
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Fee Count</th><th>Min Base Fee</th></tr>\n";
    foreach ($newViewData as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($row['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['sort_order'] . "</td>";
        echo "<td>" . ($row['fee_config_count'] ?? 'N/A') . "</td>";
        echo "<td>" . number_format($row['min_base_fee'] ?? 0) . "₫</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 5. Compare
    echo "<h3>5. Comparison</h3>\n";
    
    if (count($viewData) !== count($newViewData)) {
        echo "<div class='alert alert-warning'>⚠️ Row count changed from " . count($viewData) . " to " . count($newViewData) . "</div>\n";
    } else {
        echo "<div class='alert alert-success'>✅ Row count is same: " . count($newViewData) . "</div>\n";
    }
    
    echo "<h3>6. Next Steps</h3>\n";
    echo "<div class='alert alert-info'>\n";
    echo "<ol>\n";
    echo "<li>Clear browser cache</li>\n";
    echo "<li>Reload checkout page</li>\n";
    echo "<li>Check if duplicates are gone</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>";
}
