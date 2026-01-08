<?php

class UserActivityTracker {

    private static $activities = [];

    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_activity'])) {
            $_SESSION['user_activity'] = [];
        }
        self::$activities = &$_SESSION['user_activity'];

        if (!isset($_SESSION['session_start_time'])) {
            $_SESSION['session_start_time'] = time();
            self::logActivity('Session Start', ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
        }
    }

    public static function logActivity($action, $details = []) {
        $activity = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? 'Guest',
            'action' => $action,
            'page' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'details' => $details
        ];
        self::$activities[] = $activity;

    }

    public static function getSessionActivities() {
        return self::$activities;
    }

    public static function getSessionDuration() {
        if (isset($_SESSION['session_start_time'])) {
            return time() - $_SESSION['session_start_time'];
        }
        return 0;
    }

    public static function clearSessionActivities() {
        self::$activities = [];
        $_SESSION['user_activity'] = [];
    }

    public static function analyzeUserBehavior() {

        $pageViews = 0;
        foreach (self::$activities as $activity) {
            if ($activity['action'] === 'Page View') {
                $pageViews++;
            }
        }
        return ['page_views_in_session' => $pageViews];
    }
}
?>