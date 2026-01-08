<?php

class MoMoConfig {

    const ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/create';

    const QUERY_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/query';

    const CONFIRM_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/confirm';

    const PARTNER_CODE = 'MOMO';
    const ACCESS_KEY = 'F8BBA842ECF85';
    const SECRET_KEY = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';
    
    const RETURN_URL = 'http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php';
    const NOTIFY_URL = 'http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_notify.php';
    
    const REQUEST_TYPE = 'payWithATM';
    
    public static function createSignature($rawData) {
        return hash_hmac('sha256', $rawData, self::SECRET_KEY);
    }
    
    public static function generateRequestId() {
        return time() . '_' . uniqid();
    }
    
    public static function generateOrderId() {
        return 'ORDER_' . time() . '_' . rand(1000, 9999);
    }
    
    public static function validateSignature($data, $signature) {
        $rawData = '';
        ksort($data);
        foreach ($data as $key => $value) {
            if ($key !== 'signature') {
                $rawData .= $key . '=' . $value . '&';
            }
        }
        $rawData = rtrim($rawData, '&');
        
        $expectedSignature = hash_hmac('sha256', $rawData, self::SECRET_KEY);
        return $signature === $expectedSignature;
    }
    
    public static function logTransaction($type, $data) {
        $logFile = __DIR__ . '/../logs/momo_transactions.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'data' => $data
        ];
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {

}
?>