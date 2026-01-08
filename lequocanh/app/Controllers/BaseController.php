<?php

abstract class BaseController
{
    protected $config;
    protected $request;
    protected $response;

    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->initializeController();
    }

    protected function initializeController()
    {

    }

    protected function view($viewName, $data = [])
    {
        $viewPath = $this->getViewPath($viewName);

        if (!file_exists($viewPath)) {
            throw new Exception("View not found: $viewName");
        }

        extract($data);

        ob_start();

        include $viewPath;

        $content = ob_get_clean();

        return $content;
    }

    protected function render($viewName, $data = [])
    {
        echo $this->view($viewName, $data);
    }

    protected function getViewPath($viewName)
    {
        return __DIR__ . '/../Views/' . str_replace('.', '/', $viewName) . '.php';
    }

    protected function redirect($url, $statusCode = 302)
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function input($key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }

        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate($rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $fieldRules = explode('|', $rule);

            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field $field is required";
                }

                if (strpos($fieldRule, 'min:') === 0) {
                    $min = (int)substr($fieldRule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field][] = "Field $field must be at least $min characters";
                    }
                }

                if (strpos($fieldRule, 'max:') === 0) {
                    $max = (int)substr($fieldRule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field][] = "Field $field must not exceed $max characters";
                    }
                }

                if ($fieldRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Field $field must be a valid email";
                }
            }
        }

        return $errors;
    }

    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function getUser()
    {
        return $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? null;
    }

    protected function isAuthenticated()
    {
        return isset($_SESSION['USER']) || isset($_SESSION['ADMIN']);
    }

    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/lequocanh/administrator/userLogin.php');
        }
    }
}
