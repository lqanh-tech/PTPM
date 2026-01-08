<?php

class NotificationManager 
{
    private $adminEmail;
    private $adminPhone;
    private $webhookUrl;
    
    public function __construct() 
    {

        $this->adminEmail = 'admin@yourdomain.com';
        $this->adminPhone = '0123456789';
        $this->webhookUrl = 'https://hooks.slack.com/your-webhook';
    }
    
    public function notifyPaymentSuccess($transaction) 
    {
        $subject = "✅ Thanh toán thành công - " . number_format($transaction['amount']) . " VND";
        $message = $this->buildSuccessMessage($transaction);
        
        $this->sendEmail($subject, $message);
        
        $this->sendSMS("Bạn vừa nhận được " . number_format($transaction['amount']) . " VND từ MoMo. Order: " . $transaction['order_id']);
        
        $this->sendSlackNotification($subject, $message, 'good');
        
        $this->logNotification('SUCCESS', $transaction['order_id'], $message);
    }
    
    public function notifyPaymentFailed($transaction) 
    {
        $subject = "❌ Thanh toán thất bại - " . $transaction['order_id'];
        $message = $this->buildFailedMessage($transaction);
        
        $this->sendEmail($subject, $message);
        
        $this->logNotification('FAILED', $transaction['order_id'], $message);
    }
    
    public function sendDailySummary() 
    {
        $summary = $this->getDailySummary();
        $subject = "📊 Báo cáo giao dịch ngày " . date('d/m/Y');
        $message = $this->buildSummaryMessage($summary);
        
        $this->sendEmail($subject, $message);
        $this->logNotification('DAILY_SUMMARY', 'SYSTEM', $message);
    }
    
    private function buildSuccessMessage($transaction) 
    {
        return "
        <h2 style='color: #28a745;'>💰 Bạn vừa nhận được thanh toán!</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Thông tin giao dịch:</h3>
            <ul style='list-style: none; padding: 0;'>
                <li><strong>Mã đơn hàng:</strong> {$transaction['order_id']}</li>
                <li><strong>Số tiền:</strong> <span style='color: #28a745; font-size: 18px;'>" . number_format($transaction['amount']) . " VND</span></li>
                <li><strong>Thông tin:</strong> {$transaction['order_info']}</li>
                <li><strong>Mã giao dịch MoMo:</strong> {$transaction['trans_id']}</li>
                <li><strong>Thời gian:</strong> " . date('d/m/Y H:i:s', strtotime($transaction['created_at'])) . "</li>
            </ul>
        </div>
        
        <p>Tiền sẽ được chuyển vào tài khoản MoMo Business của bạn trong vòng 24h.</p>
        
        <div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;'>
            <p><strong>Lưu ý:</strong> Đây là thông báo tự động từ hệ thống thanh toán.</p>
        </div>
        ";
    }
    
    private function buildFailedMessage($transaction) 
    {
        return "
        <h2 style='color: #dc3545;'>⚠️ Giao dịch thanh toán thất bại</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Thông tin giao dịch:</h3>
            <ul style='list-style: none; padding: 0;'>
                <li><strong>Mã đơn hàng:</strong> {$transaction['order_id']}</li>
                <li><strong>Số tiền:</strong> " . number_format($transaction['amount']) . " VND</li>
                <li><strong>Thông tin:</strong> {$transaction['order_info']}</li>
                <li><strong>Lý do thất bại:</strong> {$transaction['message']}</li>
                <li><strong>Thời gian:</strong> " . date('d/m/Y H:i:s', strtotime($transaction['created_at'])) . "</li>
            </ul>
        </div>
        
        <p>Vui lòng kiểm tra và liên hệ khách hàng nếu cần thiết.</p>
        ";
    }
    
    private function sendEmail($subject, $message) 
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: MoMo Payment System <noreply@yourdomain.com>',
            'Reply-To: noreply@yourdomain.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        try {
            $result = mail($this->adminEmail, $subject, $message, implode("\r\n", $headers));
            if (!$result) {
                error_log("Failed to send email notification: $subject");
            }
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
        }
    }
    
    private function sendSMS($message) 
    {

        try {

            error_log("SMS Notification: $message");
            
        } catch (Exception $e) {
            error_log("SMS notification error: " . $e->getMessage());
        }
    }
    
    private function sendSlackNotification($title, $message, $color = 'good') 
    {
        if (empty($this->webhookUrl)) {
            return;
        }
        
        try {
            $payload = [
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => $title,
                        'text' => strip_tags($message),
                        'footer' => 'MoMo Payment System',
                        'ts' => time()
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            curl_exec($ch);
            curl_close($ch);
            
        } catch (Exception $e) {
            error_log("Slack notification error: " . $e->getMessage());
        }
    }
    
    private function getDailySummary() 
    {
        try {
            require_once '../administrator/elements_LQA/mPDO.php';
            $pdo = new mPDO();
            
            $today = date('Y-m-d');
            
            $successQuery = "SELECT COUNT(*) as count, SUM(amount) as total 
                           FROM momo_transactions 
                           WHERE DATE(created_at) = ? AND status = 'SUCCESS'";
            $success = $pdo->executeS($successQuery, [$today]);
            
            $failedQuery = "SELECT COUNT(*) as count 
                          FROM momo_transactions 
                          WHERE DATE(created_at) = ? AND status = 'FAILED'";
            $failed = $pdo->executeS($failedQuery, [$today]);
            
            return [
                'date' => $today,
                'success_count' => $success['count'] ?? 0,
                'success_amount' => $success['total'] ?? 0,
                'failed_count' => $failed['count'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Error getting daily summary: " . $e->getMessage());
            return null;
        }
    }
    
    private function buildSummaryMessage($summary) 
    {
        if (!$summary) {
            return "<p>Không thể tạo báo cáo do lỗi hệ thống.</p>";
        }
        
        return "
        <h2>📊 Báo cáo giao dịch ngày " . date('d/m/Y', strtotime($summary['date'])) . "</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Thống kê:</h3>
            <ul style='list-style: none; padding: 0;'>
                <li>✅ <strong>Giao dịch thành công:</strong> {$summary['success_count']} giao dịch</li>
                <li>💰 <strong>Tổng doanh thu:</strong> <span style='color: #28a745; font-size: 18px;'>" . number_format($summary['success_amount']) . " VND</span></li>
                <li>❌ <strong>Giao dịch thất bại:</strong> {$summary['failed_count']} giao dịch</li>
            </ul>
        </div>
        
        <p>Báo cáo được tạo tự động lúc " . date('H:i:s d/m/Y') . "</p>
        ";
    }
    
    private function logNotification($type, $orderId, $message) 
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'order_id' => $orderId,
            'message' => strip_tags($message)
        ];
        
        error_log("Notification Log: " . json_encode($logEntry));
    }
}
