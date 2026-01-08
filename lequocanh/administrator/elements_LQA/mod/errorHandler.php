<?php

class ErrorHandler {
    private static $logger;
    
    public static function init() {
        self::$logger = Logger::getInstance();
        
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }
    
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorData = [
            'type' => 'PHP Error',
            'severity' => self::getSeverityName($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
        
        self::logError($errorData);
        
        return true;
    }
    
    public static function handleException($exception) {
        $errorData = [
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ];
        
        self::logError($errorData);
        
        self::showErrorPage($errorData);
    }
    
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ];
            
            self::logError($errorData);
            self::showErrorPage($errorData);
        }
    }
    
    private static function logError($errorData) {
        self::$logger->error('System Error', $errorData);
        
        if (class_exists('ModernMonitoringSystem')) {
            $monitor = ModernMonitoringSystem::getInstance();
            $monitor->incrementCounter('system_errors_total', [
                'type' => $errorData['type'],
                'file' => basename($errorData['file'] ?? 'unknown')
            ]);
        }
    }
    
    private static function showErrorPage($errorData) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            self::showDetailedError($errorData);
        } else {
            self::showGenericError();
        }
        exit();
    }
    
    private static function showDetailedError($errorData) {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>System Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .error-container { background: #f8f8f8; padding: 20px; border-left: 5px solid #dc3545; }
                .error-title { color: #dc3545; margin-bottom: 10px; }
                .error-details { background: white; padding: 15px; margin: 10px 0; }
                pre { background: #f1f1f1; padding: 10px; overflow-x: auto; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <h2 class='error-title'>{$errorData['type']}</h2>
                <div class='error-details'>
                    <strong>Message:</strong> {$errorData['message']}<br>
                    <strong>File:</strong> {$errorData['file']}<br>
                    <strong>Line:</strong> {$errorData['line']}
                </div>
                " . (isset($errorData['trace']) ? "<pre>" . print_r($errorData['trace'], true) . "</pre>" : "") . "
            </div>
        </body>
        </html>";
    }
    
    private static function showGenericError() {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Lỗi Hệ Thống</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
                .error-container { max-width: 500px; margin: 0 auto; }
                .error-icon { font-size: 64px; color: #dc3545; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-icon'>⚠️</div>
                <h2>Đã xảy ra lỗi hệ thống</h2>
                <p>Chúng tôi đang khắc phục sự cố. Vui lòng thử lại sau.</p>
                <a href='javascript:history.back()'>← Quay lại</a>
            </div>
        </body>
        </html>";
    }
    
    private static function getSeverityName($severity) {
        $severities = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        return $severities[$severity] ?? 'Unknown Error';
    }
}