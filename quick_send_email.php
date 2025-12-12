<?php
/**
 * Quick Send Email - Simple version
 * Chạy: php quick_send_email.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== QUICK SEND EMAIL TEST ===\n\n";

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/EmailService.php';

try {
    $db = Database::getInstance()->getConnection();
    $emailService = new EmailService();
    
    $testEmail = 'quocanh5758@gmail.com';
    
    echo "📧 Email người nhận: $testEmail\n\n";
    
    // Tìm user
    echo "🔍 Đang tìm user...\n";
    $sql = "SELECT * FROM user WHERE email = ? OR username LIKE '%quocanh%' LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "⚠️  Không tìm thấy user. Đang tạo user test...\n";
        $testUsername = 'testuser_' . time();
        $insertSql = "INSERT INTO user (username, password, hoten, email, gioitinh, ngaysinh, diachi, dienthoai, setlock) 
                      VALUES (?, ?, ?, ?, 1, '1990-01-01', 'Test Address', '0123456789', 1)";
        $stmt = $db->prepare($insertSql);
        $stmt->execute([$testUsername, password_hash('test123', PASSWORD_DEFAULT), 'Test User', $testEmail]);
        
        $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$testUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Đã tạo user: {$user['username']}\n\n";
    } else {
        echo "✅ Tìm thấy user: {$user['hoten']} ({$user['username']})\n\n";
    }
    
    // Tìm hoặc tạo đơn hàng
    echo "🔍 Đang tìm đơn hàng...\n";
    $sql = "SELECT * FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user['username']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "⚠️  Không tìm thấy đơn hàng. Đang tạo đơn hàng test...\n";
        $orderCode = 'TEST' . time();
        $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan, ngay_tao) 
                          VALUES (?, ?, 500000, 'pending', 'pending', 'cod', NOW())";
        $stmt = $db->prepare($insertOrderSql);
        $stmt->execute([$orderCode, $user['username']]);
        
        $orderId = $db->lastInsertId();
        
        // Thêm chi tiết
        $insertDetailSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_hang_hoa, so_luong, don_gia, thanh_tien) 
                           VALUES (?, 1, 2, 250000, 500000)";
        $stmt = $db->prepare($insertDetailSql);
        $stmt->execute([$orderId]);
        
        $stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Đã tạo đơn hàng: #{$order['ma_don_hang_text']}\n\n";
    } else {
        echo "✅ Tìm thấy đơn hàng: #{$order['ma_don_hang_text']}\n";
        echo "   Tổng tiền: " . number_format($order['tong_tien'], 0, ',', '.') . " đ\n\n";
    }
    
    echo "=== BẮT ĐẦU GỬI EMAIL ===\n\n";
    
    $results = [];
    
    // 1. Email đặt hàng thành công
    echo "📨 [1/4] Gửi email đặt hàng thành công...\n";
    $result1 = $emailService->sendOrderSuccessEmail($order['id'], $testEmail);
    if ($result1) {
        echo "   ✅ Thành công!\n";
        $results[] = true;
    } else {
        echo "   ❌ Thất bại!\n";
        $results[] = false;
    }
    sleep(2);
    
    // 2. Email thanh toán
    echo "\n📨 [2/4] Gửi email thanh toán thành công...\n";
    $result2 = $emailService->sendPaymentConfirmedEmail($order['id'], $testEmail);
    if ($result2) {
        echo "   ✅ Thành công!\n";
        $results[] = true;
    } else {
        echo "   ❌ Thất bại!\n";
        $results[] = false;
    }
    sleep(2);
    
    // 3. Email đơn hàng được duyệt
    echo "\n📨 [3/4] Gửi email đơn hàng được duyệt...\n";
    $result3 = $emailService->sendOrderApprovedEmail($order['id'], $testEmail);
    if ($result3) {
        echo "   ✅ Thành công!\n";
        $results[] = true;
    } else {
        echo "   ❌ Thất bại!\n";
        $results[] = false;
    }
    sleep(2);
    
    // 4. Email đơn hàng bị hủy
    echo "\n📨 [4/4] Gửi email đơn hàng bị hủy...\n";
    $result4 = $emailService->sendOrderCancelledEmail($order['id'], $testEmail, 'Email test');
    if ($result4) {
        echo "   ✅ Thành công!\n";
        $results[] = true;
    } else {
        echo "   ❌ Thất bại!\n";
        $results[] = false;
    }
    
    // Tổng kết
    echo "\n=== TỔNG KẾT ===\n\n";
    $totalSent = array_sum($results);
    $totalEmails = count($results);
    
    echo "📊 Kết quả:\n";
    echo "   - Tổng số email: $totalEmails\n";
    echo "   - Gửi thành công: $totalSent\n";
    echo "   - Gửi thất bại: " . ($totalEmails - $totalSent) . "\n\n";
    
    if ($totalSent == $totalEmails) {
        echo "🎉 TẤT CẢ EMAIL ĐÃ ĐƯỢC GỬI THÀNH CÔNG!\n\n";
        echo "📬 Vui lòng kiểm tra hộp thư: $testEmail\n";
        echo "   (Lưu ý: Kiểm tra cả thư mục Spam)\n\n";
    } else {
        echo "⚠️  MỘT SỐ EMAIL GỬI THẤT BẠI\n\n";
        echo "Vui lòng kiểm tra:\n";
        echo "   - Cấu hình SMTP trong .env\n";
        echo "   - Gmail App Password\n";
        echo "   - Error log\n\n";
    }
    
    echo "✅ HOÀN THÀNH!\n";
    
} catch (Exception $e) {
    echo "\n❌ LỖI: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
