<?php

class AppConfig {
    private static $config = null;
    private static $environment = null;
    
    public static function init($configFile = null) {
        if (self::$config !== null) {
            return self::$config;
        }
        
        self::$environment = self::detectEnvironment();
        
        $baseConfig = self::loadBaseConfig();
        
        $envConfig = self::loadEnvironmentConfig(self::$environment);
        
        self::$config = array_merge($baseConfig, $envConfig);
        
        self::validateConfig();
        
        self::applyPhpConfig();
        
        return self::$config;
    }
    
    public static function get($key, $default = null) {
        if (self::$config === null) {
            self::init();
        }
        
        return self::getNestedValue(self::$config, $key, $default);
    }
    
    public static function getEnvironment() {
        return self::$environment ?? self::detectEnvironment();
    }
    
    public static function isDevelopment() {
        return self::getEnvironment() === 'development';
    }
    
    public static function isProduction() {
        return self::getEnvironment() === 'production';
    }
    
    private static function detectEnvironment() {

        if ($env = getenv('APP_ENV')) {
            return $env;
        }
        
        if (file_exists(__DIR__ . '/../../../../.env')) {
            $envVars = parse_ini_file(__DIR__ . '/../../../../.env');
            if (isset($envVars['APP_ENV'])) {
                return $envVars['APP_ENV'];
            }
        }
        
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, 'localhost') !== false || 
                strpos($host, '127.0.0.1') !== false ||
                strpos($host, '.local') !== false) {
                return 'development';
            }
        }
        
        return 'production';
    }
    
    private static function loadBaseConfig() {
        return [
            'app' => [
                'name' => 'Hệ Thống Quản Lý Bán Hàng',
                'version' => '2.0.0',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'charset' => 'UTF-8',
                'locale' => 'vi_VN'
            ],
            
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            ],
            
            'session' => [
                'name' => 'LEQUOCANH_SESSION',
                'lifetime' => 7200,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ],
            
            'security' => [
                'csrf_token_name' => '_token',
                'csrf_expire' => 3600,
                'password_min_length' => 8,
                'max_login_attempts' => 5,
                'lockout_duration' => 900,
                'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
                'max_file_size' => 5242880
            ],
            
            'cache' => [
                'enabled' => true,
                'default_ttl' => 3600,
                'max_size' => 1000,
                'cleanup_probability' => 0.1
            ],
            
            'logging' => [
                'enabled' => true,
                'max_files' => 30,
                'max_file_size' => 10485760,
                'log_queries' => false,
                'log_errors' => true
            ],
            
            'performance' => [
                'slow_query_threshold' => 0.1,
                'memory_limit' => '256M',
                'max_execution_time' => 30,
                'enable_compression' => true
            ],
            
            'api' => [
                'version' => 'v1',
                'rate_limit' => 1000,
                'jwt_secret' => 'change-this-in-production',
                'jwt_expire' => 3600,
                'cors_origins' => ['*'],
                'pagination_limit' => 50
            ]
        ];
    }
    
    private static function loadEnvironmentConfig($environment) {
        $configs = [
            'development' => [
                'app' => [
                    'debug' => true,
                    'url' => 'http://localhost/lequocanh'
                ],
                'database' => [
                    'name' => 'trainingdb',
                    'username' => 'root',
                    'password' => ''
                ],
                'session' => [
                    'secure' => false
                ],
                'logging' => [
                    'level' => 'debug',
                    'log_queries' => true
                ],
                'cache' => [
                    'enabled' => false
                ],
                'performance' => [
                    'slow_query_threshold' => 0.05
                ]
            ],
            
            'production' => [
                'app' => [
                    'debug' => false,
                    'url' => 'https://yourdomain.com'
                ],
                'database' => [
                    'name' => getenv('DB_NAME') ?: 'trainingdb',
                    'username' => getenv('DB_USER') ?: 'root',
                    'password' => getenv('DB_PASS') ?: ''
                ],
                'session' => [
                    'secure' => true,
                    'domain' => '.yourdomain.com'
                ],
                'logging' => [
                    'level' => 'error',
                    'log_queries' => false
                ],
                'security' => [
                    'csrf_expire' => 1800
                ],
                'api' => [
                    'jwt_secret' => getenv('JWT_SECRET') ?: bin2hex(random_bytes(32)),
                    'cors_origins' => ['https://yourdomain.com']
                ]
            ],
            
            'testing' => [
                'app' => [
                    'debug' => true
                ],
                'database' => [
                    'name' => 'trainingdb_test',
                    'username' => 'root',
                    'password' => ''
                ],
                'logging' => [
                    'level' => 'info'
                ],
                'cache' => [
                    'enabled' => false
                ]
            ]
        ];
        
        return $configs[$environment] ?? [];
    }
    
    private static function validateConfig() {
        $required = [
            'database.name',
            'database.username',
            'app.name',
            'session.name'
        ];
        
        foreach ($required as $key) {
            if (self::getNestedValue(self::$config, $key) === null) {
                throw new Exception("Required configuration key '$key' is missing");
            }
        }
        
        if (self::isProduction() && self::get('api.jwt_secret') === 'change-this-in-production') {
            throw new Exception("JWT secret must be changed in production environment");
        }
    }
    
    private static function applyPhpConfig() {

        date_default_timezone_set(self::get('app.timezone', 'UTC'));
        
        ini_set('memory_limit', self::get('performance.memory_limit', '256M'));
        
        set_time_limit(self::get('performance.max_execution_time', 30));
        
        if (self::isDevelopment()) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        }
        
        $sessionConfig = self::get('session');
        ini_set('session.name', $sessionConfig['name']);
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime']);
        ini_set('session.cookie_lifetime', $sessionConfig['lifetime']);
        ini_set('session.cookie_httponly', $sessionConfig['httponly']);
        ini_set('session.cookie_secure', $sessionConfig['secure']);
        ini_set('session.cookie_samesite', $sessionConfig['samesite']);
    }
    
    private static function getNestedValue($array, $key, $default = null) {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public static function dump() {
        if (self::isDevelopment()) {
            return self::$config;
        }
        
        $safe = self::$config;
        unset($safe['database']['password']);
        unset($safe['api']['jwt_secret']);
        
        return $safe;
    }
}