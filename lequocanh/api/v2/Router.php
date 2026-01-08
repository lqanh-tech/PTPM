<?php

class Router {
    private $routes = [];
    private $middleware = [];
    
    public function __construct() {
        $this->loadMiddleware();
    }
    
    private function loadMiddleware() {
        $this->middleware = [
            'auth' => new JwtAuthMiddleware(),
            'rate_limit' => new RateLimitMiddleware(),
            'cors' => new CorsMiddleware(),
            'validation' => new ValidationMiddleware()
        ];
    }
    
    public function get($path, $handler, $middleware = []) {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    public function post($path, $handler, $middleware = []) {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    public function put($path, $handler, $middleware = []) {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    public function delete($path, $handler, $middleware = []) {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    private function addRoute($method, $path, $handler, $middleware) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api/v2', '', $path);
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                return $this->executeRoute($route, $path);
            }
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
    
    private function matchRoute($route, $method, $path) {
        if ($route['method'] !== $method) {
            return false;
        }
        
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $path);
    }
    
    private function executeRoute($route, $path) {
        try {

            foreach ($route['middleware'] as $middlewareName) {
                if (isset($this->middleware[$middlewareName])) {
                    $this->middleware[$middlewareName]->handle();
                }
            }
            
            $params = $this->extractParams($route['path'], $path);
            
            if (is_callable($route['handler'])) {
                return call_user_func($route['handler'], $params);
            } elseif (is_string($route['handler'])) {
                list($controller, $method) = explode('@', $route['handler']);
                $controllerInstance = new $controller();
                return $controllerInstance->$method($params);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function extractParams($routePath, $actualPath) {
        $routeParts = explode('/', trim($routePath, '/'));
        $actualParts = explode('/', trim($actualPath, '/'));
        $params = [];
        
        for ($i = 0; $i < count($routeParts); $i++) {
            if (preg_match('/\{([^}]+)\}/', $routeParts[$i], $matches)) {
                $params[$matches[1]] = $actualParts[$i] ?? null;
            }
        }
        
        return $params;
    }
}
