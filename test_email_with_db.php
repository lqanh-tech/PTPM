<?php
/**
 * Test đầy đủ hệ thống email với database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!getenv($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/EmailService.php';

echo "<html><head><meta charset='utf-8'><title>Test Email với Database</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f5f5f5; }
</style>
</head><body>";

echo "<h1>🧪 Test Email với Database</h1><hr>";

$testEmail = isset($_GET['email']) ? $_GET['email'] : getenv('MAIL_USERNAME');
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p class='success'>✅ Kết nối database thành công</p>";
    
    // Lấy đơn hàng mới nhất nếu không có order_id
    if ($orderId == 0) {
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai FROM don_hang ORDER BY id DESC LIMIT 5";
        $stmt = $conn->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Đơn hàng gần đây:</h2>";
        echo "<table><tr><th>ID</th><th>Mã đơn</th><th>User</th><th>Tổng tiền</th><th>Trạng thái</th><th>Test</th></tr>";
        foreach ($orders as $o) {
            echo "<tr>";
            echo "<td>{$o['id']}</td>";
            echo "<td>{$o['ma_don_hang_text']}</td>";
            echo "<td>{$o['ma_nguoi_dung']}</td>";
            echo "<td>" . number_format($o['tong_tien']) . "đ</td>";
            echo "<td>{$o['trang_thai']}</td>";
            echo "<td><a href='?order_id={$o['id']}&email=$testEmail'>Test email</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!empty($orders)) {
            $orderId = $orders[0]['id'];
            echo "<p class='info'>📌 Sử dụng đơn hàng mới nhất: #$orderId</p>";
        }
    }
    
    if ($orderId > 0) {
        echo "<h2>Test gửi email cho đơn hàng #$orderId</h2>";
        echo "<p>📧 Gửi đến: <strong>$testEmail</strong></p>";
        
        $emailService = new EmailService();
        
        // Test từng loại email
        $tests = [
            ['name' => 'Order Success', 'method' => 'sendOrderSuccessEmail'],
            ['name' => 'Order Approved', 'method' => 'sendOrderApprovedEmail'],
            ['name' => 'Payment Confirmed', 'method' => 'sendPaymentConfirmedEmail'],
            ['name' => 'Order Cancelled', 'method' => 'sendOrderCancelledEmail'],
        ];
        
        echo "<table><tr><th>Loại email</th><th>Kết quả</th></tr>";
        
        foreach ($tests as $test) {
            echo "<tr><td>{$test['name']}</td><td>";
            
            try {
                if ($test['method'] == 'sendOrderCancelledEmail') {
                    $result = $emailService->{$test['method']}($orderId, $testEmail, 'Test hủy đơn');
                } else {
                    $result = $emailService->{$test['method']}($orderId, $testEmail);
                }
                
                if ($result) {
                    echo "<span class='success'>✅ Thành công</span>";
                } else {
                    echo "<span class='error'>❌ Thất bại</span>";
                }
            } catch (Exception $e) {
                echo "<span class='error'>❌ Lỗi: " . $e->getMessage() . "</span>";
            }
            
            echo "</td></tr>";
            
            // Đợi 1 giây giữa các email để tránh rate limit
            sleep(1);
        }
        
        echo "</table>";
        
        echo "<h2>✅ Hoàn tất!</h2>";
        echo "<p>Kiểm tra hộp thư <strong>$testEmail</strong> để xem các email.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
