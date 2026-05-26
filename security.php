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
            "script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com",
            "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "connect-src 'self' https://test-payment.momo.vn https://dev-online-gateway.ghn.vn",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        header("Content-Security-Policy: " . implode('; ', $csp));
        
        // Ẩn thông tin server
        header_remove('X-Powered-By');
        
        // HTTPOnly cookies, Secure only on HTTPS
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }
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
        $cacheDir = __DIR__ . '/cache/ratelimit';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Probabilistic cleanup: ~1% chance per request to avoid unbounded growth
        if (random_int(1, 100) === 1) {
            self::cleanupRateLimit($cacheDir, $time_window);
        }

        $key = 'rate_limit_' . md5($identifier);
        $file = $cacheDir . '/' . $key . '.json';
        $now = time();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $now - $data['start'] > $time_window) {
                $data = ['count' => 1, 'start' => $now];
            } elseif ($data && $data['count'] >= $max_attempts) {
                return false;
            } elseif ($data) {
                $data['count']++;
            } else {
                $data = ['count' => 1, 'start' => $now];
            }
        } else {
            $data = ['count' => 1, 'start' => $now];
        }

        file_put_contents($file, json_encode($data));
        return true;
    }

    /**
     * Remove rate limit files older than the time window.
     */
    private static function cleanupRateLimit($cacheDir, $time_window) {
        $files = glob($cacheDir . '/rate_limit_*.json');
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > $time_window * 2) {
                @unlink($file);
            }
        }
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
    
    /**
     * @deprecated Use prepared statements instead. addslashes() does NOT prevent SQL injection.
     */
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
