<?php
/**
 * Script sửa nhanh coupon NEWUSER20
 * Ghi nhận sử dụng cho đơn hàng đã dùng mã này
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>🔧 Sửa Coupon NEWUSER20</h2>";

// Tìm đơn hàng đã dùng NEWUSER20
$orderSql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, coupon_code, coupon_discount, ngay_tao 
             FROM don_hang 
             WHERE UPPER(coupon_code) = 'NEWUSER20'";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->execute();
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Tìm thấy " . count($orders) . " đơn hàng dùng mã NEWUSER20</p>";

if (count($orders) == 0) {
    echo "<p style='color:orange'>Không tìm thấy đơn hàng nào dùng mã NEWUSER20</p>";
    exit;
}

// Lấy coupon ID
$couponSql = "SELECT id, code, usage_count FROM coupons WHERE UPPER(code) = 'NEWUSER20'";
$couponStmt = $conn->prepare($couponSql);
$couponStmt->execute();
$coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo "<p style='color:red'>Không tìm thấy coupon NEWUSER20 trong database!</p>";
    exit;
}

echo "<p>Coupon ID: {$coupon['id']}, Usage count hiện tại: {$coupon['usage_count']}</p>";

// Kiểm tra bảng coupon_usage
$checkTable = $conn->query("SHOW TABLES LIKE 'coupon_usage'");
if ($checkTable->rowCount() == 0) {
    echo "<p>Đang tạo bảng coupon_usage...</p>";
    $createSql = "CREATE TABLE IF NOT EXISTS coupon_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id INT NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        order_id INT NOT NULL,
        discount_amount DECIMAL(15,2) NOT NULL,
        used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_coupon (coupon_id),
        INDEX idx_user (user_id),
        INDEX idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($createSql);
}

$fixedCount = 0;

foreach ($orders as $order) {
    // Kiểm tra đã ghi nhận chưa
    $checkUsageSql = "SELECT id FROM coupon_usage WHERE order_id = ?";
    $checkStmt = $conn->prepare($checkUsageSql);
    $checkStmt->execute([$order['id']]);
    
    if ($checkStmt->rowCount() > 0) {
        echo "<p>Đơn hàng #{$order['id']} đã được ghi nhận trước đó</p>";
        continue;
    }
    
    // Thêm vào coupon_usage
    $insertSql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at) 
                  VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        $coupon['id'],
        $order['ma_nguoi_dung'],
        $order['id'],
        $order['coupon_discount'],
        $order['ngay_tao']
    ]);
    
    // Tăng usage_count
    $updateSql = "UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$coupon['id']]);
    
    $fixedCount++;
    echo "<p style='color:green'>✓ Đã ghi nhận đơn hàng #{$order['id']} ({$order['ma_don_hang_text']})</p>";
}

// Kiểm tra lại usage_count
$checkSql = "SELECT usage_count FROM coupons WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->execute([$coupon['id']]);
$newCount = $checkStmt->fetchColumn();

echo "<h3>Kết quả:</h3>";
echo "<p>Đã sửa: $fixedCount đơn hàng</p>";
echo "<p>Usage count mới của NEWUSER20: <strong>$newCount</strong></p>";
echo "<p><a href='lequocanh/administrator/index.php?req=coupon'>← Quay lại quản lý Coupon</a></p>";
