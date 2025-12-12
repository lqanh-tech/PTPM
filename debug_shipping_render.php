<?php
/**
 * Debug script - Kiểm tra xem shipping methods có bị render duplicate không
 */
session_start();

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

// Giả lập session cho test
$_SESSION['cart_weight'] = 1.0;
$_SESSION['cart_total'] = 300000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;

$cartWeight = $_SESSION['cart_weight'] ?? 1.0;
$cartValue = $_SESSION['cart_total'] ?? 0;
$provinceId = $_SESSION['province_id'] ?? 1;
$districtId = $_SESSION['district_id'] ?? 1;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Debug Shipping Methods Rendering</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .debug-info { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .duplicate { background: #ffcccc !important; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Debug Shipping Methods Rendering</h1>";

echo "<div class='debug-info'>";
echo "<h3>Session Info:</h3>";
echo "Cart Weight: {$cartWeight} kg<br>";
echo "Cart Value: " . number_format($cartValue, 0, ',', '.') . " ₫<br>";
echo "Province ID: {$provinceId}<br>";
echo "District ID: {$districtId}<br>";
echo "</div>";

// Query giống y hệt trong shipping_method_selector_v2.php
$stmt = $db->query("
    SELECT 
        sm.*,
        COUNT(DISTINCT sf.id) as fee_config_count,
        MIN(sf.base_fee) as min_base_fee,
        MIN(sf.min_order_free_ship) as min_free_ship_threshold
    FROM shipping_methods sm
    LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
    WHERE sm.is_active = 1
    GROUP BY sm.id
    ORDER BY sm.sort_order DESC
");
$shippingMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<div class='debug-info'>";
echo "<h3>Query Result:</h3>";
echo "Số bản ghi trả về: <strong>" . count($shippingMethods) . "</strong><br>";
echo "SQL: <pre>" . $stmt->queryString . "</pre>";
echo "</div>";

// Kiểm tra duplicate
$codes = [];
$duplicateIndexes = [];
foreach ($shippingMethods as $index => $method) {
    if (in_array($method['code'], $codes)) {
        $duplicateIndexes[] = $index;
    }
    $codes[] = $method['code'];
}

if (count($duplicateIndexes) > 0) {
    echo "<div style='background: #ffcccc; padding: 15px; margin-bottom: 20px; border: 2px solid red; border-radius: 5px;'>";
    echo "<h3>⚠️ PHÁT HIỆN DUPLICATE!</h3>";
    echo "Các index bị trùng: " . implode(', ', $duplicateIndexes);
    echo "</div>";
}

// Hiển thị bảng
echo "<h2>📋 Danh sách phương thức (như sẽ render):</h2>";
echo "<table>";
echo "<tr>
        <th>#</th>
        <th>ID</th>
        <th>Code</th>
        <th>Tên</th>
        <th>Mô tả</th>
        <th>Thời gian giao</th>
        <th>Sort Order</th>
        <th>Fee Count</th>
      </tr>";

foreach ($shippingMethods as $index => $method) {
    $isDuplicate = in_array($index, $duplicateIndexes);
    $rowClass = $isDuplicate ? "class='duplicate'" : "";
    
    echo "<tr $rowClass>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td>{$method['id']}</td>";
    echo "<td><strong>{$method['code']}</strong></td>";
    echo "<td>{$method['name']}</td>";
    echo "<td>{$method['description']}</td>";
    echo "<td>{$method['delivery_time']}</td>";
    echo "<td>{$method['sort_order']}</td>";
    echo "<td>{$method['fee_config_count']}</td>";
    echo "</tr>";
}

echo "</table>";

// Render giống y hệt trong shipping_method_selector_v2.php
echo "<h2>🎨 HTML Render (giống trong checkout):</h2>";
echo "<div style='border: 2px solid #667eea; padding: 20px; border-radius: 10px; background: white;'>";
echo "<h3 style='color: #667eea;'>Phương thức vận chuyển</h3>";
echo "<table>";
echo "<thead>
        <tr style='background: #f8f9fa;'>
            <th></th>
            <th>Tên</th>
            <th>Mô tả</th>
            <th>Thời gian giao</th>
            <th>Phí hiện tại</th>
        </tr>
      </thead>";
echo "<tbody>";

$renderCount = 0;
foreach ($shippingMethods as $index => $method) {
    $renderCount++;
    $isSelected = $index === 0;
    
    echo "<tr style='cursor: pointer; border-bottom: 1px solid #dee2e6;'>";
    echo "<td style='text-align: center;'>";
    echo "<input type='radio' name='shipping_method' value='{$method['code']}' " . ($isSelected ? 'checked' : '') . ">";
    echo "</td>";
    echo "<td><strong>{$method['name']}</strong></td>";
    echo "<td>{$method['description']}</td>";
    echo "<td>{$method['delivery_time']}</td>";
    echo "<td><strong>25.000₫</strong></td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "<p><strong>Tổng số dòng đã render: {$renderCount}</strong></p>";
echo "</div>";

// Kiểm tra xem có loop nào khác không
echo "<h2>🔬 Phân tích chi tiết:</h2>";
echo "<div class='debug-info'>";
echo "<h4>Các code trong mảng:</h4>";
echo "<pre>" . print_r(array_column($shippingMethods, 'code'), true) . "</pre>";

echo "<h4>Các ID trong mảng:</h4>";
echo "<pre>" . print_r(array_column($shippingMethods, 'id'), true) . "</pre>";

echo "<h4>Kiểm tra unique:</h4>";
$codes = array_column($shippingMethods, 'code');
$uniqueCodes = array_unique($codes);
echo "Tổng codes: " . count($codes) . "<br>";
echo "Unique codes: " . count($uniqueCodes) . "<br>";
echo "Có duplicate: " . (count($codes) !== count($uniqueCodes) ? "CÓ ❌" : "KHÔNG ✅");
echo "</div>";

echo "</body></html>";
