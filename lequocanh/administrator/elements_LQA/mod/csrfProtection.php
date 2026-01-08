<?php

class CSRFProtection {
    const TOKEN_NAME = 'csrf_token';
    const TOKEN_EXPIRY = 3600;
    
    public static function generateToken() {

        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::TOKEN_NAME]) || 
            !isset($_SESSION[self::TOKEN_NAME . '_time']) ||
            (time() - $_SESSION[self::TOKEN_NAME . '_time']) > self::TOKEN_EXPIRY) {
            
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_NAME . '_time'] = time();
            
            if (class_exists('Logger')) {
                Logger::debug("CSRF token generated");
            }
        }
        
        return $_SESSION[self::TOKEN_NAME];
    }
    
    public static function validateToken($token) {

        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::TOKEN_NAME]) || !isset($_SESSION[self::TOKEN_NAME . '_time'])) {
            if (class_exists('Logger')) {
                Logger::warning("CSRF token validation failed - no token in session");
            }
            return false;
        }
        
        if ((time() - $_SESSION[self::TOKEN_NAME . '_time']) > self::TOKEN_EXPIRY) {
            if (class_exists('Logger')) {
                Logger::warning("CSRF token validation failed - token expired");
            }
            return false;
        }
        
        $isValid = hash_equals($_SESSION[self::TOKEN_NAME], $token);
        
        if (class_exists('Logger')) {
            if ($isValid) {
                Logger::debug("CSRF token validation successful");
            } else {
                Logger::warning("CSRF token validation failed - token mismatch");
            }
        }
        
        return $isValid;
    }
    
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function getMetaTag() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    public static function validateRequest($request = null) {
        if ($request === null) {
            $request = $_REQUEST;
        }
        
        $token = $request[self::TOKEN_NAME] ?? '';
        return self::validateToken($token);
    }
    
    public static function requireValidToken($request = null) {
        if (!self::validateRequest($request)) {
            if (class_exists('Logger')) {
                Logger::error("CSRF protection triggered - invalid token", [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
                ]);
            }
            throw new Exception("CSRF token validation failed");
        }
    }
    
    public static function getAjaxScript() {
        $token = self::generateToken();
        return "
        <script>

        (function() {
            var csrfToken = '" . addslashes($token) . "';
            
            if (typeof jQuery !== 'undefined') {
                jQuery.ajaxSetup({
                    beforeSend: function(xhr, settings) {
                        if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                            xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                        }
                    }
                });
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                var forms = document.querySelectorAll('form');
                forms.forEach(function(form) {
                    if (form.method.toLowerCase() === 'post') {
                        var csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '" . self::TOKEN_NAME . "';
                        csrfInput.value = csrfToken;
                        form.appendChild(csrfInput);
                    }
                });
            });
        })();
        </script>";
    }
    
    public static function regenerateToken() {

        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[self::TOKEN_NAME . '_time'] = time();
        
        if (class_exists('Logger')) {
            Logger::info("CSRF token regenerated");
        }
    }
    
    public static function clearToken() {

        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::TOKEN_NAME]);
        unset($_SESSION[self::TOKEN_NAME . '_time']);
        
        if (class_exists('Logger')) {
            Logger::debug("CSRF token cleared");
        }
    }
}