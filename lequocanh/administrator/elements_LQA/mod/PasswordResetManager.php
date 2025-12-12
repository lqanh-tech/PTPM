<?php
/**
 * Password Reset Manager
 * Quản lý việc reset mật khẩu qua email
 * 
 * Flow:
 * 1. User nhập email/username
 * 2. Hệ thống tạo token và gửi email
 * 3. User click link trong email
 * 4. User nhập mật khẩu mới
 * 5. Hệ thống cập nhật mật khẩu
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/PasswordHelper.php';

class PasswordResetManager
{
    private $db;
    private $tokenExpiry = 3600; // Token hết hạn sau 1 giờ (3600 giây)
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }
    
    /**
     * Tạo bảng password_resets nếu chưa tồn tại
     */
    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL,
            expires_at TIMESTAMP NOT NULL,
            ip_address VARCHAR(45),
            INDEX idx_token (token),
            INDEX idx_email (email),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->exec($sql);
    }
    
    /**
     * Tìm user theo email hoặc username
     * 
     * @param string $identifier Email hoặc username
     * @return object|false User object hoặc false
     */
    public function findUser($identifier)
    {
        $identifier = trim($identifier);
        
        // Tìm theo email trước
        $sql = "SELECT * FROM user WHERE email = ? OR username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $identifier]);
        
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Tạo token reset password
     * 
     * @param int $userId User ID
     * @param string $email Email
     * @return string Token
     */
    public function createResetToken($userId, $email)
    {
        // Xóa các token cũ của user này
        $this->invalidateOldTokens($userId);
        
        // Tạo token mới (64 ký tự hex = 32 bytes random)
        $token = bin2hex(random_bytes(32));
        
        // Tính thời gian hết hạn
        $expiresAt = date('Y-m-d H:i:s', time() + $this->tokenExpiry);
        
        // Lấy IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Lưu vào database
        $sql = "INSERT INTO password_resets (user_id, email, token, expires_at, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $email, $token, $expiresAt, $ipAddress]);
        
        return $token;
    }
    
    /**
     * Xóa các token cũ của user
     */
    private function invalidateOldTokens($userId)
    {
        $sql = "DELETE FROM password_resets WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    /**
     * Xác thực token
     * 
     * @param string $token Token cần xác thực
     * @return object|false Reset record hoặc false
     */
    public function validateToken($token)
    {
        $sql = "SELECT pr.*, u.username, u.hoten 
                FROM password_resets pr
                JOIN user u ON pr.user_id = u.iduser
                WHERE pr.token = ? 
                AND pr.used_at IS NULL 
                AND pr.expires_at > NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Đặt lại mật khẩu
     * 
     * @param string $token Token
     * @param string $newPassword Mật khẩu mới
     * @return bool Success
     */
    public function resetPassword($token, $newPassword)
    {
        // Validate token
        $resetRecord = $this->validateToken($token);
        
        if (!$resetRecord) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Hash mật khẩu mới
            $hashedPassword = PasswordHelper::hash($newPassword);
            
            // Cập nhật mật khẩu
            $sql = "UPDATE user SET password = ? WHERE iduser = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hashedPassword, $resetRecord->user_id]);
            
            // Đánh dấu token đã sử dụng
            $sql = "UPDATE password_resets SET used_at = NOW() WHERE token = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            
            $this->db->commit();
            
            // Log
            error_log("Password reset successful for user ID: " . $resetRecord->user_id);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Password reset failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gửi email reset password
     * 
     * @param string $email Email người nhận
     * @param string $token Token
     * @param string $username Username
     * @return bool Success
     */
    public function sendResetEmail($email, $token, $username)
    {
        // Load cấu hình từ .env
        $envPath = __DIR__ . '/../../../../.env';
        $config = $this->loadEnvConfig($envPath);
        
        // Tạo reset URL
        $baseUrl = $this->getBaseUrl();
        $resetUrl = $baseUrl . "/lequocanh/administrator/reset_password.php?token=" . $token;
        
        // Nội dung email
        $subject = "Đặt lại mật khẩu - LQA Shop";
        
        $htmlBody = $this->getEmailTemplate($username, $resetUrl);
        
        // Gửi email
        return $this->sendEmail($email, $subject, $htmlBody, $config);
    }
    
    /**
     * Load cấu hình từ file .env
     */
    private function loadEnvConfig($envPath)
    {
        $config = [
            'MAIL_HOST' => 'smtp.gmail.com',
            'MAIL_PORT' => 587,
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_FROM_ADDRESS' => '',
            'MAIL_FROM_NAME' => 'LQA Shop',
            'BASE_URL' => 'http://localhost:8081'
        ];
        
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (isset($config[$key])) {
                    $config[$key] = $value;
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Lấy base URL từ cấu hình
     */
    private function getBaseUrl()
    {
        $envPath = __DIR__ . '/../../../../.env';
        $config = $this->loadEnvConfig($envPath);
        
        $useCloudflare = strtolower($config['USE_CLOUDFLARE_TUNNEL'] ?? 'false') === 'true';
        
        if ($useCloudflare && !empty($config['BASE_URL'])) {
            return rtrim($config['BASE_URL'], '/');
        }
        
        return 'http://localhost:8081';
    }
    
    /**
     * Template email HTML
     */
    private function getEmailTemplate($username, $resetUrl)
    {
        $expiryMinutes = $this->tokenExpiry / 60;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .button { display: inline-block; background: #0d6efd; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .button:hover { background: #0b5ed7; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Đặt Lại Mật Khẩu</h1>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$username}</strong>,</p>
                    <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>LQA Shop</strong>.</p>
                    <p>Nhấn vào nút bên dưới để đặt lại mật khẩu:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$resetUrl}' class='button'>Đặt Lại Mật Khẩu</a>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Lưu ý:</strong>
                        <ul>
                            <li>Link này sẽ hết hạn sau <strong>{$expiryMinutes} phút</strong></li>
                            <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này</li>
                            <li>Không chia sẻ link này với bất kỳ ai</li>
                        </ul>
                    </div>
                    
                    <p>Nếu nút không hoạt động, copy và paste link sau vào trình duyệt:</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 5px; font-size: 12px;'>{$resetUrl}</p>
                </div>
                <div class='footer'>
                    <p>Email này được gửi tự động từ LQA Shop</p>
                    <p>© " . date('Y') . " LQA Shop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Gửi email sử dụng PHPMailer hoặc mail() function
     */
    private function sendEmail($to, $subject, $htmlBody, $config)
    {
        // Tìm vendor/autoload.php - thử nhiều đường dẫn
        $possiblePaths = [
            __DIR__ . '/../../../../vendor/autoload.php',
            $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php',
            '/var/www/html/vendor/autoload.php'
        ];
        
        $phpmailerPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $phpmailerPath = $path;
                break;
            }
        }
        
        if ($phpmailerPath) {
            require_once $phpmailerPath;
            
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $config['MAIL_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['MAIL_USERNAME'];
                $mail->Password = $config['MAIL_PASSWORD'];
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)$config['MAIL_PORT'];
                $mail->CharSet = 'UTF-8';
                
                // Recipients
                $mail->setFrom($config['MAIL_FROM_ADDRESS'], $config['MAIL_FROM_NAME']);
                $mail->addAddress($to);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = strip_tags($htmlBody);
                
                $mail->send();
                error_log("Password reset email sent to: " . $to);
                return true;
                
            } catch (Exception $e) {
                error_log("PHPMailer Error: " . $e->getMessage());
                return false;
            }
        }
        
        // Fallback to mail() function
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $config['MAIL_FROM_NAME'] . ' <' . $config['MAIL_FROM_ADDRESS'] . '>',
            'Reply-To: ' . $config['MAIL_FROM_ADDRESS']
        ];
        
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    /**
     * Dọn dẹp các token hết hạn
     */
    public function cleanupExpiredTokens()
    {
        $sql = "DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Kiểm tra rate limit (chống spam)
     * 
     * @param string $email Email
     * @param int $maxAttempts Số lần tối đa trong 1 giờ
     * @return bool True nếu được phép, False nếu bị giới hạn
     */
    public function checkRateLimit($email, $maxAttempts = 3)
    {
        $sql = "SELECT COUNT(*) FROM password_resets 
                WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        
        $count = $stmt->fetchColumn();
        
        return $count < $maxAttempts;
    }
}
