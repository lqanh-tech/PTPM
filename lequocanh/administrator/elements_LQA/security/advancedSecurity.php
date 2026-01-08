<?php

class AdvancedSecurity {
    private static $instance = null;
    private $config;
    private $logger;
    private $rateLimiter;
    private $ipWhitelist = [];
    private $ipBlacklist = [];
    
    private function __construct() {
        $this->config = AppConfig::get('security');
        $this->logger = Logger::getInstance();
        $this->rateLimiter = new RateLimiter();
        $this->loadIPLists();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function middleware() {

        $this->checkIPRestrictions();
        
        $this->checkRateLimit();
        
        $this->setSecurityHeaders();
        
        $this->sanitizeInput();
        
        $this->detectSQLInjection();
        
        $this->detectXSS();
        
        $this->secureFileUploads();
        
        $this->enhanceSessionSecurity();
    }
    
    private function checkIPRestrictions() {
        $clientIP = $this->getClientIP();
        
        if (in_array($clientIP, $this->ipBlacklist)) {
            $this->logger->warning('Blocked IP attempted access', ['ip' => $clientIP]);
            $this->blockAccess('IP blocked');
        }
        
        if (!empty($this->ipWhitelist) && !in_array($clientIP, $this->ipWhitelist)) {
            $this->logger->warning('Non-whitelisted IP attempted access', ['ip' => $clientIP]);
            $this->blockAccess('IP not whitelisted');
        }
    }
    
    private function checkRateLimit() {
        $clientIP = $this->getClientIP();
        $identifier = $clientIP . '_' . ($_SESSION['user_id'] ?? 'anonymous');
        
        if (!$this->rateLimiter->attempt($identifier)) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $clientIP,
                'identifier' => $identifier
            ]);
            $this->blockAccess('Rate limit exceeded');
        }
    }
    
    private function setSecurityHeaders() {

        header('X-Frame-Options: DENY');
        
        header('X-Content-Type-Options: nosniff');
        
        header('X-XSS-Protection: 1; mode=block');
        
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://cdnjs.cloudflare.com; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";
        header("Content-Security-Policy: $csp");
        
        if (AppConfig::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    private function sanitizeInput() {

        foreach ($_GET as $key => $value) {
            $_GET[$key] = $this->sanitizeValue($value);
        }
        
        foreach ($_POST as $key => $value) {
            $_POST[$key] = $this->sanitizeValue($value);
        }
        
        foreach ($_COOKIE as $key => $value) {
            $_COOKIE[$key] = $this->sanitizeValue($value);
        }
    }
    
    private function sanitizeValue($value) {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }
        
        $value = str_replace("\0", '', $value);
        
        $value = trim($value);
        
        return $value;
    }
    
    private function detectSQLInjection() {
        $suspiciousPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b.*\bwhere\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bor\b.*1\s*=\s*1)/i',
            '/(\band\b.*1\s*=\s*1)/i',
            '/(\'.*\bor\b.*\')/i',
            '/(\-\-)/i',
            '/(\/\*.*\*\/)/i'
        ];
        
        $allInput = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $this->logger->critical('SQL Injection attempt detected', [
                            'ip' => $this->getClientIP(),
                            'parameter' => $key,
                            'value' => $value,
                            'pattern' => $pattern
                        ]);
                        $this->blockAccess('Malicious input detected');
                    }
                }
            }
        }
    }
    
    private function detectXSS() {
        $suspiciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<form\b/i',
            '/expression\s*\(/i',
            '/vbscript:/i'
        ];
        
        $allInput = array_merge($_GET, $_POST);
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $this->logger->warning('XSS attempt detected', [
                            'ip' => $this->getClientIP(),
                            'parameter' => $key,
                            'value' => substr($value, 0, 200),
                            'pattern' => $pattern
                        ]);
                        
                        $_GET[$key] = $this->cleanXSS($value);
                        $_POST[$key] = $this->cleanXSS($value);
                    }
                }
            }
        }
    }
    
    private function cleanXSS($input) {

        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        
        $input = preg_replace('/javascript:/i', '', $input);
        
        $input = preg_replace('/on\w+\s*=/i', '', $input);
        
        $input = preg_replace('/<(iframe|object|embed|form)\b[^>]*>/i', '', $input);
        
        return $input;
    }
    
    private function secureFileUploads() {
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $this->validateUploadedFile($file, $key);
                }
            }
        }
    }
    
    private function validateUploadedFile($file, $key) {

        $maxSize = $this->config['max_file_size'] ?? 5242880;
        if ($file['size'] > $maxSize) {
            $this->logger->warning('File upload size exceeded', [
                'file' => $file['name'],
                'size' => $file['size'],
                'max_size' => $maxSize
            ]);
            unset($_FILES[$key]);
            return;
        }
        
        $allowedTypes = $this->config['allowed_file_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            $this->logger->warning('Disallowed file type uploaded', [
                'file' => $file['name'],
                'extension' => $extension,
                'allowed' => $allowedTypes
            ]);
            unset($_FILES[$key]);
            return;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        
        if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
            $this->logger->warning('MIME type mismatch in uploaded file', [
                'file' => $file['name'],
                'expected_mime' => $allowedMimes[$extension] ?? 'unknown',
                'actual_mime' => $mimeType
            ]);
            unset($_FILES[$key]);
            return;
        }
        
        $content = file_get_contents($file['tmp_name']);
        if (strpos($content, '<?php') !== false || strpos($content, '<script') !== false) {
            $this->logger->critical('Malicious file upload attempt', [
                'file' => $file['name'],
                'ip' => $this->getClientIP()
            ]);
            unset($_FILES[$key]);
            $this->blockAccess('Malicious file detected');
        }
    }
    
    private function enhanceSessionSecurity() {

        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->getClientIP();
        
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $userAgent;
            $_SESSION['ip_address'] = $ipAddress;
        } else {
            if ($_SESSION['user_agent'] !== $userAgent || $_SESSION['ip_address'] !== $ipAddress) {
                $this->logger->warning('Possible session hijacking detected', [
                    'session_id' => session_id(),
                    'original_ip' => $_SESSION['ip_address'],
                    'current_ip' => $ipAddress,
                    'original_ua' => $_SESSION['user_agent'],
                    'current_ua' => $userAgent
                ]);
                
                session_destroy();
                $this->blockAccess('Session security violation');
            }
        }
    }
    
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function loadIPLists() {

        $this->ipWhitelist = $this->config['ip_whitelist'] ?? [];
        $this->ipBlacklist = $this->config['ip_blacklist'] ?? [];
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT ip_address FROM blocked_ips WHERE expires_at > NOW() OR expires_at IS NULL");
            $blockedIPs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $this->ipBlacklist = array_merge($this->ipBlacklist, $blockedIPs);
        } catch (Exception $e) {
            $this->logger->warning('Failed to load IP blacklist from database: ' . $e->getMessage());
        }
    }
    
    public function blockIP($ip, $duration = 3600) {
        try {
            $db = Database::getInstance()->getConnection();
            $expiresAt = date('Y-m-d H:i:s', time() + $duration);
            
            $stmt = $db->prepare("INSERT INTO blocked_ips (ip_address, expires_at, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE expires_at = ?");
            $stmt->execute([$ip, $expiresAt, $expiresAt]);
            
            $this->logger->info('IP blocked', ['ip' => $ip, 'duration' => $duration]);
        } catch (Exception $e) {
            $this->logger->error('Failed to block IP: ' . $e->getMessage());
        }
    }
    
    private function blockAccess($reason) {
        http_response_code(403);
        
        $this->logger->critical('Access blocked', [
            'reason' => $reason,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ]);
        
        $this->blockIP($this->getClientIP(), 900);
        
        echo json_encode([
            'error' => 'Access Denied',
            'message' => 'Your request has been blocked for security reasons.',
            'code' => 403
        ]);
        
        exit();
    }
    
    public function createBlockedIPsTable() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "CREATE TABLE IF NOT EXISTS blocked_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_expires (ip_address, expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $db->exec($sql);
        } catch (Exception $e) {
            $this->logger->error('Failed to create blocked_ips table: ' . $e->getMessage());
        }
    }
}

class RateLimiter {
    private $limits = [
        'default' => ['requests' => 100, 'window' => 3600],
        'login' => ['requests' => 5, 'window' => 900],
        'api' => ['requests' => 1000, 'window' => 3600]
    ];
    
    public function attempt($identifier, $type = 'default') {
        $limit = $this->limits[$type] ?? $this->limits['default'];
        $key = "rate_limit_{$type}_{$identifier}";
        
        $current = $this->getCount($key);
        
        if ($current >= $limit['requests']) {
            return false;
        }
        
        $this->incrementCount($key, $limit['window']);
        
        return true;
    }
    
    private function getCount($key) {

        $file = sys_get_temp_dir() . '/' . md5($key) . '.rate';
        
        if (!file_exists($file)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data || $data['expires'] < time()) {
            unlink($file);
            return 0;
        }
        
        return $data['count'];
    }
    
    private function incrementCount($key, $window) {
        $file = sys_get_temp_dir() . '/' . md5($key) . '.rate';
        $current = $this->getCount($key);
        
        $data = [
            'count' => $current + 1,
            'expires' => time() + $window
        ];
        
        file_put_contents($file, json_encode($data));
    }
}