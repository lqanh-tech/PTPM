<?php

class PerformanceMonitor {

    private static $logs = [];
    private static $startTime;

    public static function start() {
        self::$startTime = microtime(true);
    }

    public static function log($operationName) {
        if (self::$startTime === null) {
            return;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - self::$startTime;

        self::$logs[] = [
            'operation' => $operationName,
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::$startTime = null;
    }

    public static function getLogs() {
        return self::$logs;
    }

    public static function getSlowOperations($threshold = 1.0) {
        $slowOperations = [];
        foreach (self::$logs as $log) {
            if ($log['execution_time'] > $threshold) {
                $slowOperations[] = $log;
            }
        }
        return $slowOperations;
    }

    public static function saveLogsToFile($filePath = 'logs/performance.log') {
        $logContent = "";
        foreach (self::$logs as $log) {
            $logContent .= sprintf(
                "[%s] Operation: %s | Execution Time: %.4f seconds\n",
                $log['timestamp'],
                $log['operation'],
                $log['execution_time']
            );
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $logContent, FILE_APPEND);
    }
}
?>