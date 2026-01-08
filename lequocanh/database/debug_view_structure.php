<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>shipping_methods Table Structure & Data Comparison</h2>\n";
    echo "<style>
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
    </style>\n";
    
    echo "<h3>1. Kiểm Tra VIEW v_shipping_methods_with_fees</h3>\n";
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='alert alert-info'>\n";
    echo "<strong>Views tìm thấy:</strong><br>\n";
    if (empty($views)) {
        echo "❌ KHÔNG có view nào trong database!<br>\n";
    } else {
        foreach ($views as $view) {
            echo "✅ " . $view . "<br>\n";
        }
    }
    echo "</div>\n";
    
    $viewExists = in_array('v_shipping_methods_with_fees', $views);
    
    if ($viewExists) {
        echo "<h3>2. Định Nghĩa VIEW</h3>\n";
        $stmt = $db->query("SHOW CREATE VIEW v_shipping_methods_with_fees");
        $viewDef = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>\n";
        echo htmlspecialchars($viewDef['Create View']);
        echo "\n</pre>\n";
    }
    
    echo "<h3>3. Cấu Trúc Bảng shipping_methods</h3>\n";
    $stmt = $db->query("SHOW COLUMNS FROM shipping_methods");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>4. Dữ Liệu Thực Tế Trong shipping_methods (sắp xếp theo sort_order DESC)</h3>\n";
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr>";
    foreach (array_keys($methods[0] ?? []) as $key) {
        echo "<th>" . htmlspecialchars($key) . "</th>";
    }
    echo "</tr>\n";
    
    foreach ($methods as $method) {
        echo "<tr>";
        foreach ($method as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>5. Kiểm Tra Dữ Liệu Trùng Lặp</h3>\n";
    $stmt = $db->query("
        SELECT code, name, COUNT(*) as count 
        FROM shipping_methods 
        GROUP BY code, name 
        HAVING count > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "<div class='alert alert-success'>✅ KHÔNG có dữ liệu trùng lặp về code và name</div>\n";
    } else {
        echo "<div class='alert alert-danger'>\n";
        echo "❌ CÓ dữ liệu trùng lặp:<br>\n";
        echo "<table>\n";
        echo "<tr><th>Code</th><th>Name</th><th>Số lượng</th></tr>\n";
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($dup['code']) . "</td>";
            echo "<td>" . htmlspecialchars($dup['name']) . "</td>";
            echo "<td><strong>" . $dup['count'] . "</strong></td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        echo "</div>\n";
    }
    
    echo "<h3>6. So Sánh Query Admin vs Frontend</h3>\n";
    
    if ($viewExists) {
        $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order DESC");
    } else {
        $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
    }
    $adminMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($viewExists) {
        $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
    } else {
        $stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
    }
    $frontendMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display: flex; gap: 20px;'>\n";
    
    echo "<div style='flex: 1;'>\n";
    echo "<h4>Admin (ALL records) - " . count($adminMethods) . " records</h4>\n";
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort</th><th>Active</th></tr>\n";
    foreach ($adminMethods as $m) {
        $activeClass = ($m['is_active'] ?? 0) ? 'style="background-color: #d4edda;"' : 'style="background-color: #f8d7da;"';
        echo "<tr $activeClass>";
        echo "<td>" . ($m['id'] ?? '') . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code'] ?? '') . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name'] ?? '') . "</td>";
        echo "<td>" . ($m['sort_order'] ?? 0) . "</td>";
        echo "<td>" . (($m['is_active'] ?? 0) ? '✅ YES' : '❌ NO') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    echo "<div style='flex: 1;'>\n";
    echo "<h4>Frontend (is_active=1 only) - " . count($frontendMethods) . " records</h4>\n";
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort</th><th>Active</th></tr>\n";
    foreach ($frontendMethods as $m) {
        echo "<tr style='background-color: #d4edda;'>";
        echo "<td>" . ($m['id'] ?? '') . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code'] ?? '') . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name'] ?? '') . "</td>";
        echo "<td>" . ($m['sort_order'] ?? 0) . "</td>";
        echo "<td>✅ YES</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    echo "</div>\n";
    
    echo "<h3>7. Kiểm Tra Bảng shipping_fees</h3>\n";
    $stmt = $db->query("SHOW TABLES LIKE 'shipping_fees'");
    $shippingFeesExists = $stmt->rowCount() > 0;
    
    if ($shippingFeesExists) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_fees");
        $feeCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='alert alert-info'>✅ Bảng shipping_fees tồn tại. Có <strong>" . $feeCount['count'] . "</strong> bản ghi.</div>\n";
        
        $stmt = $db->query("SHOW COLUMNS FROM shipping_fees LIKE 'shipping_method_id'");
        $hasMethodIdCol = $stmt->rowCount() > 0;
        
        if ($hasMethodIdCol) {
            echo "<div class='alert alert-success'>✅ Có cột shipping_method_id trong bảng shipping_fees</div>\n";
            
            $stmt = $db->query("SELECT sf.*, sm.name as method_name FROM shipping_fees sf LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id LIMIT 5");
            $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($fees)) {
                echo "<h4>Sample Data (First 5 records)</h4>\n";
                echo "<table>\n";
                echo "<tr><th>ID</th><th>Name</th><th>Method</th><th>Base Fee</th><th>Fee/kg</th><th>Free Ship From</th><th>Active</th></tr>\n";
                foreach ($fees as $fee) {
                    echo "<tr>";
                    echo "<td>" . ($fee['id'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($fee['name'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($fee['method_name'] ?? 'NULL') . "</td>";
                    echo "<td>" . number_format($fee['base_fee'] ?? 0) . "₫</td>";
                    echo "<td>" . number_format($fee['fee_per_kg'] ?? 0) . "₫</td>";
                    echo "<td>" . number_format($fee['min_order_free_ship'] ?? 0) . "₫</td>";
                    echo "<td>" . (($fee['is_active'] ?? 0) ? '✅' : '❌') . "</td>";
                    echo "</tr>\n";
                }
                echo "</table>\n";
            }
        } else {
            echo "<div class='alert alert-danger'>❌ KHÔNG có cột shipping_method_id trong bảng shipping_fees</div>\n";
        }
    } else {
        echo "<div class='alert alert-danger'>❌ Bảng shipping_fees KHÔNG tồn tại</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>";
}
