<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Email Service - Gửi email thông báo đơn hàng
 */
class EmailService
{
    public function __construct()
    {
        // PHPMailer will be loaded when needed
    }
    
    /**
     * Gửi email xác nhận đơn hàng
     */
    public function sendOrderConfirmation(string $toEmail, string $customerName, array $order): bool
    {
        try {
            $subject = "Xác nhận đơn hàng #{$order['ma_don_hang_text']} - LQA Shop";
            
            $html = $this->buildOrderConfirmationHTML($customerName, $order);
            
            return $this->send($toEmail, $subject, $html);
            
        } catch (Exception $e) {
            error_log("EmailService::sendOrderConfirmation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gửi email thông báo trạng thái đơn hàng
     */
    public function sendOrderStatusUpdate(string $toEmail, string $customerName, array $order, string $newStatus): bool
    {
        try {
            $statusText = match($newStatus) {
                'approved' => 'đã được duyệt',
                'delivered' => 'đang được giao',
                'completed' => 'đã hoàn tất',
                'cancelled' => 'đã bị hủy',
                default => 'đã được cập nhật'
            };
            
            $subject = "Đơn hàng #{$order['ma_don_hang_text']} {$statusText} - LQA Shop";
            
            $html = $this->buildStatusUpdateHTML($customerName, $order, $newStatus, $statusText);
            
            return $this->send($toEmail, $subject, $html);
            
        } catch (Exception $e) {
            error_log("EmailService::sendOrderStatusUpdate error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build HTML cho email xác nhận đơn hàng
     */
    private function buildOrderConfirmationHTML(string $customerName, array $order): string
    {
        $items = $order['items'] ?? [];
        $itemsHTML = '';
        
        foreach ($items as $item) {
            $itemsHTML .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['tenhanghoa']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['so_luong']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>" . number_format($item['gia'], 0, ',', '.') . "₫</td>
            </tr>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #eee; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #666; }
                .info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .total { font-size: 24px; font-weight: bold; color: #e74c3c; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #f8f9fa; padding: 10px; text-align: left; }
                .btn { display: inline-block; background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛍️ Cảm ơn bạn đã đặt hàng!</h1>
                    <p>Đơn hàng của bạn đã được tiếp nhận</p>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$customerName}</strong>,</p>
                    <p>Cảm ơn bạn đã mua sắm tại LQA Shop! Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.</p>
                    
                    <div class='info-box'>
                        <p><strong>Mã đơn hàng:</strong> #{$order['ma_don_hang_text']}</p>
                        <p><strong>Ngày đặt:</strong> " . date('d/m/Y H:i', strtotime($order['ngay_tao'])) . "</p>
                        <p><strong>Phương thức thanh toán:</strong> {$order['phuong_thuc_thanh_toan']}</p>
                        <p><strong>Địa chỉ giao hàng:</strong> {$order['dia_chi_giao_hang']}</p>
                    </div>
                    
                    <h3>Chi tiết đơn hàng</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th style='text-align: center;'>SL</th>
                                <th style='text-align: right;'>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHTML}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='2' style='padding: 10px; text-align: right;'><strong>Tổng cộng:</strong></td>
                                <td style='padding: 10px; text-align: right;' class='total'>" . number_format($order['tong_tien'], 0, ',', '.') . "₫</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <p style='margin-top: 20px;'>Chúng tôi sẽ thông báo cho bạn khi đơn hàng được giao.</p>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php?id={$order['id']}' class='btn'>
                            Xem chi tiết đơn hàng
                        </a>
                    </p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " LQA Shop. All rights reserved.</p>
                    <p>Email: support@lqashop.com | Hotline: 1900 xxxx</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Build HTML cho email cập nhật trạng thái
     */
    private function buildStatusUpdateHTML(string $customerName, array $order, string $newStatus, string $statusText): string
    {
        $statusIcon = match($newStatus) {
            'approved' => '✅',
            'delivered' => '🚚',
            'completed' => '🎉',
            'cancelled' => '❌',
            default => '📋'
        };
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #27ae60, #219653); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #eee; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #666; }
                .info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .btn { display: inline-block; background: #27ae60; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$statusIcon} Đơn hàng {$statusText}</h1>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>{$customerName}</strong>,</p>
                    <p>Đơn hàng <strong>#{$order['ma_don_hang_text']}</strong> của bạn {$statusText}.</p>
                    
                    <div class='info-box'>
                        <p><strong>Mã đơn hàng:</strong> #{$order['ma_don_hang_text']}</p>
                        <p><strong>Tổng tiền:</strong> " . number_format($order['tong_tien'], 0, ',', '.') . "₫</p>
                        <p><strong>Trạng thái mới:</strong> {$statusText}</p>
                    </div>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php?id={$order['id']}' class='btn'>
                            Xem chi tiết đơn hàng
                        </a>
                    </p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " LQA Shop. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Gửi email sử dụng PHPMailer hoặc mail()
     */
    private function send(string $to, string $subject, string $html): bool
    {
        // Try PHPMailer first
        $phpmailerPath = __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        
        if (file_exists($phpmailerPath)) {
            return $this->sendWithPHPMailer($to, $subject, $html);
        }
        
        // Fallback to mail()
        return $this->sendWithMail($to, $subject, $html);
    }
    
    /**
     * Gửi bằng PHPMailer
     */
    private function sendWithPHPMailer(string $to, string $subject, string $html): bool
    {
        try {
            require_once __DIR__ . '/../../../vendor/autoload.php';
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = 'tls';
            $mail->Port = $_ENV['MAIL_PORT'] ?? 587;
            
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@lqashop.com', $_ENV['MAIL_FROM_NAME'] ?? 'LQA Shop');
            $mail->addAddress($to);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gửi bằng mail() - fallback
     */
    private function sendWithMail(string $to, string $subject, string $html): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: LQA Shop <noreply@lqashop.com>'
        ];
        
        return mail($to, $subject, $html, implode("\r\n", $headers));
    }
}