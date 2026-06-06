<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * CSRF Protection Helper
 *
 * Usage in controllers:
 *   use CsrfProtection;
 *   $this->validateCsrf();
 *
 * Usage in views:
 *   <?= CsrfHelper::field() ?>
 */
trait CsrfProtection
{
    /**
     * Generate a CSRF token.
     */
    protected static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token.
     */
    protected static function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Validate CSRF token from request.
     */
    protected function validateCsrf(): void
    {
        $token = $this->input('csrf_token') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token || !self::validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}

/**
 * Static helper for views
 */
class CsrfHelper
{
    /**
     * Generate a CSRF token.
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Generate hidden input field with CSRF token.
     */
    public static function field(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF token value.
     */
    public static function token(): string
    {
        return self::generateCsrfToken();
    }

    /**
     * Generate meta tag for AJAX requests.
     */
    public static function meta(): string
    {
        $token = self::generateCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Generate JavaScript snippet for AJAX CSRF.
     */
    public static function script(): string
    {
        $token = self::generateCsrfToken();
        return <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add CSRF token to all AJAX requests
    const token = '{$token}';

    // For fetch API
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        options.headers = options.headers || {};
        if (options.method && options.method.toUpperCase() !== 'GET') {
            options.headers['X-CSRF-TOKEN'] = token;
        }
        return originalFetch.call(this, url, options);
    };

    // For XMLHttpRequest
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url) {
        this._method = method;
        return originalXHROpen.apply(this, arguments);
    };

    const originalXHRSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(data) {
        if (this._method && this._method.toUpperCase() !== 'GET') {
            this.setRequestHeader('X-CSRF-TOKEN', token);
        }
        return originalXHRSend.apply(this, arguments);
    };
});
</script>
JS;
    }
}
