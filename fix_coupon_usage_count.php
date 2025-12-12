<?php
/**
 * Script sửa lỗi coupon usage_count
 * Vấn đề: Một số đơn hàng có coupon_code nhưng chưa được ghi nhận vào bảng coupon_usage
 * và chưa tăng usage_count trong bảng coupons
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Sửa lỗi Coupon Usage Count</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:10px;text-align:left;} .error{color:red;} .success{color:green;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h1>🔧 Sửa lỗi Coupon Usage Count</h1>";

// Kiểm tra bảng coupon_usage có tồn tại không
$checkTable = $conn->query("SHOW TABLES LIKE 'coupon_usage'");
if ($checkTable->rowCount() == 0) {
    echo "<p class='warning'>⚠️ Bảng coupon_usage chưa tồn tại. Đang tạo...</p>";
    
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
    echo "<p class='success'>✓ Đã tạo bảng coupon_usage</p>";
}

// Lấy tất cả đơn hàng có coupon_code nhưng chưa được ghi nhận
$sql = "SELECT dh.id, dh.ma_don_hang_text, dh.ma_nguoi_dung, dh.coupon_code, dh.coupon_discount, dh.ngay_tao,
               c.id as coupon_id, c.code as coupon_code_db, c.usage_count
        FROM don_hang dh
        LEFT JOIN coupons c ON UPPER(dh.coupon_code) = UPPER(c.code)
        WHERE dh.coupon_code IS NOT NULL 
        AND dh.coupon_code != ''
        AND dh.coupon_discount > 0
        ORDER BY dh.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>📋 Đơn hàng có sử dụng coupon: " . count($orders) . " đơn</h2>";

if (count($orders) == 0) {
    echo "<p>Không có đơn hàng nào sử dụng coupon.</p>";
    echo "</body></html>";
    exit;
}

// Kiểm tra xem đã được ghi nhận chưa
$missingUsage = [];

echo "<table>";
echo "<tr><th>ID</th><th>Mã đơn</th><th>User</th><th>Coupon</th><th>Giảm giá</th><th>Coupon ID</th><th>Đã ghi nhận?</th></tr>";

foreach ($orders as $order) {
    // Kiểm tra xem đã có trong coupon_usage chưa
    $checkUsageSql = "SELECT id FROM coupon_usage WHERE order_id = ?";
    $checkStmt = $conn->prepare($checkUsageSql);
    $checkStmt->execute([$order['id']]);
    $hasUsage = $checkStmt->rowCount() > 0;
    
    $status = $hasUsage ? '<span class="success">✓ Đã ghi nhận</span>' : '<span class="error">✗ Chưa ghi nhận</span>';
    
    if (!$hasUsage && $order['coupon_id']) {
        $missingUsage[] = $order;
    }
    
    echo "<tr>";
    echo "<td>{$order['id']}</td>";
    echo "<td>{$order['ma_don_hang_text']}</td>";
    echo "<td>{$order['ma_nguoi_dung']}</td>";
    echo "<td>{$order['coupon_code']}</td>";
    echo "<td>" . number_format($order['coupon_discount'], 0, ',', '.') . " ₫</td>";
    echo "<td>" . ($order['coupon_id'] ?: '<span class="error">Không tìm thấy</span>') . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Sửa các đơn hàng chưa được ghi nhận
if (!empty($missingUsage)) {
    echo "<h2>🔧 Cần ghi nhận " . count($missingUsage) . " đơn hàng</h2>";
    
    if (isset($_GET['fix']) && $_GET['fix'] == '1') {
        echo "<div style='background:#e8f5e9;padding:15px;border-radius:5px;margin:10px 0;'>";
        
        $fixedCount = 0;
        foreach ($missingUsage as $order) {
            try {
                // Thêm vào coupon_usage
                $insertSql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at) 
                              VALUES (?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->execute([
                    $order['coupon_id'],
                    $order['ma_nguoi_dung'],
                    $order['id'],
                    $order['coupon_discount'],
                    $order['ngay_tao']
                ]);
                
                // Tăng usage_count trong coupons
                $updateSql = "UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$order['coupon_id']]);
                
                $fixedCount++;
                echo "<p class='success'>✓ Đã ghi nhận coupon cho đơn hàng #{$order['id']} ({$order['coupon_code']})</p>";
                
            } catch (Exception $e) {
                echo "<p class='error'>✗ Lỗi khi ghi nhận đơn hàng #{$order['id']}: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "</div>";
        echo "<p><strong>Đã sửa $fixedCount đơn hàng!</strong></p>";
        
        // Hiển thị usage_count mới
        echo "<h3>📊 Usage count sau khi sửa:</h3>";
        $couponsSql = "SELECT code, name, usage_count, usage_limit FROM coupons ORDER BY code";
        $couponsStmt = $conn->prepare($couponsSql);
        $couponsStmt->execute();
        $coupons = $couponsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Mã</th><th>Tên</th><th>Đã dùng</th><th>Giới hạn</th></tr>";
        foreach ($coupons as $coupon) {
            echo "<tr>";
            echo "<td>{$coupon['code']}</td>";
            echo "<td>{$coupon['name']}</td>";
            echo "<td>{$coupon['usage_count']}</td>";
            echo "<td>" . ($coupon['usage_limit'] ?: 'Không giới hạn') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div style='background:#fff3e0;padding:15px;border-radius:5px;margin:10px 0;'>";
        echo "<p class='warning'>⚠️ Có " . count($missingUsage) . " đơn hàng cần ghi nhận coupon.</p>";
        echo "<ul>";
        foreach ($missingUsage as $order) {
            echo "<li>Đơn #{$order['id']}: {$order['coupon_code']} - " . number_format($order['coupon_discount'], 0, ',', '.') . " ₫</li>";
        }
        echo "</ul>";
        echo "<a href='?fix=1' style='display:inline-block;background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-top:10px;'>🔧 Ghi nhận tất cả</a>";
        echo "</div>";
    }
} else {
    echo "<div style='background:#e8f5e9;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<p class='success'>✓ Tất cả đơn hàng đã được ghi nhận coupon đúng!</p>";
    echo "</div>";
}

// Hiển thị thống kê coupon hiện tại
echo "<h2>📊 Thống kê Coupon hiện tại:</h2>";
$couponsSql = "SELECT code, name, usage_count, usage_limit FROM coupons ORDER BY code";
$couponsStmt = $conn->prepare($couponsSql);
$couponsStmt->execute();
$coupons = $couponsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Mã</th><th>Tên</th><th>Đã dùng</th><th>Giới hạn</th></tr>";
foreach ($coupons as $coupon) {
    echo "<tr>";
    echo "<td>{$coupon['code']}</td>";
    echo "<td>{$coupon['name']}</td>";
    echo "<td>{$coupon['usage_count']}</td>";
    echo "<td>" . ($coupon['usage_limit'] ?: 'Không giới hạn') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "</body></html>";
