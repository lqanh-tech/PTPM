<?php
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

echo "<pre>";

// 1. Show Function Definition
echo "<h2>Function Definition: calculate_shipping_fee</h2>";
try {
    $stmt = $db->query("SHOW CREATE FUNCTION calculate_shipping_fee");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo htmlspecialchars($res['Create Function']);
} catch (Exception $e) {
    echo "Error showing function: " . $e->getMessage();
}

// 2. Test Call for GHN
echo "\n\n<h2>Test Call for GHN (ID: ?)</h2>";
// Find GHN ID
$stmt = $db->query("SELECT id FROM shipping_methods WHERE code = 'ghn'");
$ghnId = $stmt->fetchColumn();
echo "GHN ID: " . $ghnId . "\n";

if ($ghnId) {
    try {
        // Call with dummy values
        $sql = "SELECT calculate_shipping_fee($ghnId, 1, 1, 1.0, 100000) as fee";
        echo "Executing: $sql\n";
        $stmt = $db->query($sql);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Result: " . print_r($res, true);
    } catch (Exception $e) {
        echo "❌ ERROR calling function: " . $e->getMessage();
    }
}
echo "</pre>";
