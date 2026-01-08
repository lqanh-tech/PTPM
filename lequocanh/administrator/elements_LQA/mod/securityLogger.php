<?php

class SecurityLogger
{
    private static $logFile;
    private static $alertFile;
    private static $instance = null;

    private static $adminWhitelist = ['admin'];
    
    private static $basicModules = [
        'userprofile',
        'userUpdateProfile',
        'thongbao'
    ];

    private static $sensitiveModules = [
        'nhanvienview',
        'roleview',
        'vaiTroView',
        'danhSachVaiTroView',
        'payment_config',
        'mtonkho',
        'mphieunhap'
    ];

    public function __construct()
    {
        self::$logFile = __DIR__ . "/security_access.log";
        self::$alertFile = __DIR__ . "/security_alerts.log";

        if (!file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, "=== SECURITY ACCESS LOG STARTED ===" . PHP_EOL);
        }
        if (!file_exists(self::$alertFile)) {
            file_put_contents(self::$alertFile, "=== SECURITY ALERTS LOG STARTED ===" . PHP_EOL);
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function logAccess($username, $module, $hasAccess, $reason = "", $additionalInfo = [])
    {
        $logger = self::getInstance();

        $timestamp = date("Y-m-d H:i:s");
        $ip = self::getClientIP();
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "unknown";
        $sessionId = session_id() ?? "no-session";
        $referer = $_SERVER["HTTP_REFERER"] ?? "direct";

        $logData = [
            'timestamp' => $timestamp,
            'username' => $username,
            'module' => $module,
            'access' => $hasAccess ? 'GRANTED' : 'DENIED',
            'reason' => $reason,
            'ip' => $ip,
            'session_id' => $sessionId,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'additional' => $additionalInfo
        ];

        $logEntry = sprintf(
            "[%s] USER: %s | MODULE: %s | ACCESS: %s | REASON: %s | IP: %s | SESSION: %s | REFERER: %s%s",
            $timestamp,
            $username,
            $module,
            $hasAccess ? 'GRANTED' : 'DENIED',
            $reason,
            $ip,
            $sessionId,
            $referer,
            PHP_EOL
        );

        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

        self::checkAndAlert($username, $module, $hasAccess, $logData);

        return $logData;
    }

    public static function checkWhitelist($username, $module)
    {

        if (in_array($username, self::$adminWhitelist) || $username === 'admin') {
            return true;
        }
        
        if (in_array($module, self::$basicModules)) {
            return true;
        }
        
        try {

            if (!class_exists('Database')) {
                $dbPaths = [
                    __DIR__ . '/database.php',
                    '../../elements_LQA/mod/database.php',
                    './elements_LQA/mod/database.php'
                ];
                foreach ($dbPaths as $path) {
                    if (file_exists($path)) {
                        require_once $path;
                        break;
                    }
                }
            }
            
            if (!class_exists('Database')) {
                error_log("SecurityLogger: Database class not found");
                return true;
            }
            
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT COUNT(*) FROM NhanVien_PhanHeQuanLy nvph
                    JOIN PhanHeQuanLy ph ON nvph.idPhanHe = ph.idPhanHe
                    JOIN nhanvien nv ON nvph.idNhanVien = nv.idNhanVien
                    JOIN user u ON nv.iduser = u.iduser
                    WHERE u.username = ? AND ph.maPhanHe = ? AND ph.trangThai = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $module]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("SecurityLogger checkWhitelist error: " . $e->getMessage());

            return false;
        }
    }
    
