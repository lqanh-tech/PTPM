<?php

$sessionSecurityPath = __DIR__ . '/../../includes/session_security.php';
if (!class_exists('SessionSecurity') && file_exists($sessionSecurityPath)) {
    require_once $sessionSecurityPath;
}

if (!class_exists('SessionManager')) {
    class SessionManager
    {
        private static $started = false;
        private static $securityEnabled = true;

        public static function start()
        {
            if (self::$started || session_status() === PHP_SESSION_ACTIVE) {

                if (self::$securityEnabled && class_exists('SessionSecurity')) {
                    SessionSecurity::checkTimeout();
                    SessionSecurity::validateSession();
                    SessionSecurity::regenerateIfNeeded();
                }
                return true;
            }

            try {

                if (!headers_sent()) {

                    ini_set('session.cookie_httponly', 1);
                    ini_set('session.use_only_cookies', 1);
                    ini_set('session.cookie_samesite', 'Lax');
                    ini_set('session.use_strict_mode', 1);
                    
                    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                        ini_set('session.cookie_secure', 1);
                    }

                    session_start();
                    self::$started = true;
                    
                    if (self::$securityEnabled && class_exists('SessionSecurity')) {
                        SessionSecurity::checkTimeout();
                        SessionSecurity::validateSession();
                        SessionSecurity::regenerateIfNeeded();
                    }

                    return true;
                } else {
                    error_log("SessionManager: Cannot start session - headers already sent");
                    return false;
                }
            } catch (Exception $e) {
                error_log("SessionManager: Error starting session - " . $e->getMessage());
                return false;
            }
        }

        public static function isActive()
        {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        public static function isStarted()
        {
            return self::isActive();
        }

        public static function getId()
        {
            return self::isActive() ? session_id() : null;
        }

        public static function destroy()
        {
            if (self::isActive()) {

                if (self::$securityEnabled && class_exists('SessionSecurity')) {
                    SessionSecurity::destroy();
                } else {
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
                    
                    session_destroy();
                }
                self::$started = false;
            }
        }

        public static function regenerateId($deleteOldSession = true)
        {
            if (self::isActive()) {
                if (self::$securityEnabled && class_exists('SessionSecurity')) {
                    SessionSecurity::regenerate();
                    return true;
                }
                return session_regenerate_id($deleteOldSession);
            }
            return false;
        }
        
        public static function onLogin($userId, $username)
        {
            if (self::$securityEnabled && class_exists('SessionSecurity')) {
                SessionSecurity::onLogin($userId, $username);
            } else {
                self::regenerateId(true);
            }
        }
        
        public static function onLogout()
        {
            if (self::$securityEnabled && class_exists('SessionSecurity')) {
                SessionSecurity::onLogout();
            } else {
                self::destroy();
            }
        }
        
        public static function checkTimeout()
        {
            if (self::$securityEnabled && class_exists('SessionSecurity')) {
                return SessionSecurity::checkTimeout();
            }
            return true;
        }
        
        public static function getTimeRemaining()
        {
            if (self::$securityEnabled && class_exists('SessionSecurity')) {
                return SessionSecurity::getTimeRemaining();
            }
            return 1800;
        }

        public static function set($key, $value)
        {
            if (self::isActive()) {
                $_SESSION[$key] = $value;
                return true;
            }
            return false;
        }

        public static function get($key, $default = null)
        {
            if (self::isActive() && isset($_SESSION[$key])) {
                return $_SESSION[$key];
            }
            return $default;
        }

        public static function remove($key)
        {
            if (self::isActive() && isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
                return true;
            }
            return false;
        }

        public static function has($key)
        {
            return self::isActive() && isset($_SESSION[$key]);
        }

        public static function all()
        {
            if (self::isActive()) {
                return $_SESSION;
            }
            return [];
        }

        public static function regenerate($deleteOldSession = true)
        {
            return self::regenerateId($deleteOldSession);
        }
    }
}
