<?php
/**
 * Fix Duplicate Shipping Fees for "Giao hàng nhanh" (express)
 * Automatically detects and disables duplicate fee configs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h3 { color: #764ba2; margin-top: 30px; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .alert-warning { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .keep { background-color: #d4edda !important; }
        .disable { background-color: #f8d7da !important; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; color: #e83e8c; }
    </style>\n";
    
    echo "<div class='container'>\n";
    echo "<h2>🔧 Sửa Lỗi Trùng Lặp Shipping Fees</h2>\n";
    
    // Step 1: Find all shipping methods with multiple active fee configs
    echo "<h3>Bước 1: Tìm Phương Thức Có Nhiều Fee Configs</h3>\n";
    
    $stmt = $db->query("
        SELECT 
            sm.id,
            sm.code,
            sm.name,
            COUNT(sf.id) as active_fee_count
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
        GROUP BY sm.id, sm.code, sm.name
        HAVING active_fee_count > 1
    ");
    $duplicateMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateMethods)) {
        echo "<div class='alert alert-success'>✅ Không tìm thấy phương thức nào có fee configs trùng lặp!</div>\n";
        echo "</div>";
        exit;
    }
    
    echo "<div class='alert alert-warning'>\n";
    echo "<strong>⚠️ Tìm thấy " . count($duplicateMethods) . " phương thức có fee configs trùng lặp:</strong><br>\n";
    foreach ($duplicateMethods as $method) {
        echo "• " . htmlspecialchars($method['name']) . " (<code>" . htmlspecialchars($method['code']) . "</code>) - " . $method['active_fee_count'] . " fee configs active<br>\n";
    }
    echo "</div>\n";
    
    // Step 2: Process each duplicate method
    echo "<h3>Bước 2: Xử Lý Từng Phương Thức</h3>\n";
    
    $db->beginTransaction();
    $totalDisabled = 0;
    
    foreach ($duplicateMethods as $method) {
        echo "<h4>Xử lý: " . htmlspecialchars($method['name']) . " (<code>" . htmlspecialchars($method['code']) . "</code>)</h4>\n";
        
        // Get all active fee configs for this method
        $stmt = $db->prepare("
            SELECT * FROM shipping_fees 
            WHERE shipping_method_id = ? AND is_active = 1
            ORDER BY priority DESC, id ASC
        ");
        $stmt->execute([$method['id']]);
        $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>\n";
        echo "<tr><th>ID</th><th>Name</th><th>Base Fee</th><th>Fee/kg</th><th>Free From</th><th>Priority</th><th>Hành Động</th></tr>\n";
        
        $isFirst = true;
        foreach ($fees as $fee) {
            $action = '';
            $rowClass = '';
            
            if ($isFirst) {
                // Keep the first one (highest priority)
                $action = '✅ <strong>GIỮ LẠI</strong> (Priority cao nhất)';
                $rowClass = 'keep';
                $isFirst = false;
            } else {
                // Disable others
                $action = '❌ <strong>TẮT</strong> (Priority thấp hơn)';
                $rowClass = 'disable';
                
                // Disable this fee
                $updateStmt = $db->prepare("UPDATE shipping_fees SET is_active = 0 WHERE id = ?");
                $updateStmt->execute([$fee['id']]);
                $totalDisabled++;
            }
            
            echo "<tr class='$rowClass'>";
            echo "<td>" . $fee['id'] . "</td>";
            echo "<td>" . htmlspecialchars($fee['name'] ?? '') . "</td>";
            echo "<td>" . number_format($fee['base_fee'] ?? 0) . "₫</td>";
            echo "<td>" . number_format($fee['fee_per_kg'] ?? 0) . "₫</td>";
            echo "<td>" . number_format($fee['min_order_free_ship'] ?? 0) . "₫</td>";
            echo "<td><strong>" . ($fee['priority'] ?? 0) . "</strong></td>";
            echo "<td>$action</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    }
    
    // Commit transaction
    $db->commit();
    
    echo "<div class='alert alert-success'>\n";
    echo "<h3>✅ Hoàn Tất!</h3>\n";
    echo "<p>Đã tắt <strong>$totalDisabled</strong> fee config(s) trùng lặp.</p>\n";
    echo "<p>Mỗi phương thức giờ chỉ còn <strong>1 fee config active</strong> (có priority cao nhất).</p>\n";
    echo "</div>\n";
    
    // Step 3: Verify results
    echo "<h3>Bước 3: Kiểm Tra Kết Quả</h3>\n";
    
    $stmt = $db->query("
        SELECT 
            sm.id,
            sm.code,
            sm.name,
            sm.is_active,
            COUNT(CASE WHEN sf.is_active = 1 THEN 1 END) as active_fee_count
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id
        GROUP BY sm.id, sm.code, sm.name, sm.is_active
        ORDER BY sm.sort_order DESC
    ");
    $allMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Method Active</th><th>Active Fee Configs</th><th>Status</th></tr>\n";
    
    foreach ($allMethods as $m) {
        $status = '';
        $rowClass = '';
        
        if ($m['is_active'] == 1 && $m['active_fee_count'] == 1) {
            $status = '✅ OK';
            $rowClass = 'keep';
        } elseif ($m['is_active'] == 1 && $m['active_fee_count'] == 0) {
            $status = '⚠️ Không có fee config';
            $rowClass = 'alert-warning';
        } elseif ($m['is_active'] == 1 && $m['active_fee_count'] > 1) {
            $status = '❌ Vẫn còn trùng lặp!';
            $rowClass = 'disable';
        } else {
            $status = '⏸️ Inactive';
        }
        
        echo "<tr class='$rowClass'>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name']) . "</td>";
        echo "<td>" . ($m['is_active'] ? '✅' : '❌') . "</td>";
        echo "<td><strong>" . $m['active_fee_count'] . "</strong></td>";
        echo "<td>$status</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Step 4: Next steps
    echo "<h3>Bước 4: Các Bước Tiếp Theo</h3>\n";
    echo "<div class='alert alert-info'>\n";
    echo "<ol>\n";
    echo "<li>✅ <strong>Clear cache trình duyệt</strong></li>\n";
    echo "<li>✅ <strong>Reload trang checkout</strong>: <a href='http://localhost:20080/lequocanh/administrator/elements_LQA/mgiohang/checkout.php' target='_blank'>Checkout Page</a></li>\n";
    echo "<li>✅ <strong>Kiểm tra</strong> xem phương thức 'Giao hàng nhanh' còn hiển thị 2 lần không</li>\n";
    echo "<li>✅ <strong>Xác nhận</strong> tất cả 4 phương thức hiển thị đúng (GHN, Tiêu chuẩn, Nhanh, Lấy tại cửa hàng)</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
    echo "</div>\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ Lỗi!</h3>\n";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>";
}
