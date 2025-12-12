<?php
/**
 * Debug shipping_fees table - Tìm Nguyên Nhân Trùng Lặp
 */

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<style>
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; border: 1px solid #ffeeba; }
    </style>\n";
    
    echo "<h2>Shipping Fees Analysis</h2>\n";
    
    // 1. Kiểm tra tất cả shipping_fees
    echo "<h3>1. Tất Cả Shipping Fees</h3>\n";
    $stmt = $db->query("SELECT sf.*, sm.code as method_code, sm.name as method_name FROM shipping_fees sf LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id ORDER BY sf.shipping_method_id, sf.priority DESC");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Method Code</th><th>Method Name</th><th>Base Fee</th><th>Fee/kg</th><th>Free From</th><th>Priority</th><th>Active</th></tr>\n";
    foreach ($fees as $fee) {
        $bgColor = ($fee['is_active'] ?? 0) ? '' : 'style="background-color: #f8d7da;"';
        echo "<tr $bgColor>";
        echo "<td>" . ($fee['id'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($fee['name'] ?? '') . "</td>";
        echo "<td><code>" . htmlspecialchars($fee['method_code'] ?? 'NULL') . "</code></td>";
        echo "<td>" . htmlspecialchars($fee['method_name'] ?? 'NULL') . "</td>";
        echo "<td>" . number_format($fee['base_fee'] ?? 0) . "₫</td>";
        echo "<td>" . number_format($fee['fee_per_kg'] ?? 0) . "₫</td>";
        echo "<td>" . number_format($fee['min_order_free_ship'] ?? 0) . "₫</td>";
        echo "<td>" . ($fee['priority'] ?? 0) . "</td>";
        echo "<td>" . (($fee['is_active'] ?? 0) ? '✅' : '❌') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 2. Đếm số fee config per shipping method
    echo "<h3>2. Số Lượng Fee Config Per Shipping Method</h3>\n";
    $stmt = $db->query("
        SELECT 
            sm.id,
            sm.code,
            sm.name,
            COUNT(sf.id) as fee_count,
            COUNT(CASE WHEN sf.is_active = 1 THEN 1 END) as active_fee_count
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id
        GROUP BY sm.id, sm.code, sm.name
        ORDER BY sm.sort_order DESC
    ");
    $methodCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Total Fees</th><th>Active Fees</th></tr>\n";
    foreach ($methodCounts as $m) {
        $bgColor = ($m['active_fee_count'] > 1) ? 'style="background-color: #fff3cd;"' : '';
        echo "<tr $bgColor>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name']) . "</td>";
        echo "<td><strong>" . $m['fee_count'] . "</strong></td>";
        echo "<td><strong>" . $m['active_fee_count'] . "</strong></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 3. Kiểm tra phương thức nào có nhiều hơn 1 fee config
    $multipleFeeMethods = array_filter($methodCounts, function($m) {
        return $m['active_fee_count'] > 1;
    });
    
    if (!empty($multipleFeeMethods)) {
        echo "<div class='alert alert-warning'>\n";
        echo "<strong>⚠️ CẢN BÁO: Các phương thức sau có NHIỀU hơn 1 fee config active:</strong><br>\n";
        foreach ($multipleFeeMethods as $m) {
            echo "- " . htmlspecialchars($m['name']) . " (Code: " . htmlspecialchars($m['code']) . ") có <strong>" . $m['active_fee_count'] . "</strong> fee configs active<br>\n";
        }
        echo "<br>Điều này có thể gây ra vấn đề trong frontend nếu logic không xử lý đúng!\n";
        echo "</div>\n";
        
        // Show details for each method with multiple fees
        foreach ($multipleFeeMethods as $m) {
            echo "<h4>Chi Tiết Fee Configs Cho: " . htmlspecialchars($m['name']) . "</h4>\n";
            $stmt = $db->prepare("
                SELECT * FROM shipping_fees 
                WHERE shipping_method_id = ? AND is_active = 1
                ORDER BY priority DESC
            ");
            $stmt->execute([$m['id']]);
            $methodFees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>\n";
            echo "<tr><th>ID</th><th>Name</th><th>Base Fee</th><th>Fee/kg</th><th>Free From</th><th>Province</th><th>District</th><th>Priority</th></tr>\n";
            foreach ($methodFees as $fee) {
                echo "<tr>";
                echo "<td>" . $fee['id'] . "</td>";
                echo "<td>" . htmlspecialchars($fee['name'] ?? '') . "</td>";
                echo "<td>" . number_format($fee['base_fee'] ?? 0) . "₫</td>";
                echo "<td>" . number_format($fee['fee_per_kg'] ?? 0) . "₫</td>";
                echo "<td>" . number_format($fee['min_order_free_ship'] ?? 0) . "₫</td>";
                echo "<td>" . ($fee['province_id'] ?? 'NULL') . "</td>";
                echo "<td>" . ($fee['district_id'] ?? 'NULL') . "</td>";
                echo "<td>" . ($fee['priority'] ?? 0) . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    } else {
        echo "<div class='alert alert-info'>✅ Tất cả các phương thức chỉ có 1 fee config active hoặc không có fee config.</div>\n";
    }
    
    // 4. Test query giống frontend
    echo "<h3>3. Test Query Frontend (shipping_method_selector_v2.php)</h3>\n";
    
    echo "< pre>\n";
    echo "Query: SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC\n";
    echo "</pre>\n";
    
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
    $frontendMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>Kết quả: <strong>" . count($frontendMethods) . "</strong> records</div>\n";
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Fee Count</th><th>Min Base Fee</th></tr>\n";
    foreach ($frontendMethods as $m) {
        echo "<tr>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name']) . "</td>";
        echo "<td>" . $m['sort_order'] . "</td>";
        echo "<td>" . ($m['fee_config_count'] ?? 0) . "</td>";
        echo "<td>" . number_format($m['min_base_fee'] ?? 0) . "₫</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>";
}
