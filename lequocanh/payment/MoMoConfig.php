<?php

/**
 * MoMo Payment Configuration
 * Cấu hình cho tích hợp thanh toán MoMo
 */

class MoMoConfig
{
    /**
     * Lấy Partner Code theo environment
     */
    public static function getPartnerCode()
    {
        // Load payment configuration from payment_config.php
        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['partner_code'] ?? 'MOMO';
    }

    /**
     * Lấy Access Key theo environment
     */
    public static function getAccessKey()
    {
        // Load payment configuration from payment_config.php
        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['access_key'] ?? 'F8BBA842ECF85';
    }

    /**
     * Lấy Secret Key theo environment
     */
    public static function getSecretKey()
    {
        // Load payment configuration from payment_config.php
        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['secret_key'] ?? 'K951B6PE1waDMi640xX08PD3vg6EkVlz';
    }

    /**
     * Lấy API Endpoint theo environment
     */
    public static function getEndpoint()
    {
        // Load payment configuration from payment_config.php
        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['endpoint'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create';
    }

    /**
     * Lấy Query API Endpoint theo environment
     */
    public static function getQueryEndpoint()
    {
        // Using the same base as create endpoint but replacing /create with /query
        $endpoint = self::getEndpoint();
        return str_replace('/create', '/query', $endpoint);
    }

    /**
     * Lấy cấu hình thanh toán từ hệ thống cấu hình trung tâm
     */
    private static function getPaymentConfig()
    {
        // Load payment configuration from the centralized config system
        if (class_exists('ConfigManager')) {
            $config = ConfigManager::getInstance();
            $paymentConfig = $config->getPaymentConfig('momo');
            
            // Fallback to direct file include if ConfigManager is not available
            if (empty($paymentConfig)) {
                $paymentConfig = self::loadPaymentConfigDirectly();
            }
            
            return $paymentConfig;
        } else {
            return self::loadPaymentConfigDirectly();
        }
    }

    /**
     * Tải cấu hình thanh toán trực tiếp từ file
     */
    private static function loadPaymentConfigDirectly()
    {
        $configPath = __DIR__ . '/../config/payment_config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            return $config['gateways']['momo'] ?? [];
        }
        
        // Fallback to environment variables
        return [
            'partner_code' => $_ENV['MOMO_PARTNER_CODE'] ?? 'MOMO',
            'access_key' => $_ENV['MOMO_ACCESS_KEY'] ?? 'F8BBA842ECF85',
            'secret_key' => $_ENV['MOMO_SECRET_KEY'] ?? 'K951B6PE1waDMi640xX08PD3vg6EkVlz',
            'endpoint' => $_ENV['MOMO_ENDPOINT'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create'
        ];
    }

    /**
     * Đọc file .env và lấy giá trị của một biến
     */
    private static function getEnvValue($key)
    {
        $envPath = __DIR__ . '/../../.env';
        
        if (!file_exists($envPath)) {
            return null;
        }
        
        $envContent = file_get_contents($envPath);
        
        // Tìm dòng có key này
        $pattern = '/^' . preg_quote($key, '/') . '\s*=\s*(.*)$/m';
        
        if (preg_match($pattern, $envContent, $matches)) {
            $value = trim($matches[1]);
            
            // Xóa comment nếu có
            $value = preg_replace('/\s*#.*$/', '', $value);
            
            // Xóa khoảng trắng thừa
            $value = trim($value);
            
            // Xóa quotes nếu có
            $value = trim($value, '"\'');
            
            return $value;
        }
        
        return null;
    }

    /**
     * Lấy base URL của website (tự động từ .env)
     */
    public static function getBaseUrl()
    {
        // 1. Ưu tiên: Đọc từ hằng số BASE_URL nếu đã được định nghĩa
        if (defined('BASE_URL')) {
            return rtrim(BASE_URL, '/');
        }
        
        // 2. Đọc từ file .env
        $baseUrlFromEnv = self::getEnvValue('BASE_URL');
        $useTunnel = self::getEnvValue('USE_CLOUDFLARE_TUNNEL');
        
        // Nếu có BASE_URL trong .env và USE_CLOUDFLARE_TUNNEL = true
        if (!empty($baseUrlFromEnv) && $useTunnel === 'true') {
            $baseUrl = rtrim($baseUrlFromEnv, '/');
            
            // Tự động thêm /lequocanh nếu chưa có
            if (strpos($baseUrl, '/lequocanh') === false) {
                $baseUrl .= '/lequocanh';
            }
            
            return $baseUrl;
        }
        
        // 3. Sử dụng ConfigManager nếu có
        if (class_exists('ConfigManager')) {
            $configUrl = ConfigManager::getInstance()->getBaseUrl();
            if (!empty($configUrl)) {
                return rtrim($configUrl, '/');
            }
        }
        
        // 4. Detect từ server variables
        $isOnTunnel = isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false
        );
        
        if ($isOnTunnel) {
            // Đang chạy trên tunnel - sử dụng URL hiện tại
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            return $protocol . '://' . $host . '/lequocanh';
        }
        
        // 5. Fallback: Localhost
        return 'http://localhost:8080/lequocanh';
    }



    /**
     * Lấy Return URL (trang người dùng sẽ được redirect sau khi thanh toán)
     */
    public static function getReturnUrl()
    {
        return self::getBaseUrl() . '/administrator/elements_LQA/mgiohang/momo_return.php';
    }

    /**
     * Lấy Notify URL (endpoint nhận thông báo từ MoMo)
     */
    public static function getNotifyUrl()
    {
        return self::getBaseUrl() . '/administrator/elements_LQA/mgiohang/momo_notify.php';
    }
}