<?php
/**
 * Test file để kiểm tra chức năng thông báo và chi tiết đơn hàng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Notification Order Detail</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        .test-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .test-info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔔 Test Notification Order Detail</h1>
    <p class='text-muted'>Kiểm tra các chức năng thông báo và chi tiết đơn hàng</p>
    <hr>";

// Test 1: Kiểm tra bảng customer_notifications
echo "<div class='test-section'>
    <h3>📋 Test 1: Kiểm tra bảng customer_notifications</h3>";

try {
    $stmt = $db->query("SHOW TABLES LIKE 'customer_notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='test-pass'>✅ Bảng customer_notifications tồn tại</p>";
        
        // Kiểm tra cấu trúc bảng
        $stmt = $db->query("DESCRIBE customer_notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Cấu trúc bảng:</strong></p>";
        echo "<table class='table table-sm'><thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr></thead><tbody>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</tbody></table>";
        
        // Đếm số thông báo
        $stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM customer_notifications");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='test-info'>📊 Tổng số thông báo: {$counts['total']} | Chưa đọc: {$counts['unread']}</p>";
    } else {
        echo "<p class='test-fail'>❌ Bảng customer_notifications không tồn tại</p>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Kiểm tra bảng don_hang có các trường cần thiết
echo "<div class='test-section'>
    <h3>📋 Test 2: Kiểm tra cấu trúc bảng don_hang</h3>";

try {
    $requiredColumns = ['thue', 'phi_van_chuyen', 'shipping_method', 'shipping_method_name', 'estimated_delivery'];
    $stmt = $db->query("DESCRIBE don_hang");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table class='table table-sm'><thead><tr><th>Trường</th><th>Trạng thái</th></tr></thead><tbody>";
    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $columns);
        $status = $exists ? "<span class='test-pass'>✅ Có</span>" : "<span class='test-fail'>❌ Không có</span>";
        echo "<tr><td>{$col}</td><td>{$status}</td></tr>";
    }
    echo "</tbody></table>";
} catch (Exception $e) {
    echo "<p class='test-fail'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Kiểm tra một đơn hàng mẫu
echo "<div class='test-section'>
    <h3>📋 Test 3: Kiểm tra dữ liệu đơn hàng mẫu</h3>";

try {
    $stmt = $db->query("SELECT * FROM don_hang ORDER BY id DESC LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<p class='test-pass'>✅ Tìm thấy đơn hàng ID: {$order['id']}</p>";
        echo "<pre>" . json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        // Kiểm tra chi tiết đơn hàng
        $stmt = $db->prepare("SELECT oi.*, h.tenhanghoa, h.hinhanh 
                             FROM chi_tiet_don_hang oi
                             JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                             WHERE oi.ma_don_hang = ?");
        $stmt->execute([$order['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Chi tiết sản phẩm ({$stmt->rowCount()} sản phẩm):</strong></p>";
        echo "<table class='table table-sm'><thead><tr><th>ID SP</th><th>Tên</th><th>Hình ảnh ID</th><th>SL</th><th>Giá</th></tr></thead><tbody>";
        foreach ($items as $item) {
            $imageStatus = !empty($item['hinhanh']) && $item['hinhanh'] > 0 
                ? "<span class='test-pass'>✅ {$item['hinhanh']}</span>" 
                : "<span class='test-fail'>❌ Không có</span>";
            echo "<tr>
                <td>{$item['ma_san_pham']}</td>
                <td>{$item['tenhanghoa']}</td>
                <td>{$imageStatus}</td>
                <td>{$item['so_luong']}</td>
                <td>" . number_format($item['gia']) . " đ</td>
            </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p class='test-fail'>❌ Không tìm thấy đơn hàng nào</p>";
    }
} catch (Exception $e) {
    echo "<p class='test-fail'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Test API getOrderDetail.php
echo "<div class='test-section'>
    <h3>📋 Test 4: Test API getOrderDetail.php</h3>";

if (isset($order) && $order) {
    $orderId = $order['id'];
    $apiUrl = "/lequocanh/administrator/elements_LQA/mthongbao/getOrderDetail.php?id={$orderId}";
    echo "<p class='test-info'>📡 API URL: {$apiUrl}</p>";
    echo "<p class='test-info'>⚠️ Để test API, bạn cần đăng nhập với tài khoản khách hàng sở hữu đơn hàng này.</p>";
    echo "<p><a href='{$apiUrl}' target='_blank' class='btn btn-primary'>Test API trực tiếp</a></p>";
}
echo "</div>";

// Test 5: Kiểm tra file notification.js
echo "<div class='test-section'>
    <h3>📋 Test 5: Kiểm tra file notification.js</h3>";

$jsFile = 'lequocanh/public_files/notification.js';
if (file_exists($jsFile)) {
    $content = file_get_contents($jsFile);
    
    $checks = [
        'showOrderDetail' => strpos($content, 'function showOrderDetail') !== false,
        'markNotificationAsRead' => strpos($content, 'function markNotificationAsRead') !== false,
        'markAllNotificationsAsRead' => strpos($content, 'function markAllNotificationsAsRead') !== false,
        'deleteReadNotifications' => strpos($content, 'function deleteReadNotifications') !== false,
        'setupHeaderButtons' => strpos($content, 'function setupHeaderButtons') !== false,
        'displayImage.php' => strpos($content, 'displayImage.php') !== false || strpos($content, 'product_image') !== false,
        'shipping_method_name' => strpos($content, 'shipping_method_name') !== false,
    ];
    
    echo "<table class='table table-sm'><thead><tr><th>Chức năng</th><th>Trạng thái</th></tr></thead><tbody>";
    foreach ($checks as $name => $exists) {
        $status = $exists ? "<span class='test-pass'>✅ Có</span>" : "<span class='test-fail'>❌ Không có</span>";
        echo "<tr><td>{$name}</td><td>{$status}</td></tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p class='test-fail'>❌ File notification.js không tồn tại</p>";
}
echo "</div>";

// Test 6: Kiểm tra CustomerNotificationManager
echo "<div class='test-section'>
    <h3>📋 Test 6: Kiểm tra CustomerNotificationManager</h3>";

$managerFile = 'lequocanh/administrator/elements_LQA/mod/CustomerNotificationManager.php';
if (file_exists($managerFile)) {
    $content = file_get_contents($managerFile);
    
    $checks = [
        'deleteReadNotifications' => strpos($content, 'function deleteReadNotifications') !== false,
        'deleteNotification' => strpos($content, 'function deleteNotification') !== false,
        'markAsRead' => strpos($content, 'function markAsRead') !== false,
        'markAllAsRead' => strpos($content, 'function markAllAsRead') !== false,
    ];
    
    echo "<table class='table table-sm'><thead><tr><th>Method</th><th>Trạng thái</th></tr></thead><tbody>";
    foreach ($checks as $name => $exists) {
        $status = $exists ? "<span class='test-pass'>✅ Có</span>" : "<span class='test-fail'>❌ Không có</span>";
        echo "<tr><td>{$name}</td><td>{$status}</td></tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p class='test-fail'>❌ File CustomerNotificationManager.php không tồn tại</p>";
}
echo "</div>";

echo "<div class='test-section'>
    <h3>📝 Hướng dẫn test thủ công</h3>
    <ol>
        <li>Đăng nhập với tài khoản khách hàng có đơn hàng</li>
        <li>Click vào icon chuông 🔔 để mở dropdown thông báo</li>
        <li>Click vào nút <strong>'Xem chi tiết đơn hàng'</strong> - kiểm tra modal hiển thị đầy đủ thông tin:
            <ul>
                <li>✅ Hình ảnh sản phẩm hiển thị đúng</li>
                <li>✅ Thuế VAT hiển thị</li>
                <li>✅ Phí vận chuyển hiển thị</li>
                <li>✅ Phương thức vận chuyển hiển thị</li>
            </ul>
        </li>
        <li>Click <strong>'Đánh dấu đã đọc'</strong> trên một thông báo</li>
        <li>Click lại vào <strong>'Xem chi tiết đơn hàng'</strong> - phải vẫn hoạt động</li>
        <li>Click <strong>'Đánh dấu tất cả đã đọc'</strong></li>
        <li>Click lại vào <strong>'Xem chi tiết đơn hàng'</strong> - phải vẫn hoạt động</li>
        <li>Click <strong>'Xóa thông báo đã đọc'</strong> - các thông báo đã đọc phải bị xóa</li>
    </ol>
</div>";

echo "</div>
</body>
</html>";
