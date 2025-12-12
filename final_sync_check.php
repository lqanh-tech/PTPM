<?php
/**
 * Kiểm tra cuối cùng - So sánh Admin vs Frontend
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra cuối cùng - Admin vs Frontend</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; background: #ecf0f1; padding: 10px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .badge-success { background: #27ae60; color: white; padding: 5px 10px; border-radius: 3px; }
        .badge-error { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>
<h1>✅ KIỂM TRA CUỐI CÙNG - ADMIN VS FRONTEND</h1>
<p><strong>Ngày:</strong> " . date('d/m/Y H:i:s') . "</p>
";

// Test với giỏ hàng: 2.5kg, 500,000đ
$testWeight = 2.5;
$testValue = 500000;

echo "<h2>🧪 Test với giỏ hàng: {$testWeight}kg, " . number_format($testValue, 0, ',', '.') . " đ</h2>";

$stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order DESC");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Phương thức</th><th>Phí tính toán</th><th>Phí mong đợi (Frontend)</th><th>Trạng thái</th></tr>";

$expectedFees = [
    'ghn' => 35000,
    'standard' => 0, // Miễn phí vì >= 500,000đ
    'express' => 45000,
    'pickup' => 0
];

$allMatch = true;

foreach ($methods as $m) {
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, ?, ?) as fee");
    $stmt->execute([$m['id'], $testWeight, $testValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $calculatedFee = $result['fee'];
    
    $expectedFee = $expectedFees[$m['code']] ?? 0;
    $match = ($calculatedFee == $expectedFee);
    
    if (!$match) $allMatch = false;
    
    $statusBadge = $match ? 'badge-success' : 'badge-error';
    $statusText = $match ? '✅ Khớp' : '❌ Khác';
    
    echo "<tr>";
    echo "<td><strong>{$m['name']}</strong></td>";
    echo "<td>" . number_format($calculatedFee, 0, ',', '.') . " đ</td>";
    echo "<td>" . number_format($expectedFee, 0, ',', '.') . " đ</td>";
    echo "<td><span class='{$statusBadge}'>{$statusText}</span></td>";
    echo "</tr>";
}
echo "</table>";

// Test với giỏ hàng khác: 2.5kg, 300,000đ (không đủ miễn phí)
$testValue2 = 300000;

echo "<h2>🧪 Test với giỏ hàng: {$testWeight}kg, " . number_format($testValue2, 0, ',', '.') . " đ</h2>";

echo "<table>";
echo "<tr><th>Phương thức</th><th>Phí tính toán</th><th>Phí mong đợi</th><th>Trạng thái</th></tr>";

$expectedFees2 = [
    'ghn' => 35000,
    'standard' => 25000, // Không miễn phí
    'express' => 45000,
    'pickup' => 0
];

foreach ($methods as $m) {
    $stmt = $db->prepare("SELECT calculate_shipping_fee(?, 1, 1, ?, ?) as fee");
    $stmt->execute([$m['id'], $testWeight, $testValue2]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $calculatedFee = $result['fee'];
    
    $expectedFee = $expectedFees2[$m['code']] ?? 0;
    $match = ($calculatedFee == $expectedFee);
    
    if (!$match) $allMatch = false;
    
    $statusBadge = $match ? 'badge-success' : 'badge-error';
    $statusText = $match ? '✅ Khớp' : '❌ Khác';
    
    echo "<tr>";
    echo "<td><strong>{$m['name']}</strong></td>";
    echo "<td>" . number_format($calculatedFee, 0, ',', '.') . " đ</td>";
    echo "<td>" . number_format($expectedFee, 0, ',', '.') . " đ</td>";
    echo "<td><span class='{$statusBadge}'>{$statusText}</span></td>";
    echo "</tr>";
}
echo "</table>";

// Tổng kết
echo "<h2>📋 TỔNG KẾT</h2>";

if ($allMatch) {
    echo "<div style='padding: 20px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 5px;'>";
    echo "<h3 class='success'>🎉 TẤT CẢ DỮ LIỆU ĐÃ ĐỒNG BỘ HOÀN HẢO!</h3>";
    echo "<p>Admin và Frontend hiển thị phí vận chuyển giống hệt nhau.</p>";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background: #fff3cd; border-left: 4px solid #f39c12; border-radius: 5px;'>";
    echo "<h3 class='error'>⚠️ VẪN CÒN SỰ KHÁC BIỆT</h3>";
    echo "<p>Cần kiểm tra lại cấu hình.</p>";
    echo "</div>";
}

// Hiển thị cấu hình hiện tại
echo "<h2>⚙️ Cấu hình hiện tại</h2>";

foreach ($methods as $m) {
    echo "<h3>{$m['name']}</h3>";
    
    $stmt = $db->prepare("
        SELECT * FROM shipping_fees 
        WHERE shipping_method_id = ? AND is_active = 1 
        ORDER BY priority DESC
    ");
    $stmt->execute([$m['id']]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fees) == 0) {
        echo "<p class='error'>❌ Không có cấu hình phí active</p>";
        continue;
    }
    
    echo "<table>";
    echo "<tr><th>Tên</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Priority</th></tr>";
    foreach ($fees as $f) {
        echo "<tr>";
        echo "<td>{$f['name']}</td>";
        echo "<td>" . number_format($f['base_fee'], 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($f['fee_per_kg'], 0, ',', '.') . " đ</td>";
        echo "<td>" . ($f['min_order_free_ship'] ? number_format($f['min_order_free_ship'], 0, ',', '.') . " đ" : "Không") . "</td>";
        echo "<td>{$f['priority']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</div>
</body>
</html>";
