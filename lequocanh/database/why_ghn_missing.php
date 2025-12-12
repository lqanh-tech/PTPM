<?php
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; }
    th { background-color: #667eea; color: white; }
    .problem { background-color: #f8d7da; }
    .ok { background-color: #d4edda; }
    pre { background: #f8f9fa; padding: 15px; }
</style>\n";

echo "<h2>WHY IS GHN MISSING?</h2>\n";

// 1. Check if GHN exists and is active
echo "<h3>1. Check GHN in shipping_methods</h3>\n";
$stmt = $db->query("SELECT * FROM shipping_methods WHERE code = 'ghn'");
$ghn = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ghn) {
    echo "<p>✅ GHN exists</p>\n";
    echo "<pre>" . print_r($ghn, true) . "</pre>\n";
    
    if ($ghn['is_active'] == 1) {
        echo "<p style='color: green;'>✅ GHN is ACTIVE</p>\n";
    } else {
        echo "<p style='color: red;'>❌ GHN is INACTIVE!</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ GHN does NOT exist in database!</p>\n";
}

// 2. Check GHN fee configs
echo "<h3>2. Check GHN fee configs</h3>\n";
if ($ghn) {
    $stmt = $db->prepare("SELECT * FROM shipping_fees WHERE shipping_method_id = ?");
    $stmt->execute([$ghn['id']]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total fee configs: " . count($fees) . "</p>\n";
    
    $activeFees = array_filter($fees, function($f) { return $f['is_active'] == 1; });
    echo "<p>Active fee configs: " . count($activeFees) . "</p>\n";
    
    if (count($activeFees) == 0) {
        echo "<p style='color: red; font-size: 20px;'>❌ GHN HAS NO ACTIVE FEE CONFIG!</p>\n";
        echo "<p>This is why it's not showing!</p>\n";
    }
    
    echo "<table>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Base Fee</th><th>Priority</th><th>Active</th></tr>\n";
    foreach ($fees as $f) {
        $rowClass = $f['is_active'] ? 'ok' : 'problem';
        echo "<tr class='$rowClass'>";
        echo "<td>" . $f['id'] . "</td>";
        echo "<td>" . htmlspecialchars($f['name'] ?? '') . "</td>";
        echo "<td>" . number_format($f['base_fee']) . "₫</td>";
        echo "<td>" . $f['priority'] . "</td>";
        echo "<td>" . ($f['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// 3. Test direct query that shipping_method_selector uses
echo "<h3>3. Test Direct Query (Same as shipping_method_selector_v2.php)</h3>\n";
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
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Total results: " . count($results) . "</strong></p>\n";

echo "<table>\n";
echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Sort</th><th>Fee Count</th><th>Min Base Fee</th><th>Will Render?</th></tr>\n";
foreach ($results as $r) {
    $willRender = ($r['fee_config_count'] > 0) ? 'YES ✅' : 'NO ❌ (No fee config)';
    $rowClass = ($r['fee_config_count'] > 0) ? 'ok' : 'problem';
    
    echo "<tr class='$rowClass'>";
    echo "<td>" . $r['id'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($r['code']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($r['name']) . "</td>";
    echo "<td>" . $r['sort_order'] . "</td>";
    echo "<td><strong>" . $r['fee_config_count'] . "</strong></td>";
    echo "<td>" . number_format($r['min_base_fee'] ?? 0) . "₫</td>";
    echo "<td><strong>$willRender</strong></td>";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h3>CONCLUSION</h3>\n";
echo "<p>If GHN has <strong>fee_config_count = 0</strong>, that's the problem!</p>\n";
echo "<p>The query returns it, but it will be skipped or cause issues during rendering.</p>\n";
