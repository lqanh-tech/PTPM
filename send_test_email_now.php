<?php
/**
 * Send Test Email Now
 * Gửi email test ngay lập tức
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/EmailService.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Send Test Email</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='mb-4 text-center'>📧 Gửi Email Test</h1>
";

try {
    $db = Database::getInstance()->getConnection();
    
    // Email người nhận
    $testEmail = 'quocanh5758@gmail.com';
    
    echo "<div class='alert alert-info'>";
    echo "<h5>📬 Đang gửi email test đến: <strong>$testEmail</strong></h5>";
    echo "</div>";
    
    // Tìm user với email này
    $sql = "SELECT * FROM user WHERE email = ? OR username LIKE '%quocanh%' LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='alert alert-warning'>";
        echo "<p>⚠️ Không tìm thấy user với email này trong database.</p>";
        echo "<p>Đang tạo user test...</p>";
        echo "</div>";
        
        // Tạo user test
        $testUsername = 'testuser_' . time();
        $insertSql = "INSERT INTO user (username, password, hoten, email, gioitinh, ngaysinh, diachi, dienthoai, setlock) 
                      VALUES (?, ?, ?, ?, 1, '1990-01-01', 'Test Address', '0123456789', 1)";
        $stmt = $db->prepare($insertSql);
        $stmt->execute([$testUsername, password_hash('test123', PASSWORD_DEFAULT), 'Test User', $testEmail]);
        
        $userId = $db->lastInsertId();
        
        // Lấy lại user
        $stmt = $db->prepare("SELECT * FROM user WHERE iduser = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='alert alert-success'>";
        echo "<p>✅ Đã tạo user test: <strong>" . $user['username'] . "</strong></p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "<p>✅ Tìm thấy user: <strong>" . htmlspecialchars($user['hoten']) . "</strong> (Username: " . htmlspecialchars($user['username']) . ")</p>";
        echo "</div>";
    }
    
    // Tìm hoặc tạo đơn hàng test
    $sql = "SELECT * FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user['username']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "<div class='alert alert-warning'>";
        echo "<p>⚠️ Không tìm thấy đơn hàng. Đang tạo đơn hàng test...</p>";
        echo "</div>";
        
        // Tạo đơn hàng test
        $orderCode = 'TEST' . time();
        $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan, ngay_tao) 
                          VALUES (?, ?, 500000, 'pending', 'pending', 'cod', NOW())";
        $stmt = $db->prepare($insertOrderSql);
        $stmt->execute([$orderCode, $user['username']]);
        
        $orderId = $db->lastInsertId();
        
        // Thêm chi tiết đơn hàng test
        $insertDetailSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_hang_hoa, so_luong, don_gia, thanh_tien) 
                           VALUES (?, 1, 2, 250000, 500000)";
        $stmt = $db->prepare($insertDetailSql);
        $stmt->execute([$orderId]);
        
        // Lấy lại đơn hàng
        $stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='alert alert-success'>";
        echo "<p>✅ Đã tạo đơn hàng test: <strong>#" . $order['ma_don_hang_text'] . "</strong></p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "<p>✅ Tìm thấy đơn hàng: <strong>#" . htmlspecialchars($order['ma_don_hang_text']) . "</strong></p>";
        echo "<p>Tổng tiền: <strong>" . number_format($order['tong_tien'], 0, ',', '.') . " đ</strong></p>";
        echo "</div>";
    }
    
    // Khởi tạo EmailService
    $emailService = new EmailService();
    
    echo "<hr>";
    echo "<h4 class='mt-4 mb-3'>📨 Đang gửi các loại email...</h4>";
    
    $results = [];
    
    // 1. Email đặt hàng thành công
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>1. Email Đặt Hàng Thành Công</h5>";
    echo "<p class='text-muted'>Gửi email thông báo đơn hàng đã được đặt thành công...</p>";
    
    $result1 = $emailService->sendOrderSuccessEmail($order['id'], $testEmail);
    
    if ($result1) {
        echo "<div class='alert alert-success mb-0'>";
        echo "<strong>✅ Gửi thành công!</strong><br>";
        echo "Subject: ✅ Đơn hàng #{$order['ma_don_hang_text']} đã được đặt thành công";
        echo "</div>";
        $results['order_success'] = true;
    } else {
        echo "<div class='alert alert-danger mb-0'>";
        echo "<strong>❌ Gửi thất bại!</strong><br>";
        echo "Vui lòng kiểm tra cấu hình SMTP và error log.";
        echo "</div>";
        $results['order_success'] = false;
    }
    echo "</div></div>";
    
    sleep(2); // Delay 2 giây giữa các email
    
    // 2. Email thanh toán thành công
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>2. Email Thanh Toán Thành Công</h5>";
    echo "<p class='text-muted'>Gửi email xác nhận thanh toán...</p>";
    
    $result2 = $emailService->sendPaymentConfirmedEmail($order['id'], $testEmail);
    
    if ($result2) {
        echo "<div class='alert alert-success mb-0'>";
        echo "<strong>✅ Gửi thành công!</strong><br>";
        echo "Subject: 💰 Thanh toán đơn hàng #{$order['ma_don_hang_text']} thành công";
        echo "</div>";
        $results['payment_confirmed'] = true;
    } else {
        echo "<div class='alert alert-danger mb-0'>";
        echo "<strong>❌ Gửi thất bại!</strong>";
        echo "</div>";
        $results['payment_confirmed'] = false;
    }
    echo "</div></div>";
    
    sleep(2);
    
    // 3. Email đơn hàng được duyệt
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>3. Email Đơn Hàng Được Duyệt</h5>";
    echo "<p class='text-muted'>Gửi email thông báo đơn hàng đã được duyệt...</p>";
    
    $result3 = $emailService->sendOrderApprovedEmail($order['id'], $testEmail);
    
    if ($result3) {
        echo "<div class='alert alert-success mb-0'>";
        echo "<strong>✅ Gửi thành công!</strong><br>";
        echo "Subject: ✅ Đơn hàng #{$order['ma_don_hang_text']} đã được duyệt";
        echo "</div>";
        $results['order_approved'] = true;
    } else {
        echo "<div class='alert alert-danger mb-0'>";
        echo "<strong>❌ Gửi thất bại!</strong>";
        echo "</div>";
        $results['order_approved'] = false;
    }
    echo "</div></div>";
    
    sleep(2);
    
    // 4. Email đơn hàng bị hủy
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>4. Email Đơn Hàng Bị Hủy</h5>";
    echo "<p class='text-muted'>Gửi email thông báo đơn hàng bị hủy...</p>";
    
    $result4 = $emailService->sendOrderCancelledEmail($order['id'], $testEmail, 'Đây là email test - đơn hàng không thực sự bị hủy');
    
    if ($result4) {
        echo "<div class='alert alert-success mb-0'>";
        echo "<strong>✅ Gửi thành công!</strong><br>";
        echo "Subject: ❌ Đơn hàng #{$order['ma_don_hang_text']} đã bị hủy";
        echo "</div>";
        $results['order_cancelled'] = true;
    } else {
        echo "<div class='alert alert-danger mb-0'>";
        echo "<strong>❌ Gửi thất bại!</strong>";
        echo "</div>";
        $results['order_cancelled'] = false;
    }
    echo "</div></div>";
    
    // Tổng kết
    echo "<hr>";
    echo "<div class='alert alert-info'>";
    echo "<h4 class='mb-3'>📊 Tổng Kết</h4>";
    
    $totalSent = array_sum($results);
    $totalEmails = count($results);
    
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Tổng số email:</strong> $totalEmails</p>";
    echo "<p><strong>Gửi thành công:</strong> <span class='text-success'>$totalSent</span></p>";
    echo "<p><strong>Gửi thất bại:</strong> <span class='text-danger'>" . ($totalEmails - $totalSent) . "</span></p>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Email người nhận:</strong> $testEmail</p>";
    echo "<p><strong>Đơn hàng test:</strong> #{$order['ma_don_hang_text']}</p>";
    echo "</div>";
    echo "</div>";
    
    if ($totalSent == $totalEmails) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<h5>🎉 Tất cả email đã được gửi thành công!</h5>";
        echo "<p class='mb-0'>Vui lòng kiểm tra hộp thư <strong>$testEmail</strong> để xem các email.</p>";
        echo "<p class='mb-0 mt-2'><small>Lưu ý: Email có thể vào thư mục Spam. Hãy kiểm tra cả thư mục Spam.</small></p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning mt-3'>";
        echo "<h5>⚠️ Một số email gửi thất bại</h5>";
        echo "<p class='mb-0'>Vui lòng kiểm tra:</p>";
        echo "<ul class='mb-0'>";
        echo "<li>Cấu hình SMTP trong file .env</li>";
        echo "<li>Gmail App Password đúng chưa</li>";
        echo "<li>PHP mail() function hoạt động chưa</li>";
        echo "<li>Error log: <code>error.log</code></li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Chi tiết kết quả
    echo "<div class='mt-4'>";
    echo "<h5>Chi tiết kết quả:</h5>";
    echo "<ul class='list-group'>";
    foreach ($results as $type => $success) {
        $icon = $success ? '✅' : '❌';
        $class = $success ? 'list-group-item-success' : 'list-group-item-danger';
        $status = $success ? 'Thành công' : 'Thất bại';
        echo "<li class='list-group-item $class'>$icon <strong>" . ucfirst(str_replace('_', ' ', $type)) . ":</strong> $status</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Hướng dẫn
    echo "<div class='alert alert-info mt-4'>";
    echo "<h5>📝 Hướng Dẫn Kiểm Tra Email:</h5>";
    echo "<ol>";
    echo "<li>Mở Gmail: <a href='https://mail.google.com' target='_blank'>https://mail.google.com</a></li>";
    echo "<li>Đăng nhập với tài khoản: <strong>$testEmail</strong></li>";
    echo "<li>Kiểm tra hộp thư đến (Inbox)</li>";
    echo "<li>Nếu không thấy, kiểm tra thư mục <strong>Spam</strong></li>";
    echo "<li>Bạn sẽ thấy 4 email với các subject khác nhau</li>";
    echo "</ol>";
    echo "</div>";
    
    // Debug info
    echo "<div class='mt-4'>";
    echo "<details>";
    echo "<summary style='cursor: pointer;'><strong>🔍 Debug Information</strong></summary>";
    echo "<pre class='mt-2'>";
    echo "User Info:\n";
    echo "- Username: " . $user['username'] . "\n";
    echo "- Email: " . $user['email'] . "\n";
    echo "- Họ tên: " . $user['hoten'] . "\n\n";
    
    echo "Order Info:\n";
    echo "- Order ID: " . $order['id'] . "\n";
    echo "- Mã đơn hàng: " . $order['ma_don_hang_text'] . "\n";
    echo "- Tổng tiền: " . number_format($order['tong_tien'], 0, ',', '.') . " đ\n";
    echo "- Trạng thái: " . $order['trang_thai'] . "\n\n";
    
    echo "Email Config:\n";
    $envFile = '.env';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        if (preg_match('/MAIL_FROM_ADDRESS=(.+)/', $envContent, $matches)) {
            echo "- From: " . trim($matches[1]) . "\n";
        }
        if (preg_match('/MAIL_FROM_NAME=(.+)/', $envContent, $matches)) {
            echo "- Name: " . trim($matches[1]) . "\n";
        }
    }
    echo "</pre>";
    echo "</details>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "
    <div class='text-center mt-4'>
        <a href='test_email_notification_system.php' class='btn btn-primary'>Về trang test</a>
        <a href='email_integration_dashboard.html' class='btn btn-secondary'>Dashboard</a>
        <button onclick='location.reload()' class='btn btn-success'>Gửi lại</button>
    </div>
</div>
</body>
</html>";
