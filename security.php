<?php

if (basename($_SERVER['PHP_SELF']) === 'security.php') {
    die('Direct access not permitted');
}

class Security {
    
    public static function setSecureHeaders() {
        // Ngăn clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Ngăn MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy - chặt chẽ hơn
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "connect-src 'self' https://test-payment.momo.vn https://dev-online-gateway.ghn.vn",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        header("Content-Security-Policy: " . implode('; ', $csp));
        
        // Ẩn thông tin server
        header_remove('X-Powered-By');
        
        // HTTPOnly và Secure cookies
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }
    
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'] ?? '';
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        if ($now - $data['start'] > $time_window) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        if ($data['count'] >= $max_attempts) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
    
    public static function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
        $errors = [];
        
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed with error code: ' . $file['error'];
            return $errors;
        }
        
        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        if (!empty($allowed_types)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_types)) {
                $errors[] = 'Invalid file type';
            }
        }
        
        if (preg_match('/\.(php|phtml|php3|php4|php5|phps|cgi|pl|exe|sh)$/i', $file['name'])) {
            $errors[] = 'Dangerous file extension detected';
        }
        
        return $errors;
    }
    
    public static function sanitizeSQL($value) {
        return addslashes(strip_tags(trim($value)));
    }
    
    public static function validateOrigin($allowed_origins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (empty($allowed_origins)) {
            return true;
        }
        
        return in_array($origin, $allowed_origins);
    }
    
    public static function logSecurityEvent($event, $details = []) {
        $log_file = __DIR__ . '/logs/security.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        file_put_contents($log_file, json_encode($log_entry) . PHP_EOL, FILE_APPEND);
    }
}

Security::setSecureHeaders();
