<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * API Middleware - handles CORS, request logging, and common API setup.
 */
class ApiMiddleware
{
    /**
     * Set CORS headers for API requests.
     */
    public static function handleCors(): void
    {
        $allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins) || self::isLocalRequest()) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Log API request for debugging.
     */
    public static function logRequest(string $resource, ?string $id = null): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'resource' => $resource,
            'id' => $id,
            'ip' => $ip,
        ];

        $logFile = __DIR__ . '/../../logs/api.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        @file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }

    /**
     * Get parsed JSON body from request.
     */
    public static function getJsonBody(): ?array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return null;
        }

        $data = json_decode($input, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Check if request is from local/development environment.
     */
    private static function isLocalRequest(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($ip, ['127.0.0.1', '::1', '::ffff:127.0.0.1']);
    }
}
