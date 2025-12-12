<?php

/**
 * SessionManager - Safe session handling utility
 * Provides centralized session management across the application
 */

if (!class_exists('SessionManager')) {
    class SessionManager
    {
        private static $started = false;

        /**
         * Start session safely
         * Prevents multiple session_start() calls and handles session configuration
         */
        public static function start()
        {
            if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
                return true;
            }

            try {
                // Configure session settings
                if (!headers_sent()) {
                    // Set secure session parameters
                    ini_set('session.cookie_httponly', 1);
                    ini_set('session.use_only_cookies', 1);
                    ini_set('session.cookie_samesite', 'Lax');

                    // Start the session
                    session_start();
                    self::$started = true;

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

        /**
         * Check if session is active
         */
        public static function isActive()
        {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        /**
         * Alias for isActive() for backward compatibility
         */
        public static function isStarted()
        {
            return self::isActive();
        }

        /**
         * Get session ID
         */
        public static function getId()
        {
            return self::isActive() ? session_id() : null;
        }

        /**
         * Destroy session safely
         */
        public static function destroy()
        {
            if (self::isActive()) {
                session_destroy();
                self::$started = false;
            }
        }

        /**
         * Regenerate session ID
         */
        public static function regenerateId($deleteOldSession = true)
        {
            if (self::isActive()) {
                return session_regenerate_id($deleteOldSession);
            }
            return false;
        }

        /**
         * Set session value
         */
        public static function set($key, $value)
        {
            if (self::isActive()) {
                $_SESSION[$key] = $value;
                return true;
            }
            return false;
        }

        /**
         * Get session value
         */
        public static function get($key, $default = null)
        {
            if (self::isActive() && isset($_SESSION[$key])) {
                return $_SESSION[$key];
            }
            return $default;
        }

        /**
         * Remove session value
         */
        public static function remove($key)
        {
            if (self::isActive() && isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
                return true;
            }
            return false;
        }

        /**
         * Check if session key exists
         */
        public static function has($key)
        {
            return self::isActive() && isset($_SESSION[$key]);
        }

        /**
         * Get all session data
         */
        public static function all()
        {
            if (self::isActive()) {
                return $_SESSION;
            }
            return [];
        }

        /**
         * Regenerate session (alias for regenerateId)
         */
        public static function regenerate($deleteOldSession = true)
        {
            return self::regenerateId($deleteOldSession);
        }
    }
}
