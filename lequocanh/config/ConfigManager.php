<?php

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

    private function loadEnvironmentVariables()
    {

        $envPath = dirname(dirname(__DIR__)) . '/.env';

        if (file_exists($envPath) && class_exists('Dotenv\Dotenv')) {
            try {
                $dotenvClass = 'Dotenv\Dotenv';
                $dotenv = $dotenvClass::createImmutable(dirname(dirname(__DIR__)));
                $dotenv->load();
            } catch (Exception $e) {

                $this->parseEnvFile($envPath);
            }
        } elseif (file_exists($envPath)) {

            $this->parseEnvFile($envPath);
        }
    }

    private function parseEnvFile($envPath)
    {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (preg_match('/^(["\'])(.+)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

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

        $this->applyLocalOverrides();
    }

    private function applyLocalOverrides()
    {

        $useCloudflare = filter_var($_ENV['USE_CLOUDFLARE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (!$useCloudflare) {
            $useCloudflare = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        
        $forceNgrok = filter_var($_ENV['FORCE_NGROK'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($this->isLocalEnvironment() && !$forceNgrok && !$useCloudflare) {

            if (isset($this->configs['app']['url']['base'])) {
                $this->configs['app']['url']['base'] = $this->configs['app']['url']['local'];
            }

            $this->configs['app']['app']['debug'] = true;

            $this->configs['logging']['channels']['file']['level'] = 'debug';
        } elseif ($forceNgrok || $useCloudflare) {

            if (isset($_ENV['BASE_URL'])) {
                $this->configs['app']['url']['base'] = $_ENV['BASE_URL'];
            }
        }
    }

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

    public function all()
    {
        return $this->configs;
    }

    public function section($section)
    {
        return $this->configs[$section] ?? [];
    }

    public function has($key)
    {
        return $this->get($key) !== null;
    }

    public function getBaseUrl()
    {

        $useCloudflare = filter_var($_ENV['USE_CLOUDFLARE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (!$useCloudflare) {
            $useCloudflare = filter_var($_ENV['FORCE_TUNNEL'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        
        $isOnTunnel = isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false || 
            strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false
        );
        
        if ($useCloudflare || $isOnTunnel) {
            return $this->get('app.url.base', $_ENV['BASE_URL'] ?? 'http://localhost:20080/lequocanh');
        } else {
            return $this->get('app.url.local', 'http://localhost:20080/lequocanh');
        }
    }

    public function getDatabaseConfig()
    {
        $defaultConnection = $this->get('database.default', 'mysql');
        return $this->get("database.connections.$defaultConnection", []);
    }

    public function getPaymentConfig($gateway = null)
    {
        if ($gateway) {
            return $this->get("payment.gateways.$gateway", []);
        }

        return $this->get('payment.gateways', []);
    }

    public function getLegacyConfig()
    {

        $baseUrl = $this->getBaseUrl();

        if (!defined('BASE_URL')) {
            define('BASE_URL', $baseUrl);
        }

        if (!defined('LOCAL_DEV')) {
            define('LOCAL_DEV', $this->isLocalEnvironment());
        }

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