    public static function getUserAllowedModules($username)
    {

        if (in_array($username, self::$adminWhitelist) || $username === 'admin') {
            return ['*'];
        }
        
        $allowedModules = self::$basicModules;
        
        try {

            if (!class_exists('Database')) {
                $dbPaths = [
                    __DIR__ . '/database.php',
                    '../../elements_LQA/mod/database.php',
                    './elements_LQA/mod/database.php'
                ];
                foreach ($dbPaths as $path) {
                    if (file_exists($path)) {
                        require_once $path;
                        break;
                    }
                }
            }
            
            if (!class_exists('Database')) {
                return $allowedModules;
            }
            
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT ph.maPhanHe FROM NhanVien_PhanHeQuanLy nvph
                    JOIN PhanHeQuanLy ph ON nvph.idPhanHe = ph.idPhanHe
                    JOIN nhanvien nv ON nvph.idNhanVien = nv.idNhanVien
                    JOIN user u ON nv.iduser = u.iduser
                    WHERE u.username = ? AND ph.trangThai = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$username]);
            
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $allowedModules[] = $row->maPhanHe;
            }
        } catch (Exception $e) {
            error_log("SecurityLogger getUserAllowedModules error: " . $e->getMessage());
        }
        
        return array_unique($allowedModules);
    }

    private static function checkAndAlert($username, $module, $hasAccess, $logData)
    {
        $alerts = [];

        if ($hasAccess && !self::checkWhitelist($username, $module)) {
            $alerts[] = "UNAUTHORIZED_ACCESS: User $username accessed $module without whitelist permission";
        }

        if ($hasAccess && in_array($module, self::$sensitiveModules)) {
            $alerts[] = "SENSITIVE_MODULE_ACCESS: User $username accessed sensitive module $module";
        }

        $suspiciousIPs = ['127.0.0.1'];

        if (!$hasAccess) {
            $failedAttempts = self::countRecentFailedAttempts($username, $module);
            if ($failedAttempts >= 3) {
                $alerts[] = "MULTIPLE_FAILED_ATTEMPTS: User $username failed to access $module $failedAttempts times";
            }
        }

        $currentHour = (int)date('H');
        if ($hasAccess && ($currentHour < 8 || $currentHour > 18)) {
            $alerts[] = "OFF_HOURS_ACCESS: User $username accessed $module at $currentHour:00";
        }

        foreach ($alerts as $alert) {
            self::writeAlert($alert, $logData);
        }
    }

    private static function writeAlert($alertMessage, $logData)
    {
        $timestamp = $logData['timestamp'];
        $alertEntry = sprintf(
            "[%s] 🚨 SECURITY ALERT: %s | USER: %s | MODULE: %s | IP: %s%s",
            $timestamp,
            $alertMessage,
            $logData['username'],
            $logData['module'],
            $logData['ip'],
            PHP_EOL
        );

        file_put_contents(self::$alertFile, $alertEntry, FILE_APPEND | LOCK_EX);

    }

    private static function countRecentFailedAttempts($username, $module)
    {
        if (!file_exists(self::$logFile)) {
            return 0;
        }

        $logContent = file_get_contents(self::$logFile);
        $lines = explode(PHP_EOL, $logContent);

        $failedCount = 0;
        $timeLimit = time() - 300;

        foreach (array_reverse($lines) as $line) {
            if (empty($line)) continue;

            if (
                strpos($line, "USER: $username") !== false &&
                strpos($line, "MODULE: $module") !== false &&
                strpos($line, "ACCESS: DENIED") !== false
            ) {

                if (preg_match('/\[([\d\-\s:]+)\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $timeLimit) {
                        $failedCount++;
                    } else {
                        break;
                    }
                }
            }
        }

        return $failedCount;
    }

    private static function getClientIP()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    public static function getSecurityStats($days = 7)
    {
        if (!file_exists(self::$logFile)) {
            return [];
        }

        $logContent = file_get_contents(self::$logFile);
        $lines = explode(PHP_EOL, $logContent);

        $stats = [
            'total_access' => 0,
            'granted' => 0,
            'denied' => 0,
            'users' => [],
            'modules' => [],
            'alerts' => 0
        ];

        $timeLimit = time() - ($days * 24 * 3600);

        foreach ($lines as $line) {
            if (empty($line) || strpos($line, 'USER:') === false) continue;

            if (preg_match('/\[([\d\-\s:]+)\]/', $line, $matches)) {
                $logTime = strtotime($matches[1]);
                if ($logTime < $timeLimit) continue;
            }

            $stats['total_access']++;

            if (strpos($line, 'ACCESS: GRANTED') !== false) {
                $stats['granted']++;
            } else {
                $stats['denied']++;
            }

            if (preg_match('/USER: (\w+)/', $line, $matches)) {
                $user = $matches[1];
                $stats['users'][$user] = ($stats['users'][$user] ?? 0) + 1;
            }

            if (preg_match('/MODULE: (\w+)/', $line, $matches)) {
                $module = $matches[1];
                $stats['modules'][$module] = ($stats['modules'][$module] ?? 0) + 1;
            }
        }

        if (file_exists(self::$alertFile)) {
            $alertContent = file_get_contents(self::$alertFile);
            $alertLines = explode(PHP_EOL, $alertContent);
            foreach ($alertLines as $line) {
                if (strpos($line, '🚨') !== false) {
                    $stats['alerts']++;
                }
            }
        }

        return $stats;
    }
}

function logSecurityAccess($username, $module, $hasAccess, $reason = "")
{
    return SecurityLogger::logAccess($username, $module, $hasAccess, $reason);
}
