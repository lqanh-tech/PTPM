<?php

class IntrusionDetector {

    private static $failedLoginAttempts = [];
    private static $ipBlacklist = [];
    private static $thresholds = [
        'failed_login_per_ip' => 5,
        'failed_login_time_window' => 300,
    ];

    public static function recordFailedLogin($ipAddress) {
        if (!isset(self::$failedLoginAttempts[$ipAddress])) {
            self::$failedLoginAttempts[$ipAddress] = [];
        }
        self::$failedLoginAttempts[$ipAddress][] = time();
        self::cleanOldFailedLogins($ipAddress);

        if (count(self::$failedLoginAttempts[$ipAddress]) > self::$thresholds['failed_login_per_ip']) {
            self::flagSuspiciousIp($ipAddress, 'Excessive failed login attempts');
        }
    }

    private static function cleanOldFailedLogins($ipAddress) {
        $currentTime = time();
        self::$failedLoginAttempts[$ipAddress] = array_filter(self::$failedLoginAttempts[$ipAddress], function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < self::$thresholds['failed_login_time_window'];
        });
    }

    public static function flagSuspiciousIp($ipAddress, $reason) {
        if (!in_array($ipAddress, self::$ipBlacklist)) {
            self::$ipBlacklist[] = $ipAddress;
            SecurityMonitor::logEvent('Suspicious IP Detected', ['ip_address' => $ipAddress, 'reason' => $reason]);
        }
    }

    public static function isSuspiciousIp($ipAddress) {
        return in_array($ipAddress, self::$ipBlacklist);
    }

    public static function getSuspiciousIps() {
        return self::$ipBlacklist;
    }

    public static function analyzeTrafficPatterns($trafficData) {

        if (isset($trafficData['requests_per_second']) && $trafficData['requests_per_second'] > 100) {
            SecurityMonitor::logEvent('Unusual Traffic Pattern', ['description' => 'High requests per second', 'rate' => $trafficData['requests_per_second']]);
            return true;
        }
        return false;
    }

    public static function reset() {
        self::$failedLoginAttempts = [];
        self::$ipBlacklist = [];
    }
}

require_once 'securityMonitor.php';
?>