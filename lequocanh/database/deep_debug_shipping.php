<?php

session_start();
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

$_SESSION['cart_weight'] = 1.0;
$_SESSION['cart_total'] = 100000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

$db = Database::getInstance()->getConnection();

echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 13px; }
    th { background-color: #667eea; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .duplicate { background-color: #f8d7da !important; }
    .missing { background-color: #fff3cd !important; }
    pre {background: #f8f9fa; padding: 15px; overflow-x: auto; font-size: 12px; }
    h2 { color: #667eea; }
</style>\n";

echo "<div class='container'>\n";
echo "<h2>🔬 DEEP DEBUG - Shipping Methods</h2>\n";

echo "<h3>1. VIEW Definition</h3>\n";
$stmt = $db->query("SHOW CREATE VIEW v_shipping_methods_with_fees");
$viewDef = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . htmlspecialchars($viewDef['Create View']) . "</pre>\n";

echo "<h3>2. Direct Query: shipping_methods Table</h3>\n";
$stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
$directMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Count:</strong> " . count($directMethods) . " methods</p>\n";
echo "<table>\n";
echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Active</th></tr>\n";
foreach ($directMethods as $m) {
    echo "<tr>";
    echo "<td>" . $m['id'] . "</td>";
    echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td><strong>" . $m['sort_order'] . "</strong></td>";
    echo "<td>" . ($m['is_active'] ? '✅' : '❌') . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>3. Query VIEW: v_shipping_methods_with_fees</h3>\n";
$stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
$viewMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Count:</strong> " . count($viewMethods) . " methods</p>\n";
echo "<table>\n";
echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Fee Count</th><th>Min Base Fee</th></tr>\n";
foreach ($viewMethods as $m) {
    echo "<tr>";
    echo "<td>" . $m['id'] . "</td>";
    echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td><strong>" . $m['sort_order'] . "</strong></td>";
    echo "<td>" . ($m['fee_config_count'] ?? 0) . "</td>";
    echo "<td>" . number_format($m['min_base_fee'] ?? 0) . "₫</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>4. Check Code Duplicates in shipping_methods</h3>\n";
$stmt = $db->query("SELECT code, COUNT(*) as count FROM shipping_methods GROUP BY code HAVING count > 1");
$codeDuplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($codeDuplicates)) {
    echo "<p style='color: green;'>✅ No code duplicates found</p>\n";
} else {
    echo "<p style='color: red;'>❌ Found code duplicates:</p>\n";
    echo "<table>\n";
    echo "<tr><th>Code</th><th>Count</th></tr>\n";
    foreach ($codeDuplicates as $dup) {
        echo "<tr class='duplicate'>";
        echo "<td><code>" . htmlspecialchars($dup['code']) . "</code></td>";
        echo "<td><strong>" . $dup['count'] . "</strong></td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    foreach ($codeDuplicates as $dup) {
        echo "<h4>All records with code: " . htmlspecialchars($dup['code']) . "</h4>\n";
        $stmt = $db->prepare("SELECT * FROM shipping_methods WHERE code = ?");
        $stmt->execute([$dup['code']]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>\n";
        echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Active</th><th>Created At</th></tr>\n";
        foreach ($records as $r) {
            echo "<tr class='duplicate'>";
            echo "<td>" . $r['id'] . "</td>";
            echo "<td><code>" . htmlspecialchars($r['code']) . "</code></td>";
            echo "<td>" . htmlspecialchars($r['name']) . "</td>";
            echo "<td>" . $r['sort_order'] . "</td>";
            echo "<td>" . ($r['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . $r['created_at'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

echo "<h3>5. ALL Shipping Methods (Including Inactive)</h3>\n";
$stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC, id ASC");
$allMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Total Count:</strong> " . count($allMethods) . " methods</p>\n";
echo "<table>\n";
echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort Order</th><th>Active</th><th>Created At</th></tr>\n";
foreach ($allMethods as $m) {
    $rowClass = $m['is_active'] ? '' : 'missing';
    echo "<tr class='$rowClass'>";
    echo "<td>" . $m['id'] . "</td>";
    echo "<td><code>" . htmlspecialchars($m['code']) . "</code></td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td><strong>" . $m['sort_order'] . "</strong></td>";
    echo "<td>" . ($m['is_active'] ? '✅' : '❌') . "</td>";
    echo "<td>" . $m['created_at'] . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "</div>\n";
