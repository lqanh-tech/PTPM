<?php
/**
 * Debug Checkout Shipping Display
 * Kiểm tra chính xác dữ liệu nào đang được hiển thị
 */

session_start();
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Set giả lập session như trong checkout
    $_SESSION['cart_weight'] = $_SESSION['cart_weight'] ?? 1.0;
    $_SESSION['cart_total'] = $_SESSION['cart_total'] ?? 100000;
    $_SESSION['province_id'] = $_SESSION['province_id'] ?? 1;
    $_SESSION['district_id'] = $_SESSION['district_id'] ?? 1;
    
    $cartWeight = $_SESSION['cart_weight'];
    $cartValue = $_SESSION['cart_total'];
    $provinceId = $_SESSION['province_id'];
    $districtId = $_SESSION['district_id'];
    
    echo "<h2>Debug: Checkout Shipping Display</h2>\n";
    echo "<style>
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #667eea; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .duplicate { background-color: #f8d7da !important; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-warning { background-color: #fff3cd; border: 1px solid #ffeeba; }
        .alert-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>\n";
    
    echo "<div class='alert alert-info'>\n";
    echo "<strong>Session Data:</strong><br>\n";
    echo "Cart Weight: {$cartWeight}kg<br>\n";
    echo "Cart Value: " . number_format($cartValue) . "₫<br>\n";
    echo "Province ID: {$provinceId}<br>\n";
    echo "District ID: {$districtId}<br>\n";
    echo "</div>\n";
    
    // 1. Query giống CHÍNH XÁC như trong shipping_method_selector_v2.php
    echo "<h3>1. Query From VIEW (giống shipping_method_selector_v2.php)</h3>\n";
    
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
    $shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>Số methods từ VIEW: <strong>" . count($shippingMethods) . "</strong></div>\n";
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Fee Count</th><th>Min Base Fee</th></tr>\n";
    foreach ($shippingMethods as $method) {
        echo "<tr>";
        echo "<td>" . $method['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($method['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($method['name']) . "</td>";
        echo "<td>" . $method['sort_order'] . "</td>";
        echo "<td>" . ($method['fee_config_count'] ?? 0) . "</td>";
        echo "<td>" . number_format($method['min_base_fee'] ?? 0) . "₫</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 2. Tính phí cho từng phương thức (giống logic trong file)
    echo "<h3>2. Calculate Fees (giống logic trong shipping_method_selector_v2.php)</h3>\n";
    
    echo "<table>\n";
    echo "<tr><th>Index</th><th>ID</th><th>Code</th><th>Name</th><th>Calculated Fee</th><th>Base Fee</th><th>Fee/kg</th><th>Min Free Ship</th><th>Is Free</th></tr>\n";
    
    $processedMethods = [];
    foreach ($shippingMethods as $index => &$method) {
        // Gọi function tính phí
        $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
        $stmt->execute([$method['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $method['calculated_fee'] = $result['fee'] ?? 0;
        
        // Lấy thông tin chi tiết phí
        $stmt = $db->prepare("
            SELECT base_fee, fee_per_kg, min_order_free_ship
            FROM shipping_fees
            WHERE shipping_method_id = ? AND is_active = 1
            ORDER BY priority DESC
            LIMIT 1
        ");
        $stmt->execute([$method['id']]);
        $feeDetail = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $method['base_fee'] = $feeDetail['base_fee'] ?? 0;
        $method['fee_per_kg'] = $feeDetail['fee_per_kg'] ?? 0;
        $method['min_free_ship'] = $feeDetail['min_order_free_ship'] ?? 0;
        $method['is_free'] = ($method['calculated_fee'] == 0);
        
        // Check if this is a duplicate
        $isDuplicate = false;
        $key = $method['code'] . '_' . $method['name'] . '_' . $method['calculated_fee'];
        if (isset($processedMethods[$key])) {
            $isDuplicate = true;
        }
        $processedMethods[$key] = true;
        
        $rowClass = $isDuplicate ? 'duplicate' : '';
        
        echo "<tr class='$rowClass'>";
        echo "<td>$index</td>";
        echo "<td>" . $method['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($method['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($method['name']) . "</td>";
        echo "<td><strong>" . number_format($method['calculated_fee']) . "₫</strong></td>";
        echo "<td>" . number_format($method['base_fee']) . "₫</td>";
        echo "<td>" . number_format($method['fee_per_kg']) . "₫</td>";
        echo "<td>" . number_format($method['min_free_ship']) . "₫</td>";
        echo "<td>" . ($method['is_free'] ? '✅' : '❌') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 3. Kiểm tra có duplicate không
    echo "<h3>3. Check Duplicates</h3>\n";
    
    $duplicates = [];
    $seen = [];
    foreach ($shippingMethods as $idx => $method) {
        $key = $method['code'];
        if (isset($seen[$key])) {
            $duplicates[] = [
                'index' => $idx,
                'code' => $method['code'],
                'name' => $method['name'],
                'first_seen_at' => $seen[$key]
            ];
        } else {
            $seen[$key] = $idx;
        }
    }
    
    if (empty($duplicates)) {
        echo "<div class='alert alert-info'>✅ Không có duplicate trong dữ liệu từ VIEW</div>\n";
    } else {
        echo "<div class='alert alert-warning'>\n";
        echo "<strong>⚠️ Tìm thấy duplicates:</strong><br>\n";
        foreach ($duplicates as $dup) {
            echo "• Code <code>{$dup['code']}</code> xuất hiện ở index {$dup['index']} (đã thấy lần đầu ở index {$dup['first_seen_at']})<br>\n";
        }
        echo "</div>\n";
    }
    
    // 4. Show final array that will be rendered
    echo "<h3>4. Final Data (sẽ được render trong HTML)</h3>\n";
    echo "<pre>\n";
    foreach ($shippingMethods as $idx => $method) {
        echo "[$idx] " . $method['code'] . " - " . $method['name'] . " - " . number_format($method['calculated_fee']) . "₫\n";
    }
    echo "</pre>\n";
    
    // 5. Direct query to shipping_methods table
    echo "<h3>5. Direct Query to shipping_methods (bypass VIEW)</h3>\n";
    $stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
    $directMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>Số methods trực tiếp từ bảng: <strong>" . count($directMethods) . "</strong></div>\n";
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th></tr>\n";
    foreach ($directMethods as $m) {
        echo "<tr>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
        echo "<td>" . htmlspecialchars($m['name']) . "</td>";
        echo "<td>" . $m['sort_order'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>";
}
