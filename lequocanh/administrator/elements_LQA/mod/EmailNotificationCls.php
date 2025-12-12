<?php
/**
 * Email & SMS Notification Class
 * Gửi thông báo email/SMS khi xác nhận đơn hàng
 * 
 * @author LQA E-commerce System
 * @version 1.0
 */

require_once __DIR__ . '/database.php';

class EmailNotification
{
    private $db;
    private $config;
    
    // Email templates
    const TEMPLATE_ORDER_CONFIRMATION = 'order_confirmation';
    const TEMPLATE_ORDER_APPROVED = 'order_approved';
    const TEMPLATE_ORDER_SHIPPED = 'order_shipped';
    const TEMPLATE_ORDER_DELIVERED = 'order_delivered';
    const TEMPLATE_ORDER_CANCELLED = 'order_cancelled';
    const TEMPLATE_PAYMENT_RECEIVED = 'payment_received';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->loadConfig();
        $this->ensureTableExists();
    }

    /**
     * Load cấu hình email từ .env hoặc database
     */
    private function loadConfig()
    {
        $this->config = [
            'smtp_host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
            'smtp_port' => getenv('MAIL_PORT') ?: 587,
            'smtp_username' => getenv('MAIL_USERNAME') ?: '',
            'smtp_password' => getenv('MAIL_PASSWORD') ?: '',
            'from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@lqa-shop.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'LQA Shop',
            'sms_api_key' => getenv('SMS_API_KEY') ?: '',
            'sms_sender' => getenv('SMS_SENDER') ?: 'LQA Shop'
        ];
    }

    /**
     * Đảm bảo bảng notification_logs tồn tại
     */
    private function ensureTableExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS notification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT,
                user_id VARCHAR(50),
                type ENUM('email', 'sms', 'push') NOT NULL,
                template VARCHAR(50),
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(255),
                content TEXT,
                status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
                error_message TEXT,
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id),
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating notification_logs table: " . $e->getMessage());
        }
    }

    /**
     * Gửi email xác nhận đơn hàng
     */
    public function sendOrderConfirmation($orderId, $userEmail, $userName = '')
    {
        $order = $this->getOrderDetails($orderId);
        if (!$order) {
            error_log("Order not found for email notification: $orderId");
            return false;
        }

        $subject = "✅ Xác nhận đơn hàng #{$order['ma_don_hang_text']} - LQA Shop";
        $content = $this->buildOrderConfirmationEmail($order, $userName);

        return $this->sendEmail($userEmail, $subject, $content, [
            'order_id' => $orderId,
            'user_id' => $order['ma_nguoi_dung'],
            'template' => self::TEMPLATE_ORDER_CONFIRMATION
        ]);
    }

    /**
     * Gửi email khi đơn hàng được duyệt
     */
    public function sendOrderApproved($orderId, $userEmail, $userName = '')
    {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;

        $subject = "🎉 Đơn hàng #{$order['ma_don_hang_text']} đã được duyệt - LQA Shop";
        $content = $this->buildOrderApprovedEmail($order, $userName);

        return $this->sendEmail($userEmail, $subject, $content, [
            'order_id' => $orderId,
            'user_id' => $order['ma_nguoi_dung'],
            'template' => self::TEMPLATE_ORDER_APPROVED
        ]);
    }

    /**
     * Gửi email khi đơn hàng được giao
     */
    public function sendOrderShipped($orderId, $userEmail, $trackingNumber = '', $userName = '')
    {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;

        $subject = "🚚 Đơn hàng #{$order['ma_don_hang_text']} đang được giao - LQA Shop";
        $content = $this->buildOrderShippedEmail($order, $trackingNumber, $userName);

        return $this->sendEmail($userEmail, $subject, $content, [
            'order_id' => $orderId,
            'user_id' => $order['ma_nguoi_dung'],
            'template' => self::TEMPLATE_ORDER_SHIPPED
        ]);
    }

    /**
     * Gửi SMS thông báo đơn hàng
     */
    public function sendOrderSMS($orderId, $phoneNumber, $type = 'confirmation')
    {
        $order = $this->getOrderDetails($orderId);
        if (!$order) return false;

        $message = $this->buildSMSMessage($order, $type);
        
        return $this->sendSMS($phoneNumber, $message, [
            'order_id' => $orderId,
            'user_id' => $order['ma_nguoi_dung'],
            'template' => $type
        ]);
    }

    /**
     * Gửi cả email và SMS
     */
    public function sendOrderNotifications($orderId, $email, $phone = null, $type = 'confirmation', $userName = '')
    {
        $results = ['email' => false, 'sms' => false];

        // Gửi email
        if (!empty($email)) {
            switch ($type) {
                case 'confirmation':
                    $results['email'] = $this->sendOrderConfirmation($orderId, $email, $userName);
                    break;
                case 'approved':
                    $results['email'] = $this->sendOrderApproved($orderId, $email, $userName);
                    break;
                case 'shipped':
                    $results['email'] = $this->sendOrderShipped($orderId, $email, '', $userName);
                    break;
            }
        }

        // Gửi SMS nếu có số điện thoại
        if (!empty($phone) && !empty($this->config['sms_api_key'])) {
            $results['sms'] = $this->sendOrderSMS($orderId, $phone, $type);
        }

        return $results;
    }

    /**
     * Gửi email thực tế
     */
    private function sendEmail($to, $subject, $content, $metadata = [])
    {
        // Log notification
        $logId = $this->logNotification([
            'order_id' => $metadata['order_id'] ?? null,
            'user_id' => $metadata['user_id'] ?? null,
            'type' => 'email',
            'template' => $metadata['template'] ?? null,
            'recipient' => $to,
            'subject' => $subject,
            'content' => $content,
            'status' => 'pending'
        ]);

        try {
            // Kiểm tra cấu hình SMTP
            if (empty($this->config['smtp_username']) || empty($this->config['smtp_password'])) {
                // Fallback: sử dụng mail() function
                $headers = [
                    'MIME-Version: 1.0',
                    'Content-type: text/html; charset=UTF-8',
                    'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                    'Reply-To: ' . $this->config['from_email'],
                    'X-Mailer: PHP/' . phpversion()
                ];

                $result = @mail($to, $subject, $content, implode("\r\n", $headers));
                
                if ($result) {
                    $this->updateNotificationStatus($logId, 'sent');
                    error_log("Email sent successfully to: $to");
                    return true;
                } else {
                    $this->updateNotificationStatus($logId, 'failed', 'mail() function failed');
                    error_log("Failed to send email to: $to");
                    return false;
                }
            }

            // Sử dụng SMTP (nếu có PHPMailer hoặc tương tự)
            // Ở đây sử dụng fsockopen để gửi qua SMTP đơn giản
            $result = $this->sendViaSMTP($to, $subject, $content);
            
            if ($result) {
                $this->updateNotificationStatus($logId, 'sent');
                return true;
            } else {
                $this->updateNotificationStatus($logId, 'failed', 'SMTP send failed');
                return false;
            }

        } catch (Exception $e) {
            $this->updateNotificationStatus($logId, 'failed', $e->getMessage());
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi email qua SMTP sử dụng fsockopen
     */
    private function sendViaSMTP($to, $subject, $content)
    {
        $host = $this->config['smtp_host'];
        $port = $this->config['smtp_port'];
        $username = $this->config['smtp_username'];
        $password = $this->config['smtp_password'];
        $from = $this->config['from_email'];
        $fromName = $this->config['from_name'];

        try {
            // Kết nối đến SMTP server
            $smtp = fsockopen($host, $port, $errno, $errstr, 30);
            if (!$smtp) {
                throw new Exception("Cannot connect to SMTP server: $errstr ($errno)");
            }

            // Đọc response
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("SMTP Error: $response");
            }

            // EHLO
            fputs($smtp, "EHLO $host\r\n");
            // Read all EHLO responses
            while ($line = fgets($smtp, 515)) {
                if (substr($line, 3, 1) == ' ') break; // Last line
            }

            // STARTTLS
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("STARTTLS failed: $response");
            }

            // Enable crypto
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // EHLO again after STARTTLS
            fputs($smtp, "EHLO $host\r\n");
            // Read all EHLO responses
            while ($line = fgets($smtp, 515)) {
                if (substr($line, 3, 1) == ' ') break; // Last line
            }

            // AUTH LOGIN
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '334') {
                throw new Exception("AUTH LOGIN failed: $response");
            }

            // Username
            fputs($smtp, base64_encode($username) . "\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '334') {
                throw new Exception("Username failed: $response");
            }

            // Password
            fputs($smtp, base64_encode($password) . "\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '235') {
                throw new Exception("Password failed: $response");
            }

            // MAIL FROM
            fputs($smtp, "MAIL FROM: <$from>\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("MAIL FROM failed: $response");
            }

            // RCPT TO
            fputs($smtp, "RCPT TO: <$to>\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("RCPT TO failed: $response");
            }

            // DATA
            fputs($smtp, "DATA\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '354') {
                throw new Exception("DATA failed: $response");
            }

            // Email headers and body
            $headers = "From: $fromName <$from>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";

            fputs($smtp, $headers . $content . "\r\n.\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("Message send failed: $response");
            }

            // QUIT
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);

            return true;

        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            if (isset($smtp) && is_resource($smtp)) {
                fclose($smtp);
            }
            return false;
        }
    }

    /**
     * Gửi SMS
     */
    private function sendSMS($phoneNumber, $message, $metadata = [])
    {
        // Log notification
        $logId = $this->logNotification([
            'order_id' => $metadata['order_id'] ?? null,
            'user_id' => $metadata['user_id'] ?? null,
            'type' => 'sms',
            'template' => $metadata['template'] ?? null,
            'recipient' => $phoneNumber,
            'subject' => null,
            'content' => $message,
            'status' => 'pending'
        ]);

        // Kiểm tra API key
        if (empty($this->config['sms_api_key'])) {
            error_log("SMS API key not configured - SMS not sent to: $phoneNumber");
            $this->updateNotificationStatus($logId, 'failed', 'SMS API key not configured');
            return false;
        }

        try {
            // Chuẩn hóa số điện thoại Việt Nam
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
            
            // Gửi SMS qua provider đã cấu hình
            $provider = $this->config['sms_provider'] ?? 'speedsms';
            
            switch ($provider) {
                case 'speedsms':
                    $result = $this->sendViaSpeedSMS($phoneNumber, $message);
                    break;
                case 'esms':
                    $result = $this->sendViaESMS($phoneNumber, $message);
                    break;
                default:
                    // Development mode - chỉ log
                    error_log("SMS [DEV MODE] to: $phoneNumber - Message: $message");
                    $result = true;
            }
            
            if ($result) {
                $this->updateNotificationStatus($logId, 'sent');
                error_log("SMS sent successfully to: $phoneNumber");
                return true;
            } else {
                $this->updateNotificationStatus($logId, 'failed', 'SMS provider returned error');
                return false;
            }

        } catch (Exception $e) {
            $this->updateNotificationStatus($logId, 'failed', $e->getMessage());
            error_log("SMS send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Chuẩn hóa số điện thoại Việt Nam
     */
    private function normalizePhoneNumber($phone)
    {
        // Loại bỏ khoảng trắng và ký tự đặc biệt
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Chuyển đổi đầu số
        if (substr($phone, 0, 1) === '0') {
            $phone = '84' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '84') {
            $phone = '84' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Gửi SMS qua SpeedSMS (Vietnam)
     * Đăng ký tại: https://speedsms.vn
     */
    private function sendViaSpeedSMS($phoneNumber, $message)
    {
        $apiKey = $this->config['sms_api_key'];
        $sender = $this->config['sms_sender'] ?? 'LQA Shop';
        
        $url = 'https://api.speedsms.vn/index.php/sms/send';
        
        $data = [
            'to' => [$phoneNumber],
            'content' => $message,
            'sms_type' => 2, // 2 = Brandname, 3 = Notify
            'sender' => $sender
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($apiKey . ':x')
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] === 'success') {
                return true;
            }
            error_log("SpeedSMS error: " . $response);
        } else {
            error_log("SpeedSMS HTTP error: $httpCode - $response");
        }
        
        return false;
    }
    
    /**
     * Gửi SMS qua eSMS (Vietnam)
     * Đăng ký tại: https://esms.vn
     */
    private function sendViaESMS($phoneNumber, $message)
    {
        $apiKey = $this->config['sms_api_key'];
        $secretKey = $this->config['sms_secret_key'] ?? '';
        $brandname = $this->config['sms_sender'] ?? 'LQA Shop';
        
        $url = 'http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_get';
        
        $params = [
            'Phone' => $phoneNumber,
            'Content' => $message,
            'ApiKey' => $apiKey,
            'SecretKey' => $secretKey,
            'Brandname' => $brandname,
            'SmsType' => 2 // 2 = Brandname
        ];
        
        $url .= '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['CodeResult']) && $result['CodeResult'] === '100') {
                return true;
            }
            error_log("eSMS error: " . $response);
        } else {
            error_log("eSMS HTTP error: $httpCode - $response");
        }
        
        return false;
    }

    /**
     * Build email xác nhận đơn hàng
     */
    private function buildOrderConfirmationEmail($order, $userName)
    {
        $orderItems = $this->getOrderItems($order['id']);
        $itemsHtml = $this->buildOrderItemsHtml($orderItems);
        
        $shippingMethod = $order['shipping_method_name'] ?? 'Giao hàng tiêu chuẩn';
        $estimatedDelivery = $order['estimated_delivery'] ?? 'Trong 3-5 ngày làm việc';

        return $this->getEmailTemplate('order_confirmation', [
            'user_name' => $userName ?: 'Quý khách',
            'order_code' => $order['ma_don_hang_text'],
            'order_date' => date('d/m/Y H:i', strtotime($order['ngay_tao'])),
            'order_items' => $itemsHtml,
            'subtotal' => number_format($order['tong_tien'] - ($order['thue'] ?? 0) - ($order['phi_van_chuyen'] ?? 0), 0, ',', '.'),
            'tax' => number_format($order['thue'] ?? 0, 0, ',', '.'),
            'shipping_fee' => number_format($order['phi_van_chuyen'] ?? 0, 0, ',', '.'),
            'total' => number_format($order['tong_tien'], 0, ',', '.'),
            'shipping_address' => $order['dia_chi_giao_hang'],
            'shipping_method' => $shippingMethod,
            'estimated_delivery' => $estimatedDelivery,
            'payment_method' => $this->getPaymentMethodName($order['phuong_thuc_thanh_toan']),
            'payment_status' => $order['trang_thai_thanh_toan'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'
        ]);
    }

    /**
     * Build email đơn hàng được duyệt
     */
    private function buildOrderApprovedEmail($order, $userName)
    {
        return $this->getEmailTemplate('order_approved', [
            'user_name' => $userName ?: 'Quý khách',
            'order_code' => $order['ma_don_hang_text'],
            'total' => number_format($order['tong_tien'], 0, ',', '.'),
            'shipping_address' => $order['dia_chi_giao_hang']
        ]);
    }

    /**
     * Build email đơn hàng đang giao
     */
    private function buildOrderShippedEmail($order, $trackingNumber, $userName)
    {
        return $this->getEmailTemplate('order_shipped', [
            'user_name' => $userName ?: 'Quý khách',
            'order_code' => $order['ma_don_hang_text'],
            'tracking_number' => $trackingNumber ?: 'Đang cập nhật',
            'shipping_address' => $order['dia_chi_giao_hang']
        ]);
    }

    /**
     * Build SMS message
     */
    private function buildSMSMessage($order, $type)
    {
        $orderCode = $order['ma_don_hang_text'];
        $total = number_format($order['tong_tien'], 0, ',', '.');

        switch ($type) {
            case 'confirmation':
                return "LQA Shop: Don hang #{$orderCode} da duoc tao thanh cong. Tong tien: {$total}d. Cam on quy khach!";
            case 'approved':
                return "LQA Shop: Don hang #{$orderCode} da duoc duyet va dang chuan bi. Chung toi se giao hang som nhat!";
            case 'shipped':
                return "LQA Shop: Don hang #{$orderCode} dang duoc giao. Vui long kiem tra dien thoai de nhan hang.";
            default:
                return "LQA Shop: Cap nhat don hang #{$orderCode}. Truy cap website de xem chi tiet.";
        }
    }

    /**
     * Get email template
     */
    private function getEmailTemplate($templateName, $data)
    {
        $templates = [
            'order_confirmation' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .order-info { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .order-items { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .order-items th, .order-items td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Xác nhận đơn hàng</h1>
        </div>
        <div class="content">
            <p>Xin chào <strong>{user_name}</strong>,</p>
            <p>Cảm ơn bạn đã đặt hàng tại LQA Shop! Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.</p>
            
            <div class="order-info">
                <h3>📦 Thông tin đơn hàng</h3>
                <p><strong>Mã đơn hàng:</strong> #{order_code}</p>
                <p><strong>Ngày đặt:</strong> {order_date}</p>
                <p><strong>Phương thức thanh toán:</strong> {payment_method}</p>
                <p><strong>Trạng thái thanh toán:</strong> {payment_status}</p>
            </div>

            <h3>🛒 Chi tiết đơn hàng</h3>
            {order_items}
            
            <table class="order-items">
                <tr><td>Tạm tính:</td><td style="text-align:right">{subtotal} ₫</td></tr>
                <tr><td>Thuế VAT:</td><td style="text-align:right">{tax} ₫</td></tr>
                <tr><td>Phí vận chuyển:</td><td style="text-align:right">{shipping_fee} ₫</td></tr>
                <tr class="total-row"><td>Tổng cộng:</td><td style="text-align:right">{total} ₫</td></tr>
            </table>

            <div class="order-info">
                <h3>🚚 Thông tin giao hàng</h3>
                <p><strong>Địa chỉ:</strong> {shipping_address}</p>
                <p><strong>Phương thức:</strong> {shipping_method}</p>
                <p><strong>Dự kiến giao:</strong> {estimated_delivery}</p>
            </div>

            <p style="text-align: center; margin-top: 20px;">
                <a href="#" class="btn">Theo dõi đơn hàng</a>
            </p>
        </div>
        <div class="footer">
            <p>LQA Shop - Cảm ơn bạn đã mua sắm cùng chúng tôi!</p>
            <p>Nếu có thắc mắc, vui lòng liên hệ: support@lqa-shop.com</p>
        </div>
    </div>
</body>
</html>',

            'order_approved' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Đơn hàng đã được duyệt!</h1>
        </div>
        <div class="content">
            <p>Xin chào <strong>{user_name}</strong>,</p>
            <p>Đơn hàng <strong>#{order_code}</strong> của bạn đã được duyệt và đang được chuẩn bị.</p>
            <p><strong>Tổng tiền:</strong> {total} ₫</p>
            <p><strong>Địa chỉ giao hàng:</strong> {shipping_address}</p>
            <p>Chúng tôi sẽ thông báo khi đơn hàng được giao cho đơn vị vận chuyển.</p>
        </div>
        <div class="footer">
            <p>LQA Shop - Cảm ơn bạn đã mua sắm cùng chúng tôi!</p>
        </div>
    </div>
</body>
</html>',

            'order_shipped' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #17a2b8; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .tracking-box { background: #e9ecef; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚚 Đơn hàng đang được giao!</h1>
        </div>
        <div class="content">
            <p>Xin chào <strong>{user_name}</strong>,</p>
            <p>Đơn hàng <strong>#{order_code}</strong> của bạn đã được giao cho đơn vị vận chuyển.</p>
            <div class="tracking-box">
                <p><strong>Mã vận đơn:</strong></p>
                <h2>{tracking_number}</h2>
            </div>
            <p><strong>Địa chỉ giao hàng:</strong> {shipping_address}</p>
            <p>Vui lòng giữ điện thoại để nhận hàng.</p>
        </div>
        <div class="footer">
            <p>LQA Shop - Cảm ơn bạn đã mua sắm cùng chúng tôi!</p>
        </div>
    </div>
</body>
</html>'
        ];

        $template = $templates[$templateName] ?? $templates['order_confirmation'];
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    /**
     * Build HTML cho danh sách sản phẩm
     */
    private function buildOrderItemsHtml($items)
    {
        $html = '<table class="order-items"><tr><th>Sản phẩm</th><th>SL</th><th>Giá</th></tr>';
        
        foreach ($items as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['ten_san_pham'] ?? 'Sản phẩm') . '</td>';
            $html .= '<td>' . $item['so_luong'] . '</td>';
            $html .= '<td>' . number_format($item['gia'] * $item['so_luong'], 0, ',', '.') . ' ₫</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    private function getOrderDetails($orderId)
    {
        try {
            $sql = "SELECT * FROM don_hang WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting order details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    private function getOrderItems($orderId)
    {
        try {
            $sql = "SELECT ctdh.*, hh.tenhanghoa as ten_san_pham 
                    FROM chi_tiet_don_hang ctdh
                    LEFT JOIN hanghoa hh ON ctdh.ma_san_pham = hh.idhanghoa
                    WHERE ctdh.ma_don_hang = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting order items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tên phương thức thanh toán
     */
    private function getPaymentMethodName($method)
    {
        $methods = [
            'momo' => 'Ví MoMo',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'cod' => 'Thanh toán khi nhận hàng (COD)'
        ];
        return $methods[$method] ?? $method;
    }

    /**
     * Log notification
     */
    private function logNotification($data)
    {
        try {
            $sql = "INSERT INTO notification_logs 
                    (order_id, user_id, type, template, recipient, subject, content, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['order_id'],
                $data['user_id'],
                $data['type'],
                $data['template'],
                $data['recipient'],
                $data['subject'],
                $data['content'],
                $data['status']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error logging notification: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cập nhật trạng thái notification
     */
    private function updateNotificationStatus($logId, $status, $errorMessage = null)
    {
        if (!$logId) return;
        
        try {
            $sql = "UPDATE notification_logs SET 
                    status = ?, 
                    error_message = ?,
                    sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE sent_at END
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $errorMessage, $status, $logId]);
        } catch (PDOException $e) {
            error_log("Error updating notification status: " . $e->getMessage());
        }
    }

    /**
     * Lấy lịch sử thông báo của đơn hàng
     */
    public function getOrderNotificationHistory($orderId)
    {
        try {
            $sql = "SELECT * FROM notification_logs WHERE order_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting notification history: " . $e->getMessage());
            return [];
        }
    }
}
