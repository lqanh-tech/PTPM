<?php

class SecurityMonitor {

    private static $securityEvents = [];

    public static function logEvent($eventType, $details = []) {
        $event = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'details' => $details
        ];
        self::$securityEvents[] = $event;

        file_put_contents('logs/security_events.log', json_encode($event) . "\n", FILE_APPEND);
    }

    public static function logFailedLoginAttempt($username) {
        self::logEvent('Failed Login', ['username' => $username]);
    }

    public static function logCsrfAttempt($referer, $tokenProvided, $tokenExpected) {
        self::logEvent('CSRF Attack Attempt', [
            'referer' => $referer,
            'token_provided' => $tokenProvided,
            'token_expected' => $tokenExpected
        ]);
    }

    public static function logSuspiciousActivity($activityDescription, $userId = 'Unknown') {
        self::logEvent('Suspicious User Activity', [
            'description' => $activityDescription,
            'user_id' => $userId
        ]);
    }

    public static function logFileAccess($filePath, $accessType, $userId = 'Unknown') {
        self::logEvent('File Access', [
            'file_path' => $filePath,
            'access_type' => $accessType,
            'user_id' => $userId
        ]);
    }

    public static function getSecurityEvents() {
        return self::$securityEvents;
    }

    public static function clearEvents() {
        self::$securityEvents = [];
    }
}
?>