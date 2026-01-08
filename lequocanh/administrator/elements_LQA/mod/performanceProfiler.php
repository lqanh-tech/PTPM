<?php

class PerformanceProfiler {

    private static $markers = [];
    private static $profiles = [];

    public static function start($name) {
        self::$markers[$name] = microtime(true);
    }

    public static function stop($name) {
        if (!isset(self::$markers[$name])) {
            return;
        }

        $endTime = microtime(true);
        $duration = $endTime - self::$markers[$name];

        if (!isset(self::$profiles[$name])) {
            self::$profiles[$name] = [
                'total_duration' => 0,
                'calls' => 0,
                'min_duration' => PHP_INT_MAX,
                'max_duration' => 0,
            ];
        }

        self::$profiles[$name]['total_duration'] += $duration;
        self::$profiles[$name]['calls']++;
        self::$profiles[$name]['min_duration'] = min(self::$profiles[$name]['min_duration'], $duration);
        self::$profiles[$name]['max_duration'] = max(self::$profiles[$name]['max_duration'], $duration);

        unset(self::$markers[$name]);
    }

    public static function getProfiles() {

        foreach (self::$profiles as $name => $data) {
            if ($data['calls'] > 0) {
                self::$profiles[$name]['average_duration'] = $data['total_duration'] / $data['calls'];
            } else {
                self::$profiles[$name]['average_duration'] = 0;
            }
        }
        return self::$profiles;
    }

    public static function reset() {
        self::$markers = [];
        self::$profiles = [];
    }

    public static function saveToFile($filePath = 'logs/profiler.log') {
        $logContent = "Profiling Report - " . date('Y-m-d H:i:s') . "\n";
        $logContent .= str_repeat('=', 40) . "\n";

        $profiles = self::getProfiles();
        foreach ($profiles as $name => $data) {
            $logContent .= sprintf(
                "Operation: %s | Calls: %d | Total: %.4f s | Avg: %.4f s | Min: %.4f s | Max: %.4f s\n",
                $name,
                $data['calls'],
                $data['total_duration'],
                $data['average_duration'],
                $data['min_duration'],
                $data['max_duration']
            );
        }
        $logContent .= str_repeat('=', 40) . "\n\n";

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $logContent, FILE_APPEND);
    }
}
?>