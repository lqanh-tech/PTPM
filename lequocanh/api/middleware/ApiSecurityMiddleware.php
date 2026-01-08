<?php

class ApiSecurityMiddleware {
    
    private static $instance = null;
    private $config = [];
    
    private $validActions = [
        'cart' => ['add', 'remove', 'update', 'count', 'list'],
        'wishlist' => ['add', 'remove', 'check', 'count', 'list', 'toggle'],
        'product_reviews' => ['submit', 'list', 'check', 'helpful'],
        'review_management' => ['list', 'toggle_visibility', 'delete', 'reports', 'resolve_report'],
        'support_tickets' => ['create', 'user_list', 'admin_list', 'details', 'send_message', 'update_status', 'assign'],
        'report_review' => ['submit', 'my_reports'],
        'filter_products' => ['filter']
    ];
    
    private $allowedOrigins = [];
    
    public function __construct() {
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {

        $csrfEnabled = ($_ENV['CSRF_ENABLED'] ?? 'true') === 'true';
        
        $this->config = [
            'rate_limit' => (int)($_ENV['API_RATE_LIMIT'] ?? 100),
            'rate_window' => (int)($_ENV['API_RATE_WINDOW'] ?? 60),
            'csrf_enabled' => $csrfEnabled,
            'csrf_exempt_apis' => ['filter_products', 'get_filter_options', 'get_product_reviews'],
            'max_input_length' => 10000,
            'log_security_events' => true
        ];
        
        $baseUrl = $_ENV['BASE_URL'] ?? '';
        $this->allowedOrigins = [
            'http://localhost:20080',
            'http://localhost',
            'https://localhost',
            trim($baseUrl)
        ];
    }
    
    public function handle($apiName = '') {

        $this->setSecureHeaders();
        
        $this->validateCORS();
        
        $this->checkRateLimit();
        
        if ($apiName && isset($_REQUEST['action'])) {
            $this->validateAction($apiName, $_REQUEST['action']);
        }
        
        $this->sanitizeInputs();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($apiName, $this->config['csrf_exempt_apis'])) {

            if ($this->config['csrf_enabled']) {
                $this->validateCSRF();
            }
        }
    }
    
    public function setSecureHeaders() {

        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; img-src 'self' data: https:; font-src 'self' data: https:;");
            header_remove('X-Powered-By');
        }
    }
    
    public function validateCORS() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (!headers_sent()) {
            if (empty($origin) || in_array($origin, $this->allowedOrigins)) {

                if (!empty($origin)) {
                    header("Access-Control-Allow-Origin: $origin");
                } else {

                    header("Access-Control-Allow-Origin: " . ($this->allowedOrigins[0] ?? '*'));
                }
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
            } else {

                $this->logSecurityEvent('cors_violation', ['origin' => $origin]);
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    public function checkRateLimit() {
        $identifier = $this->getClientIdentifier();
        $key = 'rate_' . md5($identifier);
        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        $now = time();
        $window = $this->config['rate_window'];
        $limit = $this->config['rate_limit'];
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
        } else {
            $data = $_SESSION[$key];
            
            if ($now - $data['start'] > $window) {
                $_SESSION[$key] = ['count' => 1, 'start' => $now];
            } else {
                $_SESSION[$key]['count']++;
                
                if ($_SESSION[$key]['count'] > $limit) {
                    $this->logSecurityEvent('rate_limit_exceeded', ['identifier' => $identifier]);
                    $this->respondError('Too many requests. Please try again later.', 429);
                }
            }
        }
        
        if (!headers_sent()) {
            $remaining = max(0, $limit - ($_SESSION[$key]['count'] ?? 0));
            header("X-RateLimit-Limit: $limit");
            header("X-RateLimit-Remaining: $remaining");
            header("X-RateLimit-Reset: " . (($_SESSION[$key]['start'] ?? $now) + $window));
        }
    }
    
    public function validateAction($apiName, $action) {
        if (isset($this->validActions[$apiName])) {
            if (!in_array($action, $this->validActions[$apiName])) {
                $this->logSecurityEvent('invalid_action', [
                    'api' => $apiName,
                    'action' => $action
                ]);
                $this->respondError('Invalid action', 400);
            }
        }
    }
    
    public function sanitizeInputs() {

        foreach ($_GET as $key => $value) {
            $_GET[$key] = $this->sanitizeValue($value);
        }
        
        foreach ($_POST as $key => $value) {
            $_POST[$key] = $this->sanitizeValue($value);
        }
        
        $_REQUEST = array_merge($_GET, $_POST);
    }
    
    private function sanitizeValue($value) {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }
        
        $value = trim($value);
        if (strlen($value) > $this->config['max_input_length']) {
            $value = substr($value, 0, $this->config['max_input_length']);
        }
        
        $value = str_replace(chr(0), '', $value);
        
        return $value;
    }
    
    public function validateCSRF() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->logSecurityEvent('csrf_violation', [
                'provided_token' => substr($token, 0, 10) . '...'
            ]);
            $this->respondError('Invalid CSRF token', 403);
        }
    }
    
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    private function getClientIdentifier() {

        if (isset($_SESSION['USER'])) {
            return 'user:' . $_SESSION['USER'];
        }
        if (isset($_SESSION['ADMIN'])) {
            return 'admin:' . $_SESSION['ADMIN'];
        }
        
        return 'ip:' . $this->getClientIP();
    }
    
    private function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    private function logSecurityEvent($event, $details = []) {
        if (!$this->config['log_security_events']) {
            return;
        }
        
        $logDir = __DIR__ . '/../../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/security.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'details' => $details
        ];
        
        @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    private function respondError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function validateInt($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) {
            return null;
        }
        if ($min !== null && $value < $min) {
            return $min;
        }
        if ($max !== null && $value > $max) {
            return $max;
        }
        return $value;
    }
    
    public static function validateString($value, $maxLength = 1000, $allowHtml = false) {
        $value = trim($value);
        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }
        if (!$allowHtml) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
