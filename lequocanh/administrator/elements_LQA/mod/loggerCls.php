<?php

class Logger {

    const DEBUG = 1;
    const INFO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const CRITICAL = 5;

    private static $logLevel = self::INFO;
    private static $logFile = null;
    private static $logToFile = true;
    private static $logToErrorLog = true;
    private static $enabled = true;
    private static $instance = null;

    private function __construct() {

        if (self::$logFile === null) {
            self::$logFile = __DIR__ . '/../../../logs/application.log';
            
            $logDir = dirname(self::$logFile);
            if (!file_exists($logDir)) {

                @mkdir($logDir, 0755, true);
            }

            if (!is_dir($logDir) || !is_writable($logDir)) {
                self::$logToFile = false;

                error_log("Logger Error: Log directory '{$logDir}' is not writable. File logging is disabled.");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    public static function configure($config = []) {
        if (isset($config['logLevel'])) {
            self::$logLevel = $config['logLevel'];
        }
        
        if (isset($config['logFile'])) {
            self::$logFile = $config['logFile'];
        }
        
        if (isset($config['logToFile'])) {
            self::$logToFile = $config['logToFile'];
        }
        
        if (isset($config['logToErrorLog'])) {
            self::$logToErrorLog = $config['logToErrorLog'];
        }
        
        if (isset($config['enabled'])) {
            self::$enabled = $config['enabled'];
        }
    }

    public static function setLevel($level) {
        self::$logLevel = $level;
    }

    public static function getLevel() {
        return self::$logLevel;
    }

    public static function setEnabled($enabled) {
        self::$enabled = $enabled;
    }

    public static function isEnabled() {
        return self::$enabled;
    }

    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }

    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }

    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }

    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }

    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
    }

    private static function log($level, $message, $context = []) {

        if (!self::$enabled) {
            return;
        }
        
        if ($level < self::$logLevel) {
            return;
        }

        self::getInstance();

        $levelName = self::getLevelName($level);
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]) ? $backtrace[1] : $backtrace[0];
        $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $line = isset($caller['line']) ? $caller['line'] : 0;
        
        $timestamp = date('Y-m-d H:i:s');
        $contextString = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $logMessage = "[$timestamp] [$levelName] [$file:$line] $message$contextString";
        
        if (self::$logToFile) {
            self::logToFile($logMessage);
        }
        
        if (self::$logToErrorLog) {
            error_log($logMessage);
        }
    }

    private static function logToFile($message) {
        try {
            file_put_contents(self::$logFile, $message . PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Không thể ghi vào file log: " . $e->getMessage());
        }
    }

    private static function getLevelName($level) {
        switch ($level) {
            case self::DEBUG:
                return 'DEBUG';
            case self::INFO:
                return 'INFO';
            case self::WARNING:
                return 'WARNING';
            case self::ERROR:
                return 'ERROR';
            case self::CRITICAL:
                return 'CRITICAL';
            default:
                return 'UNKNOWN';
        }
    }
}