<?php
/**
 * Kiểm tra dữ liệu thực tế trong database
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra dữ liệu thực tế</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; background: #ecf0f1; padding: 10px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .diff { background: #fff3cd; padding: 10px; border-left: 4px solid #f39c12; margin: 10px 0; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔍 KIỂM TRA DỮ LIỆU THỰC TẾ</h1>
<p><strong>Ngày:</strong> " . date('d/m/Y H:i:s') . "</p>
";

// 1. Kiểm tra bảng shipping_methods
echo "<h2>📋 Bảng shipping_methods</h2>";
$stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Mã</th><th>Tên</th><th>Hệ số giá</th><th>Thứ tự</th><th>Trạng thái</th></tr>";
foreach ($methods as $m) {
    echo "<tr>";
    echo "<td>{$m['id']}</td>";
    echo "<td><strong>{$m['code']}</strong></td>";
    echo "<td>{$m['name']}</td>";
    echo "<td>{$m['price_multiplier']}x</td>";
    echo "<td>{$m['sort_order']}</td>";
    echo "<td>" . ($m['is_active'] ? '✅ Active' : '❌ Inactive') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Kiểm tra bảng shipping_fees
echo "<h2>💰 Bảng shipping_fees</h2>";
foreach ($methods as $m) {
    if (!$m['is_active']) continue;
    
    echo "<h3>Phương thức: {$m['name']} (ID: {$m['id']})</h3>";
    
    $stmt = $db->prepare("SELECT * FROM shipping_fees WHERE shipping_method_id = ? ORDER BY priority DESC");
    $stmt->execute([$m['id']]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fees) == 0) {
        echo "<div class='diff'>⚠️ Không có cấu hình phí!</div>";
        continue;
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Tên</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Priority</th><th>Active</th></tr>";
    foreach ($fees as $f) {
        echo "<tr>";
        echo "<td>{$f['id']}</td>";
        echo "<td>{$f['name']}</td>";
        echo "<td>" . number_format($f['base_fee'], 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($f['fee_per_kg'], 0, ',', '.') . " đ</td>";
        echo "<td>" . ($f['min_order_free_ship'] ? number_format($f['min_order_free_ship'], 0, ',', '.') . " đ" : "Không") . "</td>";
        echo "<td>{$f['priority']}</td>";
        echo "<td>" . ($f['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Test tính phí với giỏ hàng mẫu
echo "<h2>🧪 Test tính phí (Giỏ hàng: 2.5kg, 500,000đ)</h2>";

$testWeight = 2.5;
$testValue = 500000;
$testProvinceId = 1;
$testDistrictId = 1;

echo "<table>";
echo "<tr><th>Phương thức</th><th>Phí tính toán (Function)</th><th>Phí tính toán (Manual)</th><th>Khớp?</th></tr>";

foreach ($methods as $m) {
    if (!$m['is_active']) continue;
    
    // Tính phí bằng function
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, ?, ?, ?, ?) as fee");
    $stmt->execute([$m['id'], $testProvinceId, $testDistrictId, $testWeight, $testValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $functionFee = $result['fee'] ?? 0;
    
    // Tính phí manual
    $stmt = $db->prepare("
        SELECT base_fee, fee_per_kg, min_order_free_ship
        FROM shipping_fees
        WHERE shipping_method_id = ? AND is_active = 1
        ORDER BY priority DESC
        LIMIT 1
    ");
    $stmt->execute([$m['id']]);
    $feeConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($feeConfig) {
        $manualFee = $feeConfig['base_fee'] + ($testWeight * $feeConfig['fee_per_kg']);
        $manualFee *= $m['price_multiplier'];
        
        // Check miễn phí
        if ($feeConfig['min_order_free_ship'] > 0 && $testValue >= $feeConfig['min_order_free_ship']) {
            $manualFee = 0;
        }
    } else {
        $manualFee = 0;
    }
    
    $match = ($functionFee == $manualFee);
    
    echo "<tr>";
    echo "<td><strong>{$m['name']}</strong></td>";
    echo "<td>" . number_format($functionFee, 0, ',', '.') . " đ</td>";
    echo "<td>" . number_format($manualFee, 0, ',', '.') . " đ</td>";
    echo "<td>" . ($match ? '<span class="success">✅ Khớp</span>' : '<span class="error">❌ Khác</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Kiểm tra view
echo "<h2>👁️ View v_shipping_methods_with_fees</h2>";
try {
    $stmt = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order DESC");
    $viewData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Mã</th><th>Tên</th><th>Hệ số giá</th></tr>";
    foreach ($viewData as $v) {
        echo "<tr>";
        echo "<td>{$v['id']}</td>";
        echo "<td><strong>{$v['code']}</strong></td>";
        echo "<td>{$v['name']}</td>";
        echo "<td>{$v['price_multiplier']}x</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<div class='diff'>❌ Lỗi: " . $e->getMessage() . "</div>";
}

echo "</div>
</body>
</html>";
