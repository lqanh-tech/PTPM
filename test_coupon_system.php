<?php
/**
 * Test hệ thống Coupon
 */

require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/CouponCls.php';

echo "<h1>🎫 Test Hệ Thống Mã Giảm Giá (Coupon)</h1>";

$couponManager = new Coupon();

// Test 1: Lấy danh sách coupon
echo "<h2>1. Danh sách mã giảm giá</h2>";
$coupons = $couponManager->getAllCoupons(true);

if (empty($coupons)) {
    echo "<p style='color:orange'>⚠️ Chưa có mã giảm giá. Đang tạo dữ liệu mẫu...</p>";
    
    // Tạo dữ liệu mẫu
    $sampleCoupons = [
        ['code' => 'SALE10', 'name' => 'Giảm 10%', 'description' => 'Giảm 10% tối đa 100k', 'discount_type' => 'percent', 'discount_value' => 10, 'max_discount' => 100000, 'min_order_value' => 200000],
        ['code' => 'GIAM50K', 'name' => 'Giảm 50.000đ', 'description' => 'Giảm 50k cho đơn từ 500k', 'discount_type' => 'fixed', 'discount_value' => 50000, 'min_order_value' => 500000],
    ];
    
    foreach ($sampleCoupons as $data) {
        try {
            $couponManager->createCoupon($data);
            echo "<p style='color:green'>✅ Đã tạo mã: {$data['code']}</p>";
        } catch (Exception $e) {
            echo "<p style='color:blue'>ℹ️ {$data['code']}: " . $e->getMessage() . "</p>";
        }
    }
    
    $coupons = $couponManager->getAllCoupons(true);
}

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background:#28a745;color:white;'><th>Mã</th><th>Tên</th><th>Loại</th><th>Giá trị</th><th>Đơn tối thiểu</th><th>Trạng thái</th></tr>";
foreach ($coupons as $c) {
    $status = $c->is_active ? '✅ Hoạt động' : '❌ Tắt';
    $value = $c->discount_type == 'percent' ? $c->discount_value . '%' : number_format($c->discount_value) . 'đ';
    echo "<tr>
        <td><strong>{$c->code}</strong></td>
        <td>{$c->name}</td>
        <td>{$c->discount_type}</td>
        <td>{$value}</td>
        <td>" . number_format($c->min_order_value) . "đ</td>
        <td>{$status}</td>
    </tr>";
}
echo "</table>";

// Test 2: Validate coupon
echo "<h2>2. Test Validate Coupon</h2>";

$testCases = [
    ['code' => 'SALE10', 'total' => 500000, 'expected' => true],
    ['code' => 'SALE10', 'total' => 100000, 'expected' => false], // Dưới min
    ['code' => 'GIAM50K', 'total' => 600000, 'expected' => true],
    ['code' => 'INVALID', 'total' => 500000, 'expected' => false], // Mã không tồn tại
];

foreach ($testCases as $test) {
    $result = $couponManager->validateCoupon($test['code'], $test['total'], 'testuser');
    $icon = $result['valid'] == $test['expected'] ? '✅' : '❌';
    $status = $result['valid'] ? 'Hợp lệ' : 'Không hợp lệ';
    
    echo "<p>{$icon} Mã <strong>{$test['code']}</strong> với đơn " . number_format($test['total']) . "đ: 
          <span style='color:" . ($result['valid'] ? 'green' : 'red') . "'>{$status}</span> - {$result['message']}";
    
    if ($result['valid']) {
        echo " | Giảm: <strong>" . number_format($result['discount']) . "đ</strong>";
    }
    echo "</p>";
}

// Test 3: Tính toán giảm giá
echo "<h2>3. Test Tính Toán Giảm Giá</h2>";

$coupon = $couponManager->getCouponByCode('SALE10');
if ($coupon) {
    $testAmounts = [200000, 500000, 1000000, 2000000];
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background:#007bff;color:white;'><th>Đơn hàng</th><th>Giảm</th><th>Còn lại</th></tr>";
    
    foreach ($testAmounts as $amount) {
        $discount = $couponManager->calculateDiscount($coupon, $amount);
        $final = $amount - $discount;
        echo "<tr>
            <td>" . number_format($amount) . "đ</td>
            <td style='color:green'>-" . number_format($discount) . "đ</td>
            <td style='color:red;font-weight:bold'>" . number_format($final) . "đ</td>
        </tr>";
    }
    echo "</table>";
}

// Test 4: Thống kê
echo "<h2>4. Thống kê Coupon</h2>";
$stats = $couponManager->getCouponStats();

echo "<ul>";
echo "<li>Tổng số mã: <strong>{$stats['total']}</strong></li>";
echo "<li>Đang hoạt động: <strong>{$stats['active']}</strong></li>";
echo "<li>Tổng lượt sử dụng: <strong>" . number_format($stats['total_usage']) . "</strong></li>";
echo "<li>Tổng tiền đã giảm: <strong>" . number_format($stats['total_discount']) . "đ</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<h3 style='color:green'>🎉 Test hoàn tất!</h3>";
echo "<p>Các link quan trọng:</p>";
echo "<ul>";
echo "<li><a href='lequocanh/database/setup_coupon_system.php'>Setup Database Coupon</a></li>";
echo "<li><a href='lequocanh/administrator/index.php?req=coupon'>Quản lý Coupon (Admin)</a></li>";
echo "</ul>";
?>
