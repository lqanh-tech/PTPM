<?php
session_start();
$_SESSION['cart_weight'] = 1.0;
$_SESSION['cart_total'] = 100000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$cartWeight = 1.0;
$cartValue = 100000;
$provinceId = 1;
$districtId = 1;

echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #667eea; color: white; }
    .error { background-color: #f8d7da; }
    .warning { background-color: #fff3cd; }
    .ok { background-color: #d4edda; }
    pre { background: #f8f9fa; padding: 15px; overflow-x: auto; }
</style>\n";

echo "<h2>Test Each Method calculate_shipping_fee()</h2>\n";

$stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>\n";
echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort</th><th>Has Fee Config</th><th>calculate_shipping_fee() Result</th><th>Error</th><th>Status</th></tr>\n";

foreach ($methods as $m) {

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM shipping_fees WHERE shipping_method_id = ? AND is_active = 1");
    $stmt->execute([$m['id']]);
    $feeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $hasFee = $feeCount > 0;
    
    $fee = null;
    $error = null;
    $rowClass = '';
    
    try {
        $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
        $stmt->execute([$m['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $fee = $result['fee'];
        
        if ($fee === null) {
            $rowClass = 'warning';
            $error = 'Function returned NULL';
        } else {
            $rowClass = 'ok';
        }
    } catch (Exception $e) {
        $rowClass = 'error';
        $error = $e->getMessage();
    }
    
    echo "<tr class='$rowClass'>";
    echo "<td>" . $m['id'] . "</td>";
    echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td>" . $m['sort_order'] . "</td>";
    echo "<td>" . ($hasFee ? "✅ $feeCount config(s)" : '❌ No config') . "</td>";
    echo "<td>" . ($fee !== null ? number_format($fee) . '₫' : 'NULL') . "</td>";
    echo "<td>" . ($error ?? '-') . "</td>";
    echo "<td>" . ($fee !== null && $hasFee ? '✅ OK' : '❌ PROBLEM') . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h3>Full render test</h3>\n";
echo "<p>Now test the full query with GROUP BY:</p>\n";

$stmt = $db->query("
    SELECT 
        sm.*,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Methods from GROUP BY query:</strong> " . count($shippingMethods) . "</p>\n";

echo "<table>\n";
echo "<tr><th>#</th><th>ID</th><th>Code</th><th>Name</th><th>Sort</th><th>Fee Count</th></tr>\n";
foreach ($shippingMethods as $idx => $m) {
    echo "<tr>";
    echo "<td>$idx</td>";
    echo "<td>" . $m['id'] . "</td>";
    echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td>" . $m['sort_order'] . "</td>";
    echo "<td>" . $m['fee_config_count'] . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>After calculating fees</h3>\n";

foreach ($shippingMethods as &$method) {
    try {
        $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
        $stmt->execute([$method['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $method['calculated_fee'] = $result['fee'] ?? 0;
        $method['calc_status'] = ($result['fee'] !== null) ? 'OK' :  'NULL';
    } catch (Exception $e) {
        $method['calculated_fee'] = 0;
        $method['calc_status'] = 'ERROR: ' . $e->getMessage();
    }
}

echo "<table>\n";
echo "<tr><th>#</th><th>Code</th><th>Name</th><th>Calculated Fee</th><th>Status</th></tr>\n";
foreach ($shippingMethods as $idx => $m) {
    $rowClass = ($m['calc_status'] === 'OK') ? 'ok' : 'error';
    echo "<tr class='$rowClass'>";
    echo "<td>$idx</td>";
    echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td>" . number_format($m['calculated_fee']) . "₫</td>";
    echo "<td>" . $m['calc_status'] . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<p><strong>Methods that will be rendered:</strong> " . count($shippingMethods) . "</p>\n";
