<?php

/**
 * Configuration Manager
 * Centralized configuration loading and management
 */

class ConfigManager
{
    private static $instance = null;
    private $configs = [];
    private $configPath;

    private function __construct()
    {
        $this->configPath = __DIR__;
        $this->loadEnvironmentVariables();
        $this->loadConfigurations();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvironmentVariables()
    {
        // Load .env from project root
        $envPath = dirname(dirname(__DIR__)) . '/.env';

        if (file_exists($envPath) && class_exists('Dotenv\Dotenv')) {
            try {
                $dotenvClass = 'Dotenv\Dotenv';
                $dotenv = $dotenvClass::createImmutable(dirname(dirname(__DIR__)));
                $dotenv->load();
            } catch (Exception $e) {
                // Fallback to manual parsing if Dotenv class is not available
                $this->parseEnvFile($envPath);
            }
        } elseif (file_exists($envPath)) {
            // Manual parsing fallback
            $this->parseEnvFile($envPath);
        }
    }

    /**
     * Manual .env file parsing (fallback)
     */
    private function parseEnvFile($envPath)
    {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.+)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Load all configuration files
     */
    private function loadConfigurations()
    {
        $configFiles = [
            'app' => 'app.php',
            'database' => 'database.php',
            'logging' => 'logging.php',
            'payment' => 'payment_config.php',
            'local' => 'local_config.php'
        ];

        foreach ($configFiles as $key => $file) {
            $filePath = $this->configPath . '/' . $file;
            if (file_exists($filePath)) {
                $config = require $filePath;
                $this->configs[$key] = is_array($config) ? $config : [];
            }
        }

        // Apply local environment overrides
        $this->applyLocalOverrides();
    }

    /**
     * Apply local development overrides
     */
    private function applyLocalOverrides()
    {
        // Ưu tiên sử dụng USE_CLOUDFLARE_TUNNEL (tên rõ ràng hơn)
        $useCloudflare = filter_var($_ENV['USE_CLOUDFLARE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Fallback cho cấu hình cũ
        if (!$useCloudflare) {
            $useCloudflare = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        
        $forceNgrok = filter_var($_ENV['FORCE_NGROK'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($this->isLocalEnvironment() && !$forceNgrok && !$useCloudflare) {
            // Sử dụng cấu hình localhost khi:
            // - Đang chạy trên localhost
            // - KHÔNG bật USE_CLOUDFLARE_TUNNEL
            // - KHÔNG bật FORCE_NGROK
            if (isset($this->configs['app']['url']['base'])) {
                $this->configs['app']['url']['base'] = $this->configs['app']['url']['local'];
            }

            // Enable debug mode
            $this->configs['app']['app']['debug'] = true;

            // Adjust logging levels
            $this->configs['logging']['channels']['file']['level'] = 'debug';
        } elseif ($forceNgrok || $useCloudflare) {
            // Khi bật tunnel/ngrok, sử dụng BASE_URL từ .env
            if (isset($_ENV['BASE_URL'])) {
                $this->configs['app']['url']['base'] = $_ENV['BASE_URL'];
            }
        }
    }

    /**
     * Check if running in local environment
     */
    public function isLocalEnvironment()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return false;
        }

        $localHosts = ['localhost', '127.0.0.1', '::1'];
        $currentHost = $_SERVER['HTTP_HOST'];

        foreach ($localHosts as $host) {
            if (strpos($currentHost, $host) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get configuration value using dot notation
     * Example: get('database.connections.mysql.host')
     */
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->configs;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value using dot notation
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->configs;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Get all configurations
     */
    public function all()
    {
        return $this->configs;
    }

    /**
     * Get configuration for a specific section
     */
    public function section($section)
    {
        return $this->configs[$section] ?? [];
    }

    /**
     * Check if configuration key exists
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Get base URL based on environment
     */
    public function getBaseUrl()
    {
        // Ưu tiên sử dụng USE_CLOUDFLARE_TUNNEL
        $useCloudflare = filter_var($_ENV['USE_CLOUDFLARE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Fallback cho cấu hình cũ FORCE_TUNNEL
        if (!$useCloudflare) {
            $useCloudflare = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Kiểm tra xem có đang truy cập từ tunnel domain không
        $isOnTunnel = isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false || 
            strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false
        );
        
        // Logic quyết định sử dụng URL nào:
        // 1. Nếu USE_CLOUDFLARE_TUNNEL=true HOẶC đang truy cập từ tunnel -> dùng tunnel URL
        // 2. Ngược lại -> dùng localhost URL
        if ($useCloudflare || $isOnTunnel) {
            return $this->get('app.url.base', $_ENV['BASE_URL'] ?? 'http://localhost:20080/lequocanh');
        } else {
            return $this->get('app.url.local', 'http://localhost:20080/lequocanh');
        }
    }

    /**
     * Get database configuration with fallback
     */
    public function getDatabaseConfig()
    {
        $defaultConnection = $this->get('database.default', 'mysql');
        return $this->get("database.connections.$defaultConnection", []);
    }

    /**
     * Get payment gateway configuration
     */
    public function getPaymentConfig($gateway = null)
    {
        if ($gateway) {
            return $this->get("payment.gateways.$gateway", []);
        }

        return $this->get('payment.gateways', []);
    }

    /**
     * Merge old configuration format for backward compatibility
     */
    public function getLegacyConfig()
    {
        // For backward compatibility with existing code
        $baseUrl = $this->getBaseUrl();

        if (!defined('BASE_URL')) {
            define('BASE_URL', $baseUrl);
        }

        if (!defined('LOCAL_DEV')) {
            define('LOCAL_DEV', $this->isLocalEnvironment());
        }

        // Return payment config in old format
        $paymentConfig = $this->getPaymentConfig();
        $legacyPayment = [];

        if (isset($paymentConfig['momo'])) {
            $momo = $paymentConfig['momo'];
            $legacyPayment['momo'] = [
                'partner_code' => $momo['partner_code'],
                'access_key' => $momo['access_key'],
                'secret_key' => $momo['secret_key'],
                'endpoint' => $momo['endpoint'],
                'return_url' => $baseUrl . $momo['return_url'],
                'notify_url' => $baseUrl . $momo['notify_url']
            ];
        }

        if (isset($paymentConfig['bank_transfer'])) {
            $legacyPayment['bank_transfer'] = $paymentConfig['bank_transfer'];
        }

        return $legacyPayment;
    }
}

// Global configuration helper functions
if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        $configManager = ConfigManager::getInstance();

        if ($key === null) {
            return $configManager->all();
        }

        return $configManager->get($key, $default);
    }
}

if (!function_exists('base_url')) {
    function base_url()
    {
        return ConfigManager::getInstance()->getBaseUrl();
    }
}