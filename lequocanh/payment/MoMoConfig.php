<?php

class MoMoConfig
{

    public static function getPartnerCode()
    {

        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['partner_code'] ?? 'MOMO';
    }

    public static function getAccessKey()
    {

        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['access_key'] ?? 'F8BBA842ECF85';
    }

    public static function getSecretKey()
    {

        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['secret_key'] ?? 'K951B6PE1waDMi640xX08PD3vg6EkVlz';
    }

    public static function getEndpoint()
    {

        $paymentConfig = self::getPaymentConfig();
        return $paymentConfig['endpoint'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create';
    }

    public static function getQueryEndpoint()
    {

        $endpoint = self::getEndpoint();
        return str_replace('/create', '/query', $endpoint);
    }

    private static function getPaymentConfig()
    {

        if (class_exists('ConfigManager')) {
            $config = ConfigManager::getInstance();
            $paymentConfig = $config->getPaymentConfig('momo');
            
            if (empty($paymentConfig)) {
                $paymentConfig = self::loadPaymentConfigDirectly();
            }
            
            return $paymentConfig;
        } else {
            return self::loadPaymentConfigDirectly();
        }
    }

    private static function loadPaymentConfigDirectly()
    {
        $configPath = __DIR__ . '/../config/payment_config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            return $config['gateways']['momo'] ?? [];
        }
        
        return [
            'partner_code' => $_ENV['MOMO_PARTNER_CODE'] ?? 'MOMO',
            'access_key' => $_ENV['MOMO_ACCESS_KEY'] ?? 'F8BBA842ECF85',
            'secret_key' => $_ENV['MOMO_SECRET_KEY'] ?? 'K951B6PE1waDMi640xX08PD3vg6EkVlz',
            'endpoint' => $_ENV['MOMO_ENDPOINT'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create'
        ];
    }

    private static function getEnvValue($key)
    {
        $envPath = __DIR__ . '/../../.env';
        
        if (!file_exists($envPath)) {
            return null;
        }
        
        $envContent = file_get_contents($envPath);
        
        $pattern = '/^' . preg_quote($key, '/') . '\s*=\s*(.*)$/m';
        
        if (preg_match($pattern, $envContent, $matches)) {
            $value = trim($matches[1]);
            
            $value = preg_replace('/\s*#.*$/', '', $value);
            
            $value = trim($value);
            
            $value = trim($value, '"\'');
            
            return $value;
        }
        
        return null;
    }

    public static function getBaseUrl()
    {

        if (defined('BASE_URL')) {
            return rtrim(BASE_URL, '/');
        }
        
        $baseUrlFromEnv = self::getEnvValue('BASE_URL');
        $useTunnel = self::getEnvValue('USE_CLOUDFLARE_TUNNEL');
        
        if (!empty($baseUrlFromEnv) && $useTunnel === 'true') {
            $baseUrl = rtrim($baseUrlFromEnv, '/');
            
            if (strpos($baseUrl, '/lequocanh') === false) {
                $baseUrl .= '/lequocanh';
            }
            
            return $baseUrl;
        }
        
        if (class_exists('ConfigManager')) {
            $configUrl = ConfigManager::getInstance()->getBaseUrl();
            if (!empty($configUrl)) {
                return rtrim($configUrl, '/');
            }
        }
        
        $isOnTunnel = isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false
        );
        
        if ($isOnTunnel) {

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            return $protocol . '://' . $host . '/lequocanh';
        }
        
        return 'http://localhost:8080/lequocanh';
    }

    public static function getReturnUrl()
    {
        return self::getBaseUrl() . '/administrator/elements_LQA/mgiohang/momo_return.php';
    }

    public static function getNotifyUrl()
    {
        return self::getBaseUrl() . '/administrator/elements_LQA/mgiohang/momo_notify.php';
    }
}