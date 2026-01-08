<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/securityLogger.php';

class SecurityMiddleware
{
    private $db;
    private static $instance = null;
    private $logger;

    private static $rateLimits = [
        'admin' => 100,
        'manager1' => 30,
        'staff2' => 20,
        'default' => 10
    ];

    private static $sessionTimeouts = [
        'admin' => 120,
        'manager1' => 60,
        'staff2' => 60,
        'default' => 30
    ];

    public function __construct()
    {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->logger = SecurityLogger::getInstance();
        } catch (Exception $e) {

            error_log("SecurityMiddleware: Database connection failed - " . $e->getMessage());
            $this->db = null;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function checkStrictAccess($username, $module)
    {
        try {

            if (empty($username) || empty($module)) {
                SecurityLogger::logAccess($username, $module, false, "Empty username or module");
                return false;
            }

            if (!$this->checkSessionTimeout($username)) {
                SecurityLogger::logAccess($username, $module, false, "Session timeout");
                return false;
            }

            if (!$this->checkRateLimit($username)) {
                SecurityLogger::logAccess($username, $module, false, "Rate limit exceeded");
                return false;
            }

            if (!SecurityLogger::checkWhitelist($username, $module)) {
                SecurityLogger::logAccess($username, $module, false, "Not in whitelist");
                return false;
            }

            if (!$this->checkDatabasePermission($username, $module)) {
                SecurityLogger::logAccess($username, $module, false, "Database permission denied");
                return false;
            }

            if (!$this->checkIPWhitelist($username)) {
                SecurityLogger::logAccess($username, $module, false, "IP not whitelisted");
                return false;
            }

            if (!$this->checkWorkingHours($username, $module)) {
                SecurityLogger::logAccess($username, $module, false, "Outside working hours");
                return false;
            }

            SecurityLogger::logAccess($username, $module, true, "All security checks passed");
            return true;
        } catch (Exception $e) {

            error_log("SecurityMiddleware error: " . $e->getMessage());
            SecurityLogger::logAccess($username, $module, false, "Security middleware error: " . $e->getMessage());
            return false;
        }
    }

    private function checkSessionTimeout($username)
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }

        $timeout = self::$sessionTimeouts[$username] ?? self::$sessionTimeouts['default'];
        $timeoutSeconds = $timeout * 60;

        if (time() - $_SESSION['last_activity'] > $timeoutSeconds) {
            session_destroy();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    private function checkRateLimit($username)
    {
        $limit = self::$rateLimits[$username] ?? self::$rateLimits['default'];
        $key = "rate_limit_" . $username;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }

        $data = $_SESSION[$key];

        if (time() - $data['start_time'] > 60) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }

        if ($data['count'] >= $limit) {
            return false;
        }

        $_SESSION[$key]['count']++;
        return true;
    }

    private function checkDatabasePermission($username, $module)
    {

        if ($this->db === null) {
            return false;
        }

        try {

            if ($username === 'admin') {
                return true;
            }

            $sql = "SELECT COUNT(*) FROM NhanVien_PhanHeQuanLy nvph
                    JOIN PhanHeQuanLy ph ON nvph.idPhanHe = ph.idPhanHe
                    JOIN nhanvien nv ON nvph.idNhanVien = nv.idNhanVien
                    JOIN user u ON nv.iduser = u.iduser
                    WHERE u.username = ? AND ph.maPhanHe = ? AND ph.trangThai = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username, $module]);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {

            error_log("Database permission check error: " . $e->getMessage());
            return false;
        }
    }

    private function checkIPWhitelist($username)
    {

        return true;

    }

    private function checkWorkingHours($username, $module)
    {

        $sensitiveModules = [
            'nhanvienview',
            'roleview',
            'vaiTroView',
            'payment_config',
            'mtonkho'
        ];

        if (!in_array($module, $sensitiveModules)) {
            return true;
        }

        if ($username === 'admin') {
            return true;
        }

        $currentHour = (int)date('H');
        $currentDay = (int)date('N');

        if ($currentDay >= 6 || $currentHour < 8 || $currentHour > 18) {
            return false;
        }

        return true;
    }

    public function getSecurityMetrics()
    {
        return [
            'active_sessions' => $this->countActiveSessions(),
            'rate_limit_violations' => $this->countRateLimitViolations(),
            'failed_attempts_last_hour' => $this->countFailedAttempts(3600),
            'security_alerts_today' => SecurityLogger::getSecurityStats(1)['alerts'] ?? 0
        ];
    }

    private function countActiveSessions()
    {

        return 1;
    }

    private function countRateLimitViolations()
    {

        $violations = 0;
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'rate_limit_') === 0 && is_array($value)) {
                $limit = self::$rateLimits['default'];
                if ($value['count'] >= $limit) {
                    $violations++;
                }
            }
        }
        return $violations;
    }

    private function countFailedAttempts($timeWindow)
    {

        return SecurityLogger::getSecurityStats(1)['denied'] ?? 0;
    }
}
