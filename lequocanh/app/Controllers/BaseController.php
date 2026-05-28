<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Input;
use ConfigManager;
use Exception;

abstract class BaseController
{
    protected ConfigManager $config;
    protected ?array $request = null;
    protected ?array $response = null;

    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->initializeController();
    }

    protected function initializeController(): void
    {
        // Override in child classes
    }

    protected function view(string $viewName, array $data = []): string
    {
        $viewPath = $this->getViewPath($viewName);

        if (!file_exists($viewPath)) {
            throw new Exception("View not found: {$viewName}");
        }

        extract($data);

        ob_start();

        include $viewPath;

        $content = ob_get_clean();

        return $content ?: '';
    }

    protected function render(string $viewName, array $data = []): void
    {
        echo $this->view($viewName, $data);
    }

    protected function getViewPath(string $viewName): string
    {
        // Primary: new MVC views
        $newPath = __DIR__ . '/../Views/' . str_replace('.', '/', $viewName) . '.php';
        if (file_exists($newPath)) {
            return $newPath;
        }

        // Fallback: legacy view directories
        $legacyPaths = [
            __DIR__ . '/../../apart/' . $viewName . '.php',
            __DIR__ . '/../../components/' . $viewName . '.php',
        ];

        foreach ($legacyPaths as $legacyPath) {
            if (file_exists($legacyPath)) {
                return $legacyPath;
            }
        }

        // Return primary path (will trigger "not found" error)
        return $newPath;
    }

    protected function redirect(string $url, int $statusCode = 302): never
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    protected function json(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Input::allPost() + Input::allGet();
        }

        return Input::input($key, $default);
    }

    protected function validate(array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $fieldRules = explode('|', $rule);

            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required";
                }

                if (str_starts_with($fieldRule, 'min:')) {
                    $min = (int) substr($fieldRule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        $errors[$field][] = "Field {$field} must be at least {$min} characters";
                    }
                }

                if (str_starts_with($fieldRule, 'max:')) {
                    $max = (int) substr($fieldRule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        $errors[$field][] = "Field {$field} must not exceed {$max} characters";
                    }
                }

                if ($fieldRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Field {$field} must be a valid email";
                }

                if ($fieldRule === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "Field {$field} must be a number";
                }

                if ($fieldRule === 'alpha' && !ctype_alpha((string) $value)) {
                    $errors[$field][] = "Field {$field} must contain only letters";
                }
            }
        }

        return $errors;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    protected function isGet(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }

    protected function getUser(): ?string
    {
        return $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? null;
    }

    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['USER']) || isset($_SESSION['ADMIN']);
    }

    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/lequocanh/administrator/userLogin.php');
        }
    }

    /**
     * Get client IP address.
     */
    protected function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get request headers.
     */
    protected function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Check if request is AJAX.
     */
    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /**
     * Flash message for next request.
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get and clear flash message.
     */
    protected function getFlash(?string $type = null): mixed
    {
        if ($type === null) {
            $flash = $_SESSION['flash'] ?? [];
            unset($_SESSION['flash']);
            return $flash;
        }

        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
}
