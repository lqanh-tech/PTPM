<?php

session_start();
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

$_SESSION['cart_weight'] = 1.0;
$_SESSION['cart_total'] = 100000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

$cartWeight = $_SESSION['cart_weight'];
$cartValue = $_SESSION['cart_total'];
$provinceId = $_SESSION['province_id'];
$districtId = $_SESSION['district_id'];

$db = Database::getInstance()->getConnection();

echo "<h2>Test Direct Query</h2>\n";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #667eea; color: white; }
    pre { background: #f8f9fa; padding: 15px; overflow-x: auto; }
</style>\n";

echo "<h3>Query FROM shipping_method_selector_v2.php (Line 18)</h3>\n";
echo "<pre>SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC</pre>\n";

$stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Rows returned:</strong> " . count($shippingMethods) . "</p>\n";

echo "<table>\n";
echo "<tr><th>#</th><th>ID</th><th>Code</th><th>Name</th><th>Sort</th><th>Active</th></tr>\n";
foreach ($shippingMethods as $idx => $method) {
    echo "<tr>";
    echo "<td>$idx</td>";
    echo "<td>" . $method['id'] . "</td>";
    echo "<td><code>" . htmlspecialchars($method['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($method['name']) . "</td>";
    echo "<td>" . $method['sort_order'] . "</td>";
    echo "<td>" . ($method['is_active'] ? '✅' : '❌') . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>Calculate Fees (Line 23-43)</h3>\n";

foreach ($shippingMethods as $index => &$method) {

    $stmt = $db->prepare("
        SELECT base_fee, fee_per_kg, min_order_free_ship
        FROM shipping_fees
        WHERE shipping_method_id = ? AND is_active = 1
        ORDER BY priority DESC
        LIMIT 1
    ");
    $stmt->execute([$method['id']]);
    $feeDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($feeDetail) {
        $method['base_fee'] = $feeDetail['base_fee'];
        $method['fee_per_kg'] = $feeDetail['fee_per_kg'];
        $method['min_free_ship'] = $feeDetail['min_order_free_ship'];
    } else {
        $method['base_fee'] = 0;
        $method['fee_per_kg'] = 0;
        $method['min_free_ship'] = 0;
    }
    
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
    $stmt->execute([$method['id'], $provinceId, $districtId, $cartWeight, $cartValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $method['calculated_fee'] = $result['fee'] ?? 0;
    
    $method['is_free'] = ($method['calculated_fee'] == 0);
}

echo "<table>\n";
echo "<tr><th>#</th><th>Code</th><th>Name</th><th>Base Fee</th><th>Calculated Fee</th><th>Is Free</th></tr>\n";
foreach ($shippingMethods as $idx => $method) {
    echo "<tr>";
    echo "<td>$idx</td>";
    echo "<td><code>" . htmlspecialchars($method['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($method['name']) . "</td>";
    echo "<td>" . number_format($method['base_fee']) . "₫</td>";
    echo "<td><strong>" . number_format($method['calculated_fee']) . "₫</strong></td>";
    echo "<td>" . ($method['is_free'] ? '✅ FREE' : '❌') . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>HTML Output (foreach loop Line 54-148)</h3>\n";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>\n";
echo "<p><strong>Number of cards that will be rendered:</strong> " . count($shippingMethods) . "</p>\n";
echo "<ol>\n";
foreach ($shippingMethods as $index => $method) {
    echo "<li>";
    echo "Card #$index: <code>" . $method['code'] . "</code> - " . htmlspecialchars($method['name']) . " - " . number_format($method['calculated_fee']) . "₫";
    if ($index === 0) {
        echo " <span style='color: green;'>(DEFAULT SELECTED)</span>";
    }
    echo "</li>\n";
}
echo "</ol>\n";
echo "</div>\n";
