<?php

class SessionSecurity {
    
    const SESSION_TIMEOUT = 1800;
    
    const REGENERATE_INTERVAL = 900;
    
    public static function init() {

        if (session_status() === PHP_SESSION_NONE) {

            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
        }
        
        self::checkTimeout();
        
        self::validateSession();
        
        self::regenerateIfNeeded();
    }
    
    public static function checkTimeout() {
        if (isset($_SESSION['LAST_ACTIVITY'])) {
            $inactive = time() - $_SESSION['LAST_ACTIVITY'];
            
            if ($inactive > self::SESSION_TIMEOUT) {

                self::destroy();
                return false;
            }
        }
        
        $_SESSION['LAST_ACTIVITY'] = time();
        return true;
    }
    
    public static function validateSession() {
        $currentFingerprint = self::generateFingerprint();
        
        if (!isset($_SESSION['FINGERPRINT'])) {
            $_SESSION['FINGERPRINT'] = $currentFingerprint;
        } elseif ($_SESSION['FINGERPRINT'] !== $currentFingerprint) {

            self::logSecurityEvent('session_hijacking_attempt', [
                'stored_fingerprint' => substr($_SESSION['FINGERPRINT'], 0, 20),
                'current_fingerprint' => substr($currentFingerprint, 0, 20)
            ]);
            
            self::destroy();
            return false;
        }
        
        return true;
    }
    
    private static function generateFingerprint() {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',

        ];
        
        return hash('sha256', implode('|', $data));
    }
    
    public static function regenerateIfNeeded() {
        if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        } elseif (time() - $_SESSION['CREATED'] > self::REGENERATE_INTERVAL) {

            self::regenerate();
        }
    }
    
    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['CREATED'] = time();
        }
    }
    
    public static function onLogin($userId, $username) {

        self::regenerate();
        
        $_SESSION['LOGIN_TIME'] = time();
        $_SESSION['LOGIN_IP'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        unset($_SESSION['csrf_token']);
        
        self::logSecurityEvent('user_login', [
            'user_id' => $userId,
            'username' => $username
        ]);
    }
    
    public static function onLogout() {
        $username = $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? 'unknown';
        
        self::logSecurityEvent('user_logout', [
            'username' => $username
        ]);
        
        self::destroy();
    }
    
    public static function destroy() {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    private static function logSecurityEvent($event, $details = []) {

        $possibleLogDirs = [
            __DIR__ . '/../logs',
            dirname(__DIR__) . '/logs',
            '/var/www/html/lequocanh/logs',
            'D:/PHP_WS/lequocanh/logs'
        ];
        
        $logDir = null;
        foreach ($possibleLogDirs as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                $logDir = $dir;
                break;
            }
        }
        
        if (!$logDir) {
            $logDir = __DIR__ . '/../logs';
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/session_security.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => substr(session_id(), 0, 10) . '...',
            'details' => $details
        ];
        
        @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['USER']) || isset($_SESSION['ADMIN']);
    }
    
    public static function getTimeRemaining() {
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            return self::SESSION_TIMEOUT;
        }
        
        $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
        return max(0, self::SESSION_TIMEOUT - $elapsed);
    }
}
