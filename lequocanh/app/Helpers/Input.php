<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Centralized input sanitization helper.
 * Use this instead of directly accessing $_GET/$_POST/$_REQUEST.
 */
class Input
{
    /**
     * Get sanitized GET parameter.
     */
    public static function get(string $key, $default = null)
    {
        return self::sanitize($_GET[$key] ?? $default);
    }

    /**
     * Get sanitized POST parameter.
     */
    public static function post(string $key, $default = null)
    {
        return self::sanitize($_POST[$key] ?? $default);
    }

    /**
     * Get sanitized parameter from GET or POST (POST takes precedence).
     */
    public static function input(string $key, $default = null)
    {
        return self::sanitize($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    /**
     * Get all GET parameters, sanitized.
     */
    public static function allGet(): array
    {
        return array_map([self::class, 'sanitize'], $_GET);
    }

    /**
     * Get all POST parameters, sanitized.
     */
    public static function allPost(): array
    {
        return array_map([self::class, 'sanitize'], $_POST);
    }

    /**
     * Get raw (unsanitized) value - use only when you need to preserve formatting.
     * Always sanitize before using in SQL queries.
     */
    public static function raw(string $key, string $source = 'input', $default = null)
    {
        switch ($source) {
            case 'get': return $_GET[$key] ?? $default;
            case 'post': return $_POST[$key] ?? $default;
            default: return $_POST[$key] ?? $_GET[$key] ?? $default;
        }
    }

    /**
     * Sanitize a single value.
     */
    public static function sanitize($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        if (!is_string($value)) {
            return $value;
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get integer parameter.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key, $default);
        return filter_var($value, FILTER_VALIDATE_INT) ?: $default;
    }

    /**
     * Get float parameter.
     */
    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::input($key, $default);
        return filter_var($value, FILTER_VALIDATE_FLOAT) ?: $default;
    }

    /**
     * Get email parameter, validated.
     */
    public static function email(string $key, $default = null): ?string
    {
        $value = self::input($key, $default);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
    }

    /**
     * Check if request method is POST.
     */
    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    /**
     * Check if request method is GET.
     */
    public static function isGet(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }
}
