<?php
/**
 * Send Email via SMTP (without PHPMailer)
 * Gửi email trực tiếp qua SMTP Gmail
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SEND EMAIL VIA SMTP ===\n\n";

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

// Load config từ .env
$envFile = '.env';
$config = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $config[trim($key)] = trim($value);
    }
}

$smtpHost = $config['MAIL_HOST'] ?? 'smtp.gmail.com';
$smtpPort = $config['MAIL_PORT'] ?? 587;
$smtpUser = $config['MAIL_USERNAME'] ?? '';
$smtpPass = $config['MAIL_PASSWORD'] ?? '';
$fromEmail = $config['MAIL_FROM_ADDRESS'] ?? '';
$fromName = $config['MAIL_FROM_NAME'] ?? 'LQA Shop';

echo "📧 SMTP Configuration:\n";
echo "   Host: $smtpHost\n";
echo "   Port: $smtpPort\n";
echo "   User: $smtpUser\n";
echo "   Pass: " . (empty($smtpPass) ? 'NOT SET' : '****') . "\n\n";

if (empty($smtpUser) || empty($smtpPass)) {
    echo "❌ ERROR: SMTP credentials not configured in .env\n";
    echo "Please set MAIL_USERNAME and MAIL_PASSWORD in .env file\n";
    exit(1);
}

try {
    $db = Database::getInstance()->getConnection();
    
    $testEmail = 'quocanh5758@gmail.com';
    
    echo "📬 Recipient: $testEmail\n\n";
    
    // Tìm user
    echo "🔍 Finding user...\n";
    $sql = "SELECT * FROM user WHERE email = ? OR username LIKE '%quocanh%' LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "⚠️  User not found. Creating test user...\n";
        $testUsername = 'testuser_' . time();
        $insertSql = "INSERT INTO user (username, password, hoten, email, gioitinh, ngaysinh, diachi, dienthoai, setlock) 
                      VALUES (?, ?, ?, ?, 1, '1990-01-01', 'Test Address', '0123456789', 1)";
        $stmt = $db->prepare($insertSql);
        $stmt->execute([$testUsername, password_hash('test123', PASSWORD_DEFAULT), 'Test User', $testEmail]);
        
        $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$testUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Created user: {$user['username']}\n\n";
    } else {
        echo "✅ Found user: {$user['hoten']} ({$user['username']})\n\n";
    }
    
    // Tìm đơn hàng
    echo "🔍 Finding order...\n";
    $sql = "SELECT * FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user['username']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "⚠️  Order not found. Creating test order...\n";
        $orderCode = 'TEST' . time();
        $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan, ngay_tao) 
                          VALUES (?, ?, 500000, 'pending', 'pending', 'cod', NOW())";
        $stmt = $db->prepare($insertOrderSql);
        $stmt->execute([$orderCode, $user['username']]);
        
        $orderId = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Created order: #{$order['ma_don_hang_text']}\n\n";
    } else {
        echo "✅ Found order: #{$order['ma_don_hang_text']}\n";
        echo "   Total: " . number_format($order['tong_tien'], 0, ',', '.') . " đ\n\n";
    }
    
    // Tạo email content
    $subject = "✅ Test Email - Đơn hàng #{$order['ma_don_hang_text']}";
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0;'>✅ Test Email Thành Công!</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Xin chào <strong>{$user['hoten']}</strong>,</p>
                <p>Đây là email test từ hệ thống LQA Shop.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>📦 Thông Tin Đơn Hàng</h3>
                    <p><strong>Mã đơn hàng:</strong> {$order['ma_don_hang_text']}</p>
                    <p><strong>Tổng tiền:</strong> " . number_format($order['tong_tien'], 0, ',', '.') . " đ</p>
                    <p><strong>Trạng thái:</strong> {$order['trang_thai']}</p>
                </div>
                
                <p style='color: #666;'>Nếu bạn nhận được email này, nghĩa là hệ thống email đã hoạt động!</p>
            </div>
            <div style='background: #333; color: white; padding: 20px; text-align: center;'>
                <p style='margin: 0;'><strong>LQA Shop</strong></p>
                <p style='margin: 10px 0 0 0; font-size: 12px; opacity: 0.6;'>© 2025 LQA Shop. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    echo "=== SENDING EMAIL VIA SMTP ===\n\n";
    echo "📨 Connecting to SMTP server...\n";
    
    // Kết nối SMTP
    $socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
    
    if (!$socket) {
        throw new Exception("Cannot connect to SMTP server: $errstr ($errno)");
    }
    
    echo "✅ Connected to $smtpHost:$smtpPort\n";
    
    // Đọc response
    $response = fgets($socket, 515);
    echo "   Server: " . trim($response) . "\n";
    
    // EHLO
    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 515);
    echo "   EHLO: " . trim($response) . "\n";
    
    // STARTTLS
    fputs($socket, "STARTTLS\r\n");
    $response = fgets($socket, 515);
    echo "   STARTTLS: " . trim($response) . "\n";
    
    // Enable crypto
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // EHLO again after TLS
    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 515);
    
    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    echo "   AUTH: " . trim($response) . "\n";
    
    fputs($socket, base64_encode($smtpUser) . "\r\n");
    $response = fgets($socket, 515);
    
    fputs($socket, base64_encode($smtpPass) . "\r\n");
    $response = fgets($socket, 515);
    echo "   Login: " . trim($response) . "\n";
    
    if (strpos($response, '235') === false) {
        throw new Exception("SMTP Authentication failed: $response");
    }
    
    echo "✅ Authenticated successfully\n\n";
    
    // MAIL FROM
    fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
    $response = fgets($socket, 515);
    
    // RCPT TO
    fputs($socket, "RCPT TO: <$testEmail>\r\n");
    $response = fgets($socket, 515);
    
    // DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    
    // Email headers and body
    $emailData = "From: $fromName <$fromEmail>\r\n";
    $emailData .= "To: <$testEmail>\r\n";
    $emailData .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $emailData .= "MIME-Version: 1.0\r\n";
    $emailData .= "Content-Type: text/html; charset=UTF-8\r\n";
    $emailData .= "\r\n";
    $emailData .= $message;
    $emailData .= "\r\n.\r\n";
    
    fputs($socket, $emailData);
    $response = fgets($socket, 515);
    echo "📨 Sending email...\n";
    echo "   Response: " . trim($response) . "\n";
    
    if (strpos($response, '250') !== false) {
        echo "\n🎉 EMAIL SENT SUCCESSFULLY!\n\n";
        echo "📬 Please check your inbox: $testEmail\n";
        echo "   (Note: Check Spam folder if not in Inbox)\n\n";
        $success = true;
    } else {
        echo "\n❌ FAILED TO SEND EMAIL\n";
        echo "   Response: $response\n\n";
        $success = false;
    }
    
    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    echo "✅ COMPLETED!\n";
    
    exit($success ? 0 : 1);
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
