<?php

class ErrorTracker {

    private static $errors = [];
    private static $errorCounts = [];

    public static function registerErrorHandler() {
        set_error_handler([__CLASS__, 'errorHandler']);
        register_shutdown_function([__CLASS__, 'fatalErrorShutdownHandler']);
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline) {

        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);
        $errorMessage = "[$errorType] $errstr in $errfile on line $errline";
        
        self::logError($errorMessage, $errorType, [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline
        ]);

        return true;
    }

    public static function fatalErrorShutdownHandler() {
        $lastError = error_get_last();
        if ($lastError && ($lastError['type'] === E_ERROR || $lastError['type'] === E_PARSE || $lastError['type'] === E_CORE_ERROR || $lastError['type'] === E_COMPILE_ERROR)) {
            $errorType = self::getErrorType($lastError['type']);
            $errorMessage = "[$errorType] {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}";
            
            self::logError($errorMessage, $errorType, [
                'errno' => $lastError['type'],
                'file' => $lastError['file'],
                'line' => $lastError['line']
            ]);
        }
    }

    public static function logError($message, $type = 'Unknown', $context = []) {
        self::$errors[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];

        if (!isset(self::$errorCounts[$type])) {
            self::$errorCounts[$type] = 0;
        }
        self::$errorCounts[$type]++;

    }

    public static function getErrors() {
        return self::$errors;
    }

    public static function getErrorCounts() {
        return self::$errorCounts;
    }

    public static function clearErrors() {
        self::$errors = [];
        self::$errorCounts = [];
    }

    private static function getErrorType($type) {
        switch ($type) {
            case E_ERROR: return 'Error';
            case E_WARNING: return 'Warning';
            case E_PARSE: return 'Parsing Error';
            case E_NOTICE: return 'Notice';
            case E_CORE_ERROR: return 'Core Error';
            case E_CORE_WARNING: return 'Core Warning';
            case E_COMPILE_ERROR: return 'Compile Error';
            case E_COMPILE_WARNING: return 'Compile Warning';
            case E_USER_ERROR: return 'User Error';
            case E_USER_WARNING: return 'User Warning';
            case E_USER_NOTICE: return 'User Notice';
            case E_STRICT: return 'Strict';
            case E_RECOVERABLE_ERROR: return 'Recoverable Error';
            case E_DEPRECATED: return 'Deprecated';
            case E_USER_DEPRECATED: return 'User Deprecated';
            default: return 'Unknown Error';
        }
    }
}
?>