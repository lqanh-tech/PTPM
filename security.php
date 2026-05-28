<?php

declare(strict_types=1);

if (basename($_SERVER['PHP_SELF']) === 'security.php') {
    die('Direct access not permitted');
}

class Security
{
    /**
     * Set secure HTTP headers.
     */
    public static function setSecureHeaders(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // XSS Protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
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

        // Hide server info
        header_remove('X-Powered-By');

        // HTTPOnly cookies, Secure only on HTTPS
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }
    }

    /**
     * Sanitize user input for HTML output.
     */
    public static function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim((string) $data)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate CSRF token.
     */
    public static function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Validate CSRF token using timing-safe comparison.
     */
    public static function validateCSRFToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check rate limit for identifier (IP, user, etc.).
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $cacheDir = __DIR__ . '/cache/ratelimit';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Probabilistic cleanup: ~1% chance per request to avoid unbounded growth
        if (random_int(1, 100) === 1) {
            self::cleanupRateLimit($cacheDir, $timeWindow);
        }

        $key = 'rate_limit_' . md5($identifier);
        $file = $cacheDir . '/' . $key . '.json';
        $now = time();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $now - $data['start'] > $timeWindow) {
                $data = ['count' => 1, 'start' => $now];
            } elseif ($data && $data['count'] >= $maxAttempts) {
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
    private static function cleanupRateLimit(string $cacheDir, int $timeWindow): void
    {
        $files = glob($cacheDir . '/rate_limit_*.json');
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > $timeWindow * 2) {
                @unlink($file);
            }
        }
    }

    /**
     * Validate file upload.
     */
    public static function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        $errors = [];

        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed with error code: ' . $file['error'];
            return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }

        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedTypes)) {
                $errors[] = 'Invalid file type';
            }
        }

        // Check dangerous extensions
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'cgi', 'pl', 'exe', 'sh', 'bat', 'cmd'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $dangerousExtensions)) {
            $errors[] = 'Dangerous file extension detected';
        }

        return $errors;
    }

    /**
     * Validate request origin.
     */
    public static function validateOrigin(array $allowedOrigins = []): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (empty($allowedOrigins)) {
            return true;
        }

        return in_array($origin, $allowedOrigins);
    }

    /**
     * Log security event.
     */
    public static function logSecurityEvent(string $event, array $details = []): void
    {
        $logFile = __DIR__ . '/logs/security.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];

        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }

    /**
     * Generate secure random string.
     */
    public static function generateRandomString(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    /**
     * Hash password using bcrypt.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password against hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input for SQL queries - DEPRECATED.
     * Use prepared statements instead.
     *
     * @deprecated Use PDO prepared statements for SQL injection prevention.
     */
    public static function sanitizeSQL($value): string
    {
        // This method is intentionally left as a no-op.
        // Use prepared statements instead for SQL injection prevention.
        trigger_error('Security::sanitizeSQL() is deprecated. Use prepared statements instead.', E_USER_DEPRECATED);
        return trim((string) $value);
    }
}

Security::setSecureHeaders();
