<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
        .ok { background-color: #d4edda !important; }
        .warning { background-color: #fff3cd !important; }
        .error { background-color: #f8d7da !important; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        h2 { color: #667eea; }
    </style>\n";
    
    echo "<div class='container'>\n";
    echo "<h2>🔍 FINAL CHECK - Tất Cả Phương Thức Vận Chuyển</h2>\n";
    
    $stmt = $db->query("
        SELECT 
            sm.id,
            sm.code,
            sm.name,
            sm.is_active as method_active,
            COUNT(CASE WHEN sf.is_active = 1 THEN 1 END) as active_fee_count,
            COUNT(sf.id) as total_fee_count
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id
        GROUP BY sm.id, sm.code, sm.name, sm.is_active
        ORDER BY sm.sort_order DESC
    ");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>1. Tổng Quan Phương Thức & Fee Configs</h3>\n";
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Method Status</th><th>Active Fee Configs</th><th>Total Fee Configs</th><th>Status</th></tr>\n";
    
    $hasIssue = false;
    foreach ($methods as $m) {
        $status = '';
        $rowClass = 'ok';
        
        if ($m['method_active'] == 1) {
            if ($m['active_fee_count'] == 0) {
                $status = '⚠️ Không có fee config';
                $rowClass = 'warning';
                $hasIssue = true;
            } elseif ($m['active_fee_count'] == 1) {
                $status = '✅ OK';
                $rowClass = 'ok';
            } else {
                $status = '❌ TRÙNG LẶP!';
                $rowClass = 'error';
                $hasIssue = true;
            }
        } else {
            $status = '⏸️ Method Inactive';
            $rowClass = '';
        }
        
        echo "<tr class='$rowClass'>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name']) . "</td>";
        echo "<td>" . ($m['method_active'] ? '✅ Active' : '❌ Inactive') . "</td>";
        echo "<td><strong>" . $m['active_fee_count'] . "</strong></td>";
        echo "<td>" . $m['total_fee_count'] . "</td>";
        echo "<td><strong>$status</strong></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    if (!$hasIssue) {
        echo "<div class='alert alert-success'>\n";
        echo "<h3>✅ TẤT CẢ ĐÃ ĐÚNG!</h3>\n";
        echo "<p>Mỗi phương thức active chỉ có <strong>1 fee config active</strong>.</p>\n";
        echo "<p>Database đã được chuẩn hóa hoàn toàn!</p>\n";
        echo "</div>\n";
    } else {
        echo "<div class='alert alert-danger'>\n";
        echo "<h3>❌ VẪN CÒN VẤN ĐỀ!</h3>\n";
        echo "<p>Vẫn còn phương thức có nhiều hơn 1 fee config active hoặc không có fee config.</p>\n";
        echo "<p>Vui lòng chạy lại <a href='fix_duplicate_shipping_fees.php'>fix_duplicate_shipping_fees.php</a></p>\n";
        echo "</div>\n";
    }
    
    echo "<h3>2. Chi Tiết Tất Cả Fee Configs</h3>\n";
    
    $stmt = $db->query("
        SELECT 
            sf.id,
            sf.shipping_method_id,
            sm.code,
            sm.name as method_name,
            sf.name as fee_name,
            sf.base_fee,
            sf.priority,
            sf.is_active as fee_active,
            sm.is_active as method_active
        FROM shipping_fees sf
        JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
        ORDER BY sm.sort_order DESC, sf.priority DESC, sf.id ASC
    ");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>Fee ID</th><th>Method Code</th><th>Method Name</th><th>Fee Name</th><th>Base Fee</th><th>Priority</th><th>Fee Active</th><th>Method Active</th></tr>\n";
    
    foreach ($fees as $fee) {
        $rowClass = '';
        if ($fee['fee_active'] && $fee['method_active']) {
            $rowClass = 'ok';
        } elseif (!$fee['fee_active']) {
            $rowClass = 'warning';
        }
        
        echo "<tr class='$rowClass'>";
        echo "<td>" . $fee['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($fee['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($fee['method_name']) . "</td>";
        echo "<td>" . htmlspecialchars($fee['fee_name'] ?? '') . "</td>";
        echo "<td>" . number_format($fee['base_fee']) . "₫</td>";
        echo "<td><strong>" . $fee['priority'] . "</strong></td>";
        echo "<td>" . ($fee['fee_active'] ? '✅' : '❌') . "</td>";
        echo "<td>" . ($fee['method_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>3. Test VIEW Query (Giống Checkout Page)</h3>\n";
    
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
    $viewResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>\n";
    echo "<strong>Query:</strong> <code>SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC</code><br>\n";
    echo "<strong>Results:</strong> " . count($viewResults) . " rows\n";
    echo "</div>\n";
    
    echo "<table>\n";
    echo "<tr><th>#</th><th>ID</th><th>Code</th><th>Name</th><th>Fee Count</th><th>Min Base Fee</th></tr>\n";
    foreach ($viewResults as $idx => $row) {
        echo "<tr class='ok'>";
        echo "<td>$idx</td>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($row['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['fee_config_count'] ?? 0) . "</td>";
        echo "<td>" . number_format($row['min_base_fee'] ?? 0) . "₫</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>4. Các Bước Tiếp Theo</h3>\n";
    
    if (!$hasIssue) {
        echo "<div class='alert alert-success'>\n";
        echo "<h4>✅ Database đã OK! Bây giờ:</h4>\n";
        echo "<ol>\n";
        echo "<li><strong>Clear browser cache:</strong> Nhấn <code>Ctrl + Shift + Delete</code></li>\n";
        echo "<li>Chọn \"Cached images and files\"</li>\n";
        echo "<li>Chọn time range: \"All time\"</li>\n";
        echo "<li>Click \"Clear data\"</li>\n";
        echo "<li><strong>Reload trang checkout</strong> (hoặc dùng Incognito mode: <code>Ctrl + Shift + N</code>)</li>\n";
        echo "<li>Kiểm tra lại - Bạn sẽ thấy <strong>4 phương thức riêng biệt</strong>, không trùng lặp!</li>\n";
        echo "</ol>\n";
        echo "</div>\n";
    } else {
        echo "<div class='alert alert-danger'>\n";
        echo "<p>Chạy lại script fix: <a href='fix_duplicate_shipping_fees.php' class='btn'>Fix Duplicates</a></p>\n";
        echo "</div>\n";
    }
    
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>";
}
