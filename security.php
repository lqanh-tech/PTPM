<?php
/**
 * Security Helper Functions
 * Include this file in bootstrap.php for global security
 */

// Prevent direct access (only if accessed directly via URL)
if (basename($_SERVER['PHP_SELF']) === 'security.php') {
    die('Direct access not permitted');
}

class Security {
    
    /**
     * Set secure HTTP headers
     */
    public static function setSecureHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (adjust as needed)
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; img-src 'self' data: https:; font-src 'self' data: https:;");
        
        // Remove PHP version from headers
        header_remove('X-Powered-By');
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting (simple implementation)
     */
    public static function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window passed
        if ($now - $data['start'] > $time_window) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $max_attempts) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed with error code: ' . $file['error'];
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check file type
        if (!empty($allowed_types)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_types)) {
                $errors[] = 'Invalid file type';
            }
        }
        
        // Check for double extensions
        if (preg_match('/\.(php|phtml|php3|php4|php5|phps|cgi|pl|exe|sh)$/i', $file['name'])) {
            $errors[] = 'Dangerous file extension detected';
        }
        
        return $errors;
    }
    
    /**
     * Prevent SQL injection (use with PDO prepared statements)
     */
    public static function sanitizeSQL($value) {
        return addslashes(strip_tags(trim($value)));
    }
    
    /**
     * Check if request is from allowed origin (for API)
     */
    public static function validateOrigin($allowed_origins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (empty($allowed_origins)) {
            return true; // No restriction
        }
        
        return in_array($origin, $allowed_origins);
    }
    
    /**
     * Log security events
     */
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

// Auto-apply secure headers
Security::setSecureHeaders();
