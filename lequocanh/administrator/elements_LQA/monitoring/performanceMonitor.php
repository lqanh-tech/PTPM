<?php

class RealtimePerformanceMonitor {

    private static $metrics = [];

    public static function startOperation($operationName) {
        self::$metrics[$operationName]['start_time'] = microtime(true);
    }

    public static function endOperation($operationName) {
        if (!isset(self::$metrics[$operationName]['start_time'])) {
            return;
        }
        self::$metrics[$operationName]['end_time'] = microtime(true);
        self::$metrics[$operationName]['duration'] = self::$metrics[$operationName]['end_time'] - self::$metrics[$operationName]['start_time'];
    }

    public static function getOperationDuration($operationName) {
        return self::$metrics[$operationName]['duration'] ?? null;
    }

    public static function getMemoryUsage() {
        return memory_get_usage();
    }

    public static function getPeakMemoryUsage() {
        return memory_get_peak_usage();
    }

    public static function getCpuUsage() {

        return rand(0, 100);
    }

    public static function getAllMetrics() {
        return self::$metrics;
    }

    public static function resetMetrics() {
        self::$metrics = [];
    }

    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
?>