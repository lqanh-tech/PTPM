<?php

class EmailService
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName;
    
    public function __construct()
    {
        $this->loadConfig();
    }
    
    private function loadConfig()
    {
        $envFile = __DIR__ . '/../../../../.env';
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
        
        $this->smtpHost = $config['MAIL_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = $config['MAIL_PORT'] ?? 587;
        $this->smtpUser = $config['MAIL_USERNAME'] ?? '';
        $this->smtpPass = $config['MAIL_PASSWORD'] ?? '';
        $this->fromEmail = $config['MAIL_FROM_ADDRESS'] ?? '';
        $this->fromName = $config['MAIL_FROM_NAME'] ?? 'LQA Shop';
    }
    
    public function send($toEmail, $subject, $htmlBody)
    {
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            error_log("EmailService: SMTP credentials not configured");
            return false;
        }
        
        try {
            $socket = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
            
            if (!$socket) {
                error_log("EmailService: Cannot connect to SMTP - $errstr ($errno)");
                return false;
            }
            
            fgets($socket, 515);
            
            fputs($socket, "EHLO localhost\r\n");
            $this->readMultilineResponse($socket);
            
            fputs($socket, "STARTTLS\r\n");
            fgets($socket, 515);
            
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            fputs($socket, "EHLO localhost\r\n");
            $this->readMultilineResponse($socket);
            
            fputs($socket, "AUTH LOGIN\r\n");
            fgets($socket, 515);
            
            fputs($socket, base64_encode($this->smtpUser) . "\r\n");
            fgets($socket, 515);
            
            fputs($socket, base64_encode($this->smtpPass) . "\r\n");
            $response = fgets($socket, 515);
            
            if (strpos($response, '235') === false) {
                error_log("EmailService: Authentication failed");
                fclose($socket);
                return false;
            }
            
            fputs($socket, "MAIL FROM: <{$this->fromEmail}>\r\n");
            fgets($socket, 515);
            
            fputs($socket, "RCPT TO: <$toEmail>\r\n");
            fgets($socket, 515);
            
            fputs($socket, "DATA\r\n");
            fgets($socket, 515);
            
            $emailData = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $emailData .= "To: <$toEmail>\r\n";
            $emailData .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $emailData .= "MIME-Version: 1.0\r\n";
            $emailData .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailData .= "\r\n";
            $emailData .= $htmlBody;
            $emailData .= "\r\n.\r\n";
            
            fputs($socket, $emailData);
            $response = fgets($socket, 515);
            
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return strpos($response, '250') !== false;
            
        } catch (Exception $e) {
            error_log("EmailService Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function readMultilineResponse($socket)
    {
        $response = '';
        while (true) {
            $line = fgets($socket, 515);
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    public function sendWelcomeEmail($email, $fullname, $username)
    {
        $subject = "🎉 Chào mừng bạn đến với LQA Shop!";
        $html = $this->getWelcomeEmailTemplate($fullname, $username);
        return $this->send($email, $subject, $html);
    }
    
    public function sendEmailUpdateNotification($newEmail, $fullname, $username)
    {
        $subject = "✅ Cập nhật email thành công - LQA Shop";
        $html = $this->getEmailUpdateTemplate($fullname, $username, $newEmail);
        return $this->send($newEmail, $subject, $html);
    }
    
    private function getEmailUpdateTemplate($fullname, $username, $newEmail)
    {
        $date = date('d/m/Y H:i:s');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                            <tr>
                                <td style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px 30px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>✅ Cập nhật email thành công!</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <p style='font-size: 18px; color: #333; margin: 0 0 20px 0;'>
                                        Xin chào <strong style='color: #28a745;'>{$fullname}</strong>,
                                    </p>
                                    <p style='font-size: 16px; color: #555; line-height: 1.6; margin: 0 0 20px 0;'>
                                        Email của tài khoản <strong>LQA Shop</strong> của bạn đã được cập nhật thành công!
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; margin: 25px 0;'>
                                        <tr>
                                            <td style='padding: 25px;'>
                                                <h3 style='color: #155724; margin: 0 0 15px 0; font-size: 16px;'>
                                                    📧 Thông tin cập nhật:
                                                </h3>
                                                <p style='margin: 0 0 10px 0; color: #155724; font-size: 15px;'>
                                                    <strong>Tên đăng nhập:</strong> {$username}
                                                </p>
                                                <p style='margin: 0 0 10px 0; color: #155724; font-size: 15px;'>
                                                    <strong>Email mới:</strong> {$newEmail}
                                                </p>
                                                <p style='margin: 0; color: #155724; font-size: 15px;'>
                                                    <strong>Thời gian:</strong> {$date}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; margin: 25px 0;'>
                                        <tr>
                                            <td style='padding: 20px;'>
                                                <p style='margin: 0; color: #856404; font-size: 14px;'>
                                                    <strong>⚠️ Lưu ý bảo mật:</strong><br>
                                                    Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ ngay với chúng tôi hoặc đổi mật khẩu tài khoản.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='font-size: 15px; color: #555; line-height: 1.6;'>
                                        Từ giờ, tất cả thông báo về đơn hàng và khuyến mãi sẽ được gửi đến email mới này.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color: #333; padding: 30px; text-align: center;'>
                                    <p style='color: #ffffff; margin: 0 0 10px 0; font-size: 16px; font-weight: bold;'>
                                        LQA Shop
                                    </p>
                                    <p style='color: #aaa; margin: 0; font-size: 13px;'>
                                        © 2025 LQA Shop. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
    
    private function getWelcomeEmailTemplate($fullname, $username)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                            <tr>
                                <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>🎉 Chào mừng đến với LQA Shop!</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <p style='font-size: 18px; color: #333; margin: 0 0 20px 0;'>
                                        Xin chào <strong style='color: #667eea;'>{$fullname}</strong>,
                                    </p>
                                    <p style='font-size: 16px; color: #555; line-height: 1.6; margin: 0 0 20px 0;'>
                                        Cảm ơn bạn đã đăng ký tài khoản tại <strong>LQA Shop</strong>! 
                                        Chúng tôi rất vui được chào đón bạn trở thành thành viên của cộng đồng mua sắm trực tuyến.
                                    </p>
                                    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f8f9fa; border-radius: 8px; margin: 25px 0;'>
                                        <tr>
                                            <td style='padding: 25px;'>
                                                <h3 style='color: #333; margin: 0 0 15px 0; font-size: 16px;'>
                                                    📋 Thông tin tài khoản của bạn:
                                                </h3>
                                                <p style='margin: 0; color: #555; font-size: 15px;'>
                                                    <strong>Tên đăng nhập:</strong> {$username}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='font-size: 16px; color: #333; margin: 0 0 15px 0;'>
                                        <strong>✨ Với tài khoản LQA Shop, bạn có thể:</strong>
                                    </p>
                                    <ul style='color: #555; font-size: 15px; line-height: 1.8; margin: 0 0 25px 0; padding-left: 20px;'>
                                        <li>Mua sắm hàng ngàn sản phẩm chất lượng</li>
                                        <li>Theo dõi đơn hàng dễ dàng</li>
                                        <li>Nhận thông báo khuyến mãi độc quyền</li>
                                        <li>Tích điểm và nhận ưu đãi hấp dẫn</li>
                                    </ul>
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='center' style='padding: 10px 0;'>
                                                <a href='#' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold;'>
                                                    🛒 Bắt đầu mua sắm ngay
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color: #333; padding: 30px; text-align: center;'>
                                    <p style='color: #ffffff; margin: 0 0 10px 0; font-size: 16px; font-weight: bold;'>
                                        LQA Shop
                                    </p>
                                    <p style='color: #aaa; margin: 0; font-size: 13px;'>
                                        © 2025 LQA Shop. All rights reserved.
                                    </p>
                                    <p style='color: #888; margin: 15px 0 0 0; font-size: 12px;'>
                                        Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email này.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}
