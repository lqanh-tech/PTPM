<?php
/**
 * Test các sửa lỗi thông báo
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';

SessionManager::start();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notification Fixes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .test-container { max-width: 1000px; margin: 0 auto; }
        .test-section { background: white; padding: 30px; margin-bottom: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .test-item { padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; background: #f8f9fa; border-radius: 8px; }
        .test-item.success { border-color: #28a745; background: #d4edda; }
        .test-item.error { border-color: #dc3545; background: #f8d7da; }
        .test-item.warning { border-color: #ffc107; background: #fff3cd; }
    </style>
</head>
<body>
<div class="test-container">
    <h2 class="mb-4"><i class="fas fa-bug-slash text-success"></i> Test Sửa Lỗi Thông Báo</h2>
    
    <?php
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Test 1: Kiểm tra file notification widget
        echo "<div class='test-section'>
            <h4><i class='fas fa-bell'></i> Test 1: Kiểm tra File Notification Widget</h4>";
        
        $widgetFile = 'lequocanh/administrator/elements_LQA/mthongbao/customer_notification_widget.php';
        if (file_exists($widgetFile)) {
            $content = file_get_contents($widgetFile);
            
            // Kiểm tra các sửa đổi
            $checks = [
                'Link hóa đơn đúng' => strpos($content, '/lequocanh/customer/order_invoice.php') !== false,
                'Link lịch sử đơn hàng' => strpos($content, '/lequocanh/customer/order_history.php') !== false,
                'Không có onclick viewNotificationDetail' => strpos($content, 'onclick="viewNotificationDetail') === false,
                'Có onclick markAsRead' => strpos($content, 'onclick="markAsRead') !== false,
                'URL tuyệt đối cho API' => strpos($content, '/lequocanh/administrator/elements_LQA/mthongbao/') !== false
            ];
            
            foreach ($checks as $name => $result) {
                $class = $result ? 'success' : 'error';
                $icon = $result ? 'check-circle' : 'times-circle';
                echo "<div class='test-item $class'>
                    <i class='fas fa-$icon'></i> <strong>$name:</strong> " . ($result ? 'OK' : 'FAILED') . "
                </div>";
            }
        } else {
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> File không tồn tại
            </div>";
        }
        
        echo "</div>";
        
        // Test 2: Kiểm tra file order_history.php
        echo "<div class='test-section'>
            <h4><i class='fas fa-history'></i> Test 2: Kiểm tra File Order History</h4>";
        
        $historyFile = 'lequocanh/customer/order_history.php';
        if (file_exists($historyFile)) {
            $content = file_get_contents($historyFile);
            
            $checks = [
                'Kiểm tra USER session' => strpos($content, "if (!isset(\$_SESSION['USER']))") !== false,
                'Không có ADMIN check' => strpos($content, "isset(\$_SESSION['ADMIN'])") === false,
                'Chỉ lấy đơn của user' => strpos($content, 'WHERE dh.ma_nguoi_dung = ?') !== false,
                'Redirect về index nếu chưa login' => strpos($content, "header('Location: ../index.php')") !== false
            ];
            
            foreach ($checks as $name => $result) {
                $class = $result ? 'success' : 'error';
                $icon = $result ? 'check-circle' : 'times-circle';
                echo "<div class='test-item $class'>
                    <i class='fas fa-$icon'></i> <strong>$name:</strong> " . ($result ? 'OK' : 'FAILED') . "
                </div>";
            }
        } else {
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> File không tồn tại
            </div>";
        }
        
        echo "</div>";
        
        // Test 3: Kiểm tra file order_invoice.php
        echo "<div class='test-section'>
            <h4><i class='fas fa-file-invoice'></i> Test 3: Kiểm tra File Order Invoice</h4>";
        
        $invoiceFile = 'lequocanh/customer/order_invoice.php';
        if (file_exists($invoiceFile)) {
            $content = file_get_contents($invoiceFile);
            
            $checks = [
                'Kiểm tra USER session' => strpos($content, "if (!isset(\$_SESSION['USER']))") !== false,
                'Kiểm tra owner của đơn hàng' => strpos($content, 'ma_nguoi_dung = ?') !== false,
                'Có widget đánh giá' => strpos($content, 'product_review_widget.php') !== false,
                'Chỉ hiển thị widget khi approved' => strpos($content, 'if ($isApproved)') !== false
            ];
            
            foreach ($checks as $name => $result) {
                $class = $result ? 'success' : 'error';
                $icon = $result ? 'check-circle' : 'times-circle';
                echo "<div class='test-item $class'>
                    <i class='fas fa-$icon'></i> <strong>$name:</strong> " . ($result ? 'OK' : 'FAILED') . "
                </div>";
            }
        } else {
            echo "<div class='test-item error'>
                <i class='fas fa-times-circle'></i> File không tồn tại
            </div>";
        }
        
        echo "</div>";
        
        // Test 4: Kiểm tra thông báo trong database
        echo "<div class='test-section'>
            <h4><i class='fas fa-database'></i> Test 4: Kiểm tra Thông Báo Trong Database</h4>";
        
        $sql = "SELECT 
                    cn.*,
                    u.ten as user_name,
                    dh.ma_don_hang_text,
                    dh.trang_thai,
                    dh.trang_thai_thanh_toan
                FROM customer_notifications cn
                LEFT JOIN user u ON cn.user_id = u.username
                LEFT JOIN don_hang dh ON cn.order_id = dh.id
                WHERE cn.type = 'order_approved'
                ORDER BY cn.created_at DESC
                LIMIT 5";
        
        $stmt = $conn->query($sql);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifications) > 0) {
            echo "<p class='text-muted'>Tìm thấy " . count($notifications) . " thông báo 'order_approved':</p>";
            
            foreach ($notifications as $notif) {
                $hasLink = strpos($notif['message'], 'order_invoice.php') !== false;
                $class = $hasLink ? 'success' : 'warning';
                
                echo "<div class='test-item $class'>
                    <strong>User:</strong> {$notif['user_name']} ({$notif['user_id']})<br>
                    <strong>Đơn hàng:</strong> {$notif['ma_don_hang_text']} (ID: {$notif['order_id']})<br>
                    <strong>Trạng thái:</strong> {$notif['trang_thai']} / {$notif['trang_thai_thanh_toan']}<br>
                    <strong>Có link hóa đơn:</strong> " . ($hasLink ? 'Có' : 'Không') . "<br>
                    <strong>Đã đọc:</strong> " . ($notif['is_read'] ? 'Rồi' : 'Chưa') . "
                </div>";
            }
        } else {
            echo "<div class='test-item warning'>
                <i class='fas fa-info-circle'></i> Chưa có thông báo 'order_approved' nào
            </div>";
        }
        
        echo "</div>";
        
        // Test 5: Hướng dẫn test thủ công
        echo "<div class='test-section'>
            <h4><i class='fas fa-clipboard-check'></i> Test 5: Hướng Dẫn Test Thủ Công</h4>
            
            <div class='alert alert-info'>
                <h5><i class='fas fa-info-circle'></i> Các bước test:</h5>
                <ol>
                    <li><strong>Test sau khi đánh dấu tất cả đã đọc:</strong>
                        <ul>
                            <li>Đăng nhập với tài khoản khách hàng</li>
                            <li>Click icon chuông → Click 'Đánh dấu tất cả đã đọc'</li>
                            <li>Thử click vào nút 'Xem hóa đơn & Đánh giá' hoặc 'Xem chi tiết đơn hàng'</li>
                            <li>✅ Phải chuyển đến trang tương ứng</li>
                        </ul>
                    </li>
                    
                    <li><strong>Test widget đánh giá:</strong>
                        <ul>
                            <li>Tạo đơn hàng với Bank Transfer hoặc COD</li>
                            <li>Admin duyệt đơn hàng</li>
                            <li>Khách hàng nhận thông báo → Click 'Xem hóa đơn & Đánh giá'</li>
                            <li>✅ Phải thấy widget đánh giá ở cuối trang</li>
                        </ul>
                    </li>
                    
                    <li><strong>Test lịch sử đơn hàng:</strong>
                        <ul>
                            <li>Đăng nhập với tài khoản khách hàng (KHÔNG phải admin)</li>
                            <li>Click icon chuông → Click 'Xem lịch sử đơn hàng'</li>
                            <li>✅ Phải chuyển đến /lequocanh/customer/order_history.php</li>
                            <li>✅ Chỉ thấy đơn hàng của mình</li>
                            <li>❌ KHÔNG được thấy trang quản lý admin</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>";
        
        // Tổng kết
        echo "<div class='alert alert-success'>
            <h5><i class='fas fa-check-circle'></i> Tổng kết các sửa đổi:</h5>
            <ul>
                <li>✅ Sửa lỗi không click được sau khi đánh dấu đã đọc</li>
                <li>✅ Thay đổi link từ relative sang absolute</li>
                <li>✅ Bỏ onclick viewNotificationDetail, dùng link trực tiếp</li>
                <li>✅ Thêm onclick markAsRead vào link (không block navigation)</li>
                <li>✅ Đổi 'Xem tất cả thông báo' → 'Xem lịch sử đơn hàng'</li>
                <li>✅ Link đến /lequocanh/customer/order_history.php (không phải admin)</li>
            </ul>
        </div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>
            <h5><i class='fas fa-exclamation-triangle'></i> Lỗi</h5>
            <p>{$e->getMessage()}</p>
        </div>";
    }
    ?>
    
    <div class="mt-4 text-center">
        <a href="lequocanh/index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
        <a href="lequocanh/customer/order_history.php" class="btn btn-success">
            <i class="fas fa-history"></i> Xem lịch sử đơn hàng
        </a>
    </div>
</div>

</body>
</html>
