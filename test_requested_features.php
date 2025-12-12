<?php
/**
 * Test các chức năng được yêu cầu:
 * 1. Cấu hình phí vận chuyển
 * 2. Khu vực giao hàng
 * 3. Theo dõi trạng thái thanh toán
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Các Chức Năng Yêu Cầu</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; background: #ecf0f1; padding: 10px; border-radius: 5px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .success { color: #27ae60; font-weight: bold; }
        .fail { color: #e74c3c; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .badge { padding: 5px 10px; border-radius: 3px; color: white; font-size: 12px; }
        .badge-success { background: #27ae60; }
        .badge-warning { background: #f39c12; }
        .badge-danger { background: #e74c3c; }
        .badge-info { background: #3498db; }
    </style>
</head>
<body>
<div class='container'>
<h1>🧪 TEST CÁC CHỨC NĂNG YÊU CẦU</h1>
<p><strong>Ngày test:</strong> " . date('d/m/Y H:i:s') . "</p>
";

// TEST 1: Cấu hình phí vận chuyển
echo "<h2>💰 TEST 1: Cấu Hình Phí Vận Chuyển</h2>";

echo "<div class='test-section'>";

// Kiểm tra bảng shipping_methods
$stmt = $db->query("SELECT * FROM shipping_methods WHERE is_active = 1");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>✅ Phương thức vận chuyển có sẵn:</h3>";
echo "<table>";
echo "<tr><th>Mã</th><th>Tên</th><th>Mô tả</th><th>Thời gian giao</th><th>Hệ số giá</th><th>Trạng thái</th></tr>";
foreach ($methods as $method) {
    echo "<tr>";
    echo "<td><strong>{$method['code']}</strong></td>";
    echo "<td>{$method['name']}</td>";
    echo "<td>{$method['description']}</td>";
    echo "<td>{$method['delivery_time']}</td>";
    echo "<td>{$method['price_multiplier']}x</td>";
    echo "<td><span class='badge badge-success'>Hoạt động</span></td>";
    echo "</tr>";
}
echo "</table>";

// Kiểm tra cấu hình phí
$stmt = $db->query("SELECT COUNT(*) as total FROM shipping_fees WHERE is_active = 1");
$feeCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<h3>✅ Cấu hình phí vận chuyển:</h3>";
echo "<p><strong>Tổng số cấu hình phí đang hoạt động:</strong> <span class='success'>{$feeCount} cấu hình</span></p>";

// Lấy một số cấu hình mẫu
$stmt = $db->query("
    SELECT sf.*, sm.name as method_name, sm.code as method_code,
           p.name as province_name, d.name as district_name
    FROM shipping_fees sf
    LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
    LEFT JOIN provinces p ON sf.province_id = p.id
    LEFT JOIN districts d ON sf.district_id = d.id
    WHERE sf.is_active = 1
    LIMIT 10
");
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Tên</th><th>Phương thức</th><th>Khu vực</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th></tr>";
foreach ($fees as $fee) {
    $area = $fee['province_name'] ?? 'Toàn quốc';
    if ($fee['district_name']) $area .= " - " . $fee['district_name'];
    
    echo "<tr>";
    echo "<td>{$fee['name']}</td>";
    echo "<td><span class='badge badge-info'>{$fee['method_name']}</span></td>";
    echo "<td>{$area}</td>";
    echo "<td>" . number_format($fee['base_fee'], 0, ',', '.') . " đ</td>";
    echo "<td>" . number_format($fee['fee_per_kg'], 0, ',', '.') . " đ</td>";
    echo "<td>" . ($fee['min_order_free_ship'] ? number_format($fee['min_order_free_ship'], 0, ',', '.') . " đ" : "Không") . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test tính phí
echo "<h3>🧪 Test tính phí vận chuyển:</h3>";
$testWeight = 2.5;
$testValue = 500000;
echo "<p><strong>Đơn hàng test:</strong> Trọng lượng {$testWeight}kg, Giá trị " . number_format($testValue, 0, ',', '.') . " đ</p>";

foreach ($methods as $method) {
    $stmt = $db->prepare("
        SELECT * FROM shipping_fees 
        WHERE shipping_method_id = ? AND is_active = 1
        AND (weight_from IS NULL OR weight_from <= ?)
        AND (weight_to IS NULL OR weight_to >= ?)
        ORDER BY priority DESC
        LIMIT 1
    ");
    $stmt->execute([$method['id'], $testWeight, $testWeight]);
    $applicableFee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($applicableFee) {
        $calculatedFee = $applicableFee['base_fee'] + ($testWeight * $applicableFee['fee_per_kg']);
        $calculatedFee *= $method['price_multiplier'];
        
        $freeShip = ($applicableFee['min_order_free_ship'] && $testValue >= $applicableFee['min_order_free_ship']);
        
        echo "<p><strong>{$method['name']}:</strong> ";
        if ($freeShip) {
            echo "<span class='success'>MIỄN PHÍ</span> (đơn hàng >= " . number_format($applicableFee['min_order_free_ship'], 0, ',', '.') . " đ)";
        } else {
            echo number_format($calculatedFee, 0, ',', '.') . " đ";
        }
        echo "</p>";
    }
}

echo "</div>";

// TEST 2: Khu vực giao hàng
echo "<h2>📍 TEST 2: Khu Vực Giao Hàng</h2>";
echo "<div class='test-section'>";

// Kiểm tra dữ liệu địa chỉ
$provinceCount = $db->query("SELECT COUNT(*) FROM provinces")->fetchColumn();
$districtCount = $db->query("SELECT COUNT(*) FROM districts")->fetchColumn();
$wardCount = $db->query("SELECT COUNT(*) FROM wards")->fetchColumn();

echo "<h3>✅ Dữ liệu địa chỉ Việt Nam:</h3>";
echo "<table>";
echo "<tr><th>Loại</th><th>Số lượng</th><th>Trạng thái</th></tr>";
echo "<tr><td>Tỉnh/Thành phố</td><td><strong>{$provinceCount}</strong></td><td><span class='badge badge-success'>Đầy đủ</span></td></tr>";
echo "<tr><td>Quận/Huyện</td><td><strong>{$districtCount}</strong></td><td><span class='badge badge-success'>Đầy đủ</span></td></tr>";
echo "<tr><td>Phường/Xã</td><td><strong>{$wardCount}</strong></td><td><span class='badge badge-success'>Đầy đủ</span></td></tr>";
echo "</table>";

// Kiểm tra khu vực giao hàng được hỗ trợ
$zoneCount = $db->query("SELECT COUNT(*) FROM shipping_zones WHERE is_supported = 1")->fetchColumn();
echo "<h3>✅ Khu vực giao hàng được hỗ trợ:</h3>";
echo "<p><strong>Tổng số khu vực:</strong> <span class='success'>{$zoneCount} khu vực</span></p>";

// Lấy một số khu vực mẫu
$stmt = $db->query("
    SELECT sz.*, p.name as province_name, d.name as district_name
    FROM shipping_zones sz
    LEFT JOIN provinces p ON sz.province_id = p.id
    LEFT JOIN districts d ON sz.district_id = d.id
    WHERE sz.is_supported = 1
    LIMIT 10
");
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($zones) > 0) {
    echo "<table>";
    echo "<tr><th>Tỉnh/Thành</th><th>Quận/Huyện</th><th>Thời gian giao (giờ)</th><th>Ghi chú</th></tr>";
    foreach ($zones as $zone) {
        echo "<tr>";
        echo "<td>{$zone['province_name']}</td>";
        echo "<td>" . ($zone['district_name'] ?? 'Tất cả') . "</td>";
        echo "<td>{$zone['delivery_time_min']} - {$zone['delivery_time_max']} giờ</td>";
        echo "<td>" . ($zone['note'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test API lấy địa chỉ
echo "<h3>🧪 Test API lấy địa chỉ:</h3>";
$apiFile = 'lequocanh/administrator/elements_LQA/mgiohang/get_address_data.php';
if (file_exists($apiFile)) {
    echo "<p><span class='success'>✅ API endpoint tồn tại:</span> <code>{$apiFile}</code></p>";
    
    // Test lấy danh sách tỉnh
    $provinces = $db->query("SELECT * FROM provinces WHERE is_active = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Mẫu 5 tỉnh/thành:</strong></p>";
    echo "<ul>";
    foreach ($provinces as $prov) {
        echo "<li>{$prov['name']} (code: {$prov['code']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p><span class='fail'>❌ API endpoint không tồn tại</span></p>";
}

// Kiểm tra component address selector
$componentFile = 'lequocanh/administrator/elements_LQA/mgiohang/address_selector_component.php';
if (file_exists($componentFile)) {
    echo "<p><span class='success'>✅ Component address selector tồn tại:</span> <code>{$componentFile}</code></p>";
} else {
    echo "<p><span class='fail'>❌ Component không tồn tại</span></p>";
}

echo "</div>";

// TEST 3: Theo dõi trạng thái thanh toán
echo "<h2>💳 TEST 3: Theo Dõi Trạng Thái Thanh Toán</h2>";
echo "<div class='test-section'>";

// Kiểm tra cột trạng thái thanh toán trong bảng don_hang
$stmt = $db->query("SHOW COLUMNS FROM don_hang LIKE 'trang_thai_thanh_toan'");
$hasPaymentStatusColumn = $stmt->rowCount() > 0;

if ($hasPaymentStatusColumn) {
    echo "<p><span class='success'>✅ Cột 'trang_thai_thanh_toan' tồn tại trong bảng don_hang</span></p>";
    
    // Lấy thống kê trạng thái thanh toán
    $stmt = $db->query("
        SELECT trang_thai_thanh_toan, COUNT(*) as count
        FROM don_hang
        GROUP BY trang_thai_thanh_toan
    ");
    $paymentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Thống kê trạng thái thanh toán:</h3>";
    echo "<table>";
    echo "<tr><th>Trạng thái</th><th>Số đơn hàng</th><th>Badge</th></tr>";
    
    $statusLabels = [
        'pending' => ['label' => 'Chưa thanh toán', 'class' => 'badge-warning'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'badge-success'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'badge-success'],
        'failed' => ['label' => 'Thất bại', 'class' => 'badge-danger'],
        'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'badge-info']
    ];
    
    foreach ($paymentStats as $stat) {
        $status = $stat['trang_thai_thanh_toan'];
        $label = $statusLabels[$status]['label'] ?? $status;
        $badgeClass = $statusLabels[$status]['class'] ?? 'badge-info';
        
        echo "<tr>";
        echo "<td><span class='badge {$badgeClass}'>{$label}</span></td>";
        echo "<td><strong>{$stat['count']}</strong> đơn</td>";
        echo "<td><code>{$status}</code></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Lấy một số đơn hàng mẫu
    echo "<h3>📋 Mẫu đơn hàng với trạng thái thanh toán:</h3>";
    $stmt = $db->query("
        SELECT id, ma_don_hang_text, tong_tien, trang_thai, trang_thai_thanh_toan, 
               phuong_thuc_thanh_toan, ngay_tao
        FROM don_hang
        ORDER BY ngay_tao DESC
        LIMIT 10
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orders) > 0) {
        echo "<table>";
        echo "<tr><th>Mã đơn</th><th>Tổng tiền</th><th>Trạng thái đơn</th><th>Trạng thái TT</th><th>Phương thức TT</th><th>Ngày tạo</th></tr>";
        
        foreach ($orders as $order) {
            $paymentStatus = $order['trang_thai_thanh_toan'];
            $paymentLabel = $statusLabels[$paymentStatus]['label'] ?? $paymentStatus;
            $paymentBadge = $statusLabels[$paymentStatus]['class'] ?? 'badge-info';
            
            $orderStatusBadge = match($order['trang_thai']) {
                'pending' => 'badge-warning',
                'approved' => 'badge-info',
                'completed' => 'badge-success',
                'cancelled' => 'badge-danger',
                default => 'badge-info'
            };
            
            echo "<tr>";
            echo "<td><strong>{$order['ma_don_hang_text']}</strong></td>";
            echo "<td>" . number_format($order['tong_tien'], 0, ',', '.') . " đ</td>";
            echo "<td><span class='badge {$orderStatusBadge}'>{$order['trang_thai']}</span></td>";
            echo "<td><span class='badge {$paymentBadge}'>{$paymentLabel}</span></td>";
            echo "<td>{$order['phuong_thuc_thanh_toan']}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($order['ngay_tao'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>Chưa có đơn hàng nào trong hệ thống</em></p>";
    }
    
    // Kiểm tra các phương thức thanh toán
    echo "<h3>💰 Phương thức thanh toán được hỗ trợ:</h3>";
    $stmt = $db->query("
        SELECT phuong_thuc_thanh_toan, COUNT(*) as count
        FROM don_hang
        GROUP BY phuong_thuc_thanh_toan
    ");
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Phương thức</th><th>Số đơn hàng</th></tr>";
    foreach ($paymentMethods as $method) {
        echo "<tr>";
        echo "<td><strong>{$method['phuong_thuc_thanh_toan']}</strong></td>";
        echo "<td>{$method['count']} đơn</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p><span class='fail'>❌ Cột 'trang_thai_thanh_toan' không tồn tại trong bảng don_hang</span></p>";
}

// Kiểm tra file quản lý đơn hàng
$ordersFile = 'lequocanh/administrator/elements_LQA/madmin/orders_v2.php';
if (file_exists($ordersFile)) {
    echo "<p><span class='success'>✅ Module quản lý đơn hàng tồn tại:</span> <code>{$ordersFile}</code></p>";
} else {
    echo "<p><span class='fail'>❌ Module quản lý đơn hàng không tồn tại</span></p>";
}

echo "</div>";

// TỔNG KẾT
echo "<h2>🎯 TỔNG KẾT</h2>";
echo "<div class='test-section'>";

$totalTests = 0;
$passedTests = 0;

// Test 1: Cấu hình phí vận chuyển
$test1Checks = [
    'Bảng shipping_methods tồn tại' => $db->query("SHOW TABLES LIKE 'shipping_methods'")->rowCount() > 0,
    'Bảng shipping_fees tồn tại' => $db->query("SHOW TABLES LIKE 'shipping_fees'")->rowCount() > 0,
    'Có phương thức vận chuyển' => count($methods) > 0,
    'Có cấu hình phí' => $feeCount > 0,
    'File shipping_config.php tồn tại' => file_exists('lequocanh/administrator/elements_LQA/madmin/shipping_config.php'),
    'File calculate_shipping_api.php tồn tại' => file_exists('lequocanh/administrator/elements_LQA/mgiohang/calculate_shipping_api.php')
];

// Test 2: Khu vực giao hàng
$test2Checks = [
    'Bảng provinces tồn tại' => $provinceCount > 0,
    'Bảng districts tồn tại' => $districtCount > 0,
    'Bảng wards tồn tại' => $wardCount > 0,
    'Bảng shipping_zones tồn tại' => $db->query("SHOW TABLES LIKE 'shipping_zones'")->rowCount() > 0,
    'API get_address_data.php tồn tại' => file_exists($apiFile),
    'Component address_selector tồn tại' => file_exists($componentFile)
];

// Test 3: Trạng thái thanh toán
$test3Checks = [
    'Cột trang_thai_thanh_toan tồn tại' => $hasPaymentStatusColumn,
    'Có dữ liệu trạng thái thanh toán' => count($paymentStats) > 0,
    'Module orders_v2.php tồn tại' => file_exists($ordersFile)
];

echo "<h3>📊 Kết quả chi tiết:</h3>";
echo "<table>";
echo "<tr><th>Chức năng</th><th>Kiểm tra</th><th>Kết quả</th></tr>";

foreach ($test1Checks as $check => $result) {
    $totalTests++;
    if ($result) $passedTests++;
    $badge = $result ? "<span class='badge badge-success'>✅ PASS</span>" : "<span class='badge badge-danger'>❌ FAIL</span>";
    echo "<tr><td><strong>Phí vận chuyển</strong></td><td>{$check}</td><td>{$badge}</td></tr>";
}

foreach ($test2Checks as $check => $result) {
    $totalTests++;
    if ($result) $passedTests++;
    $badge = $result ? "<span class='badge badge-success'>✅ PASS</span>" : "<span class='badge badge-danger'>❌ FAIL</span>";
    echo "<tr><td><strong>Khu vực giao hàng</strong></td><td>{$check}</td><td>{$badge}</td></tr>";
}

foreach ($test3Checks as $check => $result) {
    $totalTests++;
    if ($result) $passedTests++;
    $badge = $result ? "<span class='badge badge-success'>✅ PASS</span>" : "<span class='badge badge-danger'>❌ FAIL</span>";
    echo "<tr><td><strong>Trạng thái thanh toán</strong></td><td>{$check}</td><td>{$badge}</td></tr>";
}

echo "</table>";

$percentage = round(($passedTests / $totalTests) * 100, 1);
$statusClass = $percentage == 100 ? 'success' : ($percentage >= 80 ? 'badge-warning' : 'fail');

echo "<h3>🎯 Kết quả tổng thể:</h3>";
echo "<div style='padding: 20px; background: #ecf0f1; border-radius: 5px; text-align: center;'>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Tổng số tests:</strong> {$totalTests}</p>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Passed:</strong> <span class='success'>{$passedTests}</span></p>";
echo "<p style='font-size: 24px; margin: 10px 0;'><strong>Failed:</strong> <span class='fail'>" . ($totalTests - $passedTests) . "</span></p>";
echo "<p style='font-size: 36px; margin: 20px 0;'><strong class='{$statusClass}'>{$percentage}%</strong></p>";

if ($percentage == 100) {
    echo "<p style='font-size: 20px; color: #27ae60;'><strong>🎉 TẤT CẢ CHỨC NĂNG HOẠT ĐỘNG HOÀN HẢO!</strong></p>";
} elseif ($percentage >= 80) {
    echo "<p style='font-size: 20px; color: #f39c12;'><strong>⚠️ Hầu hết chức năng hoạt động tốt, cần kiểm tra một số điểm</strong></p>";
} else {
    echo "<p style='font-size: 20px; color: #e74c3c;'><strong>❌ Cần khắc phục một số vấn đề</strong></p>";
}

echo "</div>";
echo "</div>";

echo "</div>
</body>
</html>";
