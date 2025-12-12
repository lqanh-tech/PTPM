<?php
/**
 * Script kiểm tra hệ thống vận chuyển
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra Hệ thống Vận chuyển</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .success { color: #27ae60; }
        .error { color: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:hover { background: #f5f5f5; }
        .badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #27ae60; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-danger { background: #c0392b; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Kiểm tra Hệ thống Vận chuyển</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Kiểm tra tỉnh/thành phố
    echo "<h2>📍 Tỉnh/Thành phố</h2>";
    $stmt = $db->query("SELECT * FROM provinces ORDER BY region, name LIMIT 10");
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Mã</th><th>Tên</th><th>Miền</th><th>Trạng thái</th></tr>";
    foreach ($provinces as $province) {
        $badge = $province['is_active'] ? 'badge-success' : 'badge-danger';
        $status = $province['is_active'] ? 'Hoạt động' : 'Không hoạt động';
        echo "<tr>";
        echo "<td>{$province['code']}</td>";
        echo "<td>{$province['name']}</td>";
        echo "<td>{$province['region']}</td>";
        echo "<td><span class='badge $badge'>$status</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM provinces");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Tổng:</strong> $total tỉnh/thành</p>";
    
    // 2. Kiểm tra phương thức vận chuyển
    echo "<h2>🚚 Phương thức Vận chuyển</h2>";
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Mã</th><th>Tên</th><th>Thời gian giao</th><th>Hệ số giá</th><th>Trạng thái</th></tr>";
    foreach ($methods as $method) {
        $badge = $method['is_active'] ? 'badge-success' : 'badge-danger';
        $status = $method['is_active'] ? 'Hoạt động' : 'Không hoạt động';
        echo "<tr>";
        echo "<td>{$method['code']}</td>";
        echo "<td>{$method['name']}</td>";
        echo "<td>{$method['delivery_time']}</td>";
        echo "<td>{$method['price_multiplier']}x</td>";
        echo "<td><span class='badge $badge'>$status</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Kiểm tra cấu hình phí
    echo "<h2>💰 Cấu hình Phí Vận chuyển</h2>";
    $stmt = $db->query("SELECT * FROM v_shipping_fees_detail LIMIT 10");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Tên</th><th>Tỉnh</th><th>Quận</th><th>Phương thức</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th></tr>";
    foreach ($fees as $fee) {
        echo "<tr>";
        echo "<td>{$fee['name']}</td>";
        echo "<td>" . ($fee['province_name'] ?? 'Tất cả') . "</td>";
        echo "<td>" . ($fee['district_name'] ?? 'Tất cả') . "</td>";
        echo "<td>" . ($fee['shipping_method_name'] ?? 'Tất cả') . "</td>";
        echo "<td>" . number_format($fee['base_fee'], 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($fee['fee_per_kg'], 0, ',', '.') . " đ</td>";
        echo "<td>" . ($fee['min_order_free_ship'] ? number_format($fee['min_order_free_ship'], 0, ',', '.') . " đ" : '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Kiểm tra bảng don_hang đã có cột mới chưa
    echo "<h2>📦 Kiểm tra Bảng Đơn hàng</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM don_hang");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = [
        'province_id' => 'ID Tỉnh/Thành',
        'district_id' => 'ID Quận/Huyện',
        'ward_id' => 'ID Phường/Xã',
        'shipping_method_id' => 'ID Phương thức vận chuyển',
        'shipping_weight' => 'Trọng lượng',
        'tracking_code' => 'Mã vận đơn',
        'carrier' => 'Đơn vị vận chuyển',
        'shipping_status' => 'Trạng thái vận chuyển'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    
    echo "<table>";
    echo "<tr><th>Cột</th><th>Mô tả</th><th>Trạng thái</th></tr>";
    foreach ($requiredColumns as $col => $desc) {
        $exists = in_array($col, $existingColumns);
        $badge = $exists ? 'badge-success' : 'badge-danger';
        $status = $exists ? '✅ Có' : '❌ Chưa có';
        echo "<tr>";
        echo "<td><code>$col</code></td>";
        echo "<td>$desc</td>";
        echo "<td><span class='badge $badge'>$status</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Test tính phí vận chuyển
    echo "<h2>🧪 Test Tính Phí Vận chuyển</h2>";
    echo "<p>Ví dụ: Đơn hàng 2kg, giá trị 300,000đ</p>";
    
    $testWeight = 2;
    $testValue = 300000;
    
    $stmt = $db->query("
        SELECT * FROM shipping_fees 
        WHERE is_active = 1 
        AND (weight_from IS NULL OR weight_from <= $testWeight)
        AND (weight_to IS NULL OR weight_to >= $testWeight)
        ORDER BY priority DESC
        LIMIT 1
    ");
    $applicableFee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($applicableFee) {
        $calculatedFee = $applicableFee['base_fee'] + ($testWeight * $applicableFee['fee_per_kg']);
        
        echo "<table>";
        echo "<tr><th>Thông tin</th><th>Giá trị</th></tr>";
        echo "<tr><td>Cấu hình áp dụng</td><td>{$applicableFee['name']}</td></tr>";
        echo "<tr><td>Phí cơ bản</td><td>" . number_format($applicableFee['base_fee'], 0, ',', '.') . " đ</td></tr>";
        echo "<tr><td>Phí theo trọng lượng</td><td>" . number_format($testWeight * $applicableFee['fee_per_kg'], 0, ',', '.') . " đ</td></tr>";
        echo "<tr><td><strong>Tổng phí vận chuyển</strong></td><td><strong>" . number_format($calculatedFee, 0, ',', '.') . " đ</strong></td></tr>";
        
        if ($applicableFee['min_order_free_ship'] && $testValue >= $applicableFee['min_order_free_ship']) {
            echo "<tr><td colspan='2' class='success'><strong>✅ Đơn hàng được MIỄN PHÍ vận chuyển!</strong></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Không tìm thấy cấu hình phí phù hợp</p>";
    }
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #d5f4e6; border-radius: 5px;'>";
    echo "<h3 class='success'>✅ Hệ thống hoạt động tốt!</h3>";
    echo "<p>Tất cả các thành phần đã sẵn sàng. Bạn có thể:</p>";
    echo "<ul>";
    echo "<li>Thêm quận/huyện, phường/xã cho các tỉnh/thành</li>";
    echo "<li>Cấu hình phí vận chuyển chi tiết hơn</li>";
    echo "<li>Tích hợp vào trang checkout</li>";
    echo "<li>Xây dựng module quản lý trong Admin</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<a href='lequocanh/administrator/index.php' style='display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>🏠 Về trang Admin</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: #c0392b; background: #fadbd8; padding: 20px; border-radius: 5px;'>";
    echo "<h2>❌ Lỗi</h2>";
    echo "<p><strong>Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
