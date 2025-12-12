<?php
/**
 * Email Service
 * 
 * Service để gửi email thông báo cho khách hàng
 * Sử dụng PHPMailer với SMTP
 */

// Load PHPMailer
$vendorAutoload = __DIR__ . '/../../../../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    
    public function __construct() {
        // Load .env if not loaded
        $this->loadEnv();
        
        $this->fromEmail = $this->getEnv('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $this->fromName = $this->getEnv('MAIL_FROM_NAME', 'LQA Shop');
        $this->smtpHost = $this->getEnv('MAIL_HOST', 'smtp.gmail.com');
        $this->smtpPort = (int)$this->getEnv('MAIL_PORT', 587);
        $this->smtpUsername = $this->getEnv('MAIL_USERNAME', '');
        $this->smtpPassword = $this->getEnv('MAIL_PASSWORD', '');
        
        // Log SMTP configuration (without password)
        error_log("EmailService initialized - SMTP: {$this->smtpHost}:{$this->smtpPort}, User: {$this->smtpUsername}, From: {$this->fromEmail}");
    }
    
    /**
     * Load .env file
     */
    private function loadEnv() {
        $envFile = __DIR__ . '/../../../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!isset($_ENV[$name]) && !getenv($name)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                }
            }
        }
    }
    
    /**
     * Get environment variable
     */
    private function getEnv($key, $default = '') {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $default;
        }
        return $value ?: $default;
    }
    
    /**
     * Send email via SMTP using PHPMailer
     */
    private function sendViaSMTP($to, $subject, $htmlBody) {
        try {
            error_log("EmailService: Attempting to send email to $to via SMTP");
            
            // Validate SMTP configuration
            if (empty($this->smtpUsername) || empty($this->smtpPassword)) {
                error_log("EmailService: ❌ SMTP credentials not configured in .env");
                error_log("EmailService: MAIL_USERNAME=" . $this->smtpUsername);
                error_log("EmailService: MAIL_PASSWORD=" . (empty($this->smtpPassword) ? '(empty)' : '(set)'));
                return false;
            }
            
            // Check if PHPMailer is available
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log("EmailService: ❌ PHPMailer not found. Please run: composer require phpmailer/phpmailer");
                return false;
            }
            
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            
            // Enable debug output for troubleshooting
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            
            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            
            $mail->send();
            error_log("EmailService: ✅ Email sent successfully to $to");
            return true;
            
        } catch (Exception $e) {
            error_log("EmailService: ❌ PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send shipping update email
     */
    public function sendShippingUpdateEmail($toEmail, $customerName, $orderCode, $status, $statusDescription, $trackingCode) {
        $statusLabels = [
            'pending' => 'Chờ xử lý',
            'picking' => 'Đang lấy hàng',
            'shipping' => 'Đang vận chuyển',
            'delivered' => 'Đã giao hàng',
            'failed' => 'Giao hàng thất bại',
            'returned' => 'Đã hoàn trả',
            'cancelled' => 'Đã hủy'
        ];
        
        $statusLabel = $statusLabels[$status] ?? $status;
        $subject = "Cập nhật đơn hàng #{$orderCode} - {$statusLabel}";
        $message = $this->getShippingEmailTemplate($customerName, $orderCode, $statusLabel, $statusDescription, $trackingCode);
        
        return $this->sendViaSMTP($toEmail, $subject, $message);
    }
    
    /**
     * Get tracking URL
     */
    private function getTrackingUrl($orderCode) {
        $baseUrl = $this->getEnv('BASE_URL', 'http://localhost:20080');
        return $baseUrl . '/lequocanh/track_order.php?code=' . urlencode($orderCode);
    }
    
    /**
     * Get shipping email template
     */
    private function getShippingEmailTemplate($customerName, $orderCode, $statusLabel, $statusDescription, $trackingCode) {
        $trackingUrl = $this->getTrackingUrl($orderCode);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .status-box { background: white; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚚 Cập Nhật Đơn Hàng</h1>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$customerName}</strong>,</p>
                    <p>Đơn hàng của bạn có cập nhật mới:</p>
                    <div class='status-box'>
                        <h3>Mã đơn hàng: #{$orderCode}</h3>
                        <p><strong>Trạng thái:</strong> {$statusLabel}</p>
                        <p><strong>Chi tiết:</strong> {$statusDescription}</p>
                        <p><strong>Mã vận đơn:</strong> {$trackingCode}</p>
                    </div>
                    <p>Bạn có thể tra cứu chi tiết đơn hàng tại:</p>
                    <a href='{$trackingUrl}' class='button'>Tra Cứu Đơn Hàng</a>
                    <p>Cảm ơn bạn đã mua hàng!</p>
                </div>
                <div class='footer'>
                    <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                    <p>&copy; " . date('Y') . " {$this->fromName}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }


    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmationEmail($toEmail, $customerName, $orderCode, $totalAmount) {
        $subject = "Xác nhận đơn hàng #{$orderCode}";
        $trackingUrl = $this->getTrackingUrl($orderCode);
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='utf-8'></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1>✅ Đơn Hàng Đã Được Xác Nhận</h1>
                </div>
                <div style='background: #f8f9fa; padding: 30px;'>
                    <p>Xin chào <strong>{$customerName}</strong>,</p>
                    <p>Cảm ơn bạn đã đặt hàng! Đơn hàng của bạn đã được xác nhận.</p>
                    <div style='background: white; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                        <h3>Mã đơn hàng: #{$orderCode}</h3>
                        <p><strong>Tổng tiền:</strong> " . number_format($totalAmount, 0, ',', '.') . "₫</p>
                        <p><strong>Trạng thái:</strong> Đang xử lý</p>
                    </div>
                    <p>Chúng tôi sẽ gửi email thông báo khi đơn hàng được giao cho đơn vị vận chuyển.</p>
                    <a href='{$trackingUrl}' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0;'>Tra Cứu Đơn Hàng</a>
                </div>
                <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                    <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                    <p>&copy; " . date('Y') . " {$this->fromName}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $this->sendViaSMTP($toEmail, $subject, $message);
    }
    
    /**
     * Send order approved email
     */
    public function sendOrderApprovedEmail($orderId, $toEmail) {
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $sql = "SELECT dh.*, u.hoten FROM don_hang dh LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci WHERE dh.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("EmailService: Order not found - ID: $orderId");
                return false;
            }
            
            $customerName = $order['hoten'] ?: $order['ma_nguoi_dung'];
            $orderCode = $order['ma_don_hang_text'] ?: $orderId;
            $totalAmount = $order['tong_tien'];
            $trackingUrl = $this->getTrackingUrl($orderCode);
            
            $subject = "✅ Đơn hàng #{$orderCode} đã được duyệt";
            
            $message = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='utf-8'></head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1>✅ Đơn Hàng Đã Được Duyệt</h1>
                    </div>
                    <div style='background: #f8f9fa; padding: 30px;'>
                        <p>Xin chào <strong>{$customerName}</strong>,</p>
                        <p>Đơn hàng của bạn đã được duyệt và đang được chuẩn bị!</p>
                        <div style='background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;'>
                            <h3>Mã đơn hàng: #{$orderCode}</h3>
                            <p><strong>Tổng tiền:</strong> " . number_format($totalAmount, 0, ',', '.') . "₫</p>
                            <p><strong>Trạng thái:</strong> Đã duyệt - Đang chuẩn bị hàng</p>
                        </div>
                        <p>Chúng tôi sẽ thông báo cho bạn khi đơn hàng được giao cho đơn vị vận chuyển.</p>
                        <a href='{$trackingUrl}' style='display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0;'>Tra Cứu Đơn Hàng</a>
                        <p>Cảm ơn bạn đã tin tưởng và mua hàng!</p>
                    </div>
                    <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                        <p>&copy; " . date('Y') . " {$this->fromName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendViaSMTP($toEmail, $subject, $message);
            
        } catch (Exception $e) {
            error_log("EmailService: Error sending order approved email - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send order cancelled email
     */
    public function sendOrderCancelledEmail($orderId, $toEmail, $reason = '') {
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $sql = "SELECT dh.*, u.hoten FROM don_hang dh LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci WHERE dh.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("EmailService: Order not found - ID: $orderId");
                return false;
            }
            
            $customerName = $order['hoten'] ?: $order['ma_nguoi_dung'];
            $orderCode = $order['ma_don_hang_text'] ?: $orderId;
            $totalAmount = $order['tong_tien'];
            $trackingUrl = $this->getTrackingUrl($orderCode);
            $reasonText = !empty($reason) ? "<p><strong>Lý do:</strong> {$reason}</p>" : "";
            
            $subject = "❌ Đơn hàng #{$orderCode} đã bị hủy";
            
            $message = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='utf-8'></head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1>❌ Đơn Hàng Đã Bị Hủy</h1>
                    </div>
                    <div style='background: #f8f9fa; padding: 30px;'>
                        <p>Xin chào <strong>{$customerName}</strong>,</p>
                        <p>Rất tiếc, đơn hàng của bạn đã bị hủy.</p>
                        <div style='background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #dc3545;'>
                            <h3>Mã đơn hàng: #{$orderCode}</h3>
                            <p><strong>Tổng tiền:</strong> " . number_format($totalAmount, 0, ',', '.') . "₫</p>
                            <p><strong>Trạng thái:</strong> Đã hủy</p>
                            {$reasonText}
                        </div>
                        <p>Nếu bạn đã thanh toán, chúng tôi sẽ hoàn tiền trong vòng 3-5 ngày làm việc.</p>
                        <p>Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>
                        <a href='{$trackingUrl}' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0;'>Xem Chi Tiết</a>
                    </div>
                    <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                        <p>&copy; " . date('Y') . " {$this->fromName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendViaSMTP($toEmail, $subject, $message);
            
        } catch (Exception $e) {
            error_log("EmailService: Error sending order cancelled email - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment confirmed email
     */
    public function sendPaymentConfirmedEmail($orderId, $toEmail) {
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $sql = "SELECT dh.*, u.hoten FROM don_hang dh LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci WHERE dh.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("EmailService: Order not found - ID: $orderId");
                return false;
            }
            
            $customerName = $order['hoten'] ?: $order['ma_nguoi_dung'];
            $orderCode = $order['ma_don_hang_text'] ?: $orderId;
            $totalAmount = $order['tong_tien'];
            $trackingUrl = $this->getTrackingUrl($orderCode);
            
            $subject = "💰 Thanh toán đơn hàng #{$orderCode} đã được xác nhận";
            
            $message = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='utf-8'></head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1>💰 Thanh Toán Đã Được Xác Nhận</h1>
                    </div>
                    <div style='background: #f8f9fa; padding: 30px;'>
                        <p>Xin chào <strong>{$customerName}</strong>,</p>
                        <p>Chúng tôi đã nhận được thanh toán của bạn!</p>
                        <div style='background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #6f42c1;'>
                            <h3>Mã đơn hàng: #{$orderCode}</h3>
                            <p><strong>Số tiền:</strong> " . number_format($totalAmount, 0, ',', '.') . "₫</p>
                            <p><strong>Trạng thái:</strong> Đã thanh toán</p>
                        </div>
                        <p>Đơn hàng của bạn đang được xử lý và sẽ sớm được giao.</p>
                        <a href='{$trackingUrl}' style='display: inline-block; padding: 12px 30px; background: #6f42c1; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0;'>Tra Cứu Đơn Hàng</a>
                        <p>Cảm ơn bạn đã tin tưởng!</p>
                    </div>
                    <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                        <p>&copy; " . date('Y') . " {$this->fromName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendViaSMTP($toEmail, $subject, $message);
            
        } catch (Exception $e) {
            error_log("EmailService: Error sending payment confirmed email - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send order success email (khi đặt hàng thành công)
     */
    public function sendOrderSuccessEmail($orderId, $toEmail) {
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $sql = "SELECT dh.*, u.hoten FROM don_hang dh LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci WHERE dh.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("EmailService: Order not found - ID: $orderId");
                return false;
            }
            
            $customerName = $order['hoten'] ?: $order['ma_nguoi_dung'];
            $orderCode = $order['ma_don_hang_text'] ?: $orderId;
            $totalAmount = $order['tong_tien'];
            $trackingUrl = $this->getTrackingUrl($orderCode);
            
            $subject = "🎉 Đặt hàng thành công - Đơn hàng #{$orderCode}";
            
            $message = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='utf-8'></head>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1>🎉 Đặt Hàng Thành Công</h1>
                    </div>
                    <div style='background: #f8f9fa; padding: 30px;'>
                        <p>Xin chào <strong>{$customerName}</strong>,</p>
                        <p>Cảm ơn bạn đã đặt hàng! Đơn hàng của bạn đã được tiếp nhận thành công.</p>
                        <div style='background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;'>
                            <h3>Mã đơn hàng: #{$orderCode}</h3>
                            <p><strong>Tổng tiền:</strong> " . number_format($totalAmount, 0, ',', '.') . "₫</p>
                            <p><strong>Trạng thái:</strong> Đang chờ xử lý</p>
                        </div>
                        <p>Chúng tôi sẽ xử lý đơn hàng trong thời gian sớm nhất và thông báo cho bạn.</p>
                        <a href='{$trackingUrl}' style='display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0;'>Tra Cứu Đơn Hàng</a>
                        <p>Cảm ơn bạn đã tin tưởng và mua hàng!</p>
                    </div>
                    <div style='text-align: center; padding: 20px; color: #6c757d; font-size: 12px;'>
                        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                        <p>&copy; " . date('Y') . " {$this->fromName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendViaSMTP($toEmail, $subject, $message);
            
        } catch (Exception $e) {
            error_log("EmailService: Error sending order success email - " . $e->getMessage());
            return false;
        }
    }
}
