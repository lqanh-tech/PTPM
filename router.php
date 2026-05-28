<?php

require_once __DIR__ . '/bootstrap.php';

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = trim($request_uri, '/');

$blocked_patterns = [
    '/^\.env/',
    '/^\.git/',
    '/^DB\//',
    '/^logs\//',
    '/^monitoring\//',
    '/^\.kiro\//',
    '/^\.vscode\//',
    '/^node_modules\//',
    '/^vendor\//',
    '/\.(log|sql|ini|json|yml|yaml|md|lock|gitignore)$/',
    '/composer\.(json|lock)/',
    '/package(-lock)?\.json/',
    '/docker-compose\.yml/',
    '/Dockerfile/',
    '/\.bat$/',
    '/\.sh$/',
];

foreach ($blocked_patterns as $pattern) {
    if (preg_match($pattern, $request_uri)) {
        Security::logSecurityEvent('blocked_access', ['uri' => $request_uri]);
        http_response_code(404);
        die('404 - Not Found');
    }
}

if (strpos($request_uri, 'api/') === 0) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::checkRateLimit($ip, 60, 60)) {
        Security::logSecurityEvent('rate_limit_exceeded', ['ip' => $ip, 'uri' => $request_uri]);
        http_response_code(429);
        die('429 - Too Many Requests');
    }
}

$routes = [
    '' => '/lequocanh/index.php',
    'lequocanh' => '/lequocanh/index.php',
    'lequocanh/' => '/lequocanh/index.php',
    'admin' => '/lequocanh/administrator/index.php',
    'admin/login' => '/lequocanh/administrator/userLogin.php',
    'admin/logout' => '/lequocanh/administrator/userLogout.php',
    'api/momo/callback' => '/lequocanh/api/momo_callback.php',
    'api/momo/ipn' => '/lequocanh/api/momo_ipn.php',

    // API v1 routes
    'api/v1/products' => 'api/products',
    'api/v1/categories' => 'api/categories',
    'api/v1/orders' => 'api/orders',
    'api/v1/search' => 'api/search',
    'api/v1/stats' => 'api/stats',
    'api/v1/auth/login' => 'api/auth/login',
    'api/v1/auth/register' => 'api/auth/register',
    'api/v1/auth/logout' => 'api/auth/logout',
    'api/v1/auth/me' => 'api/auth/me',
    'api/v1/cart' => 'api/cart',
];

// API routing with controller
if (strpos($request_uri, 'api/v1/') === 0) {
    $apiRoute = substr($request_uri, 7); // Remove 'api/v1/'
    $parts = explode('/', $apiRoute);
    $resource = $parts[0] ?? '';
    $id = $parts[1] ?? null;

    // Set JSON content type
    header('Content-Type: application/json; charset=UTF-8');

    // Map routes to controller methods
    $apiController = new App\Controllers\Api\ApiController();

    switch ($resource) {
        case 'products':
            if ($id) {
                $_GET['id'] = $id;
                $apiController->product();
            } else {
                $apiController->products();
            }
            break;

        case 'categories':
            $apiController->categories();
            break;

        case 'orders':
            if ($id) {
                $_GET['id'] = $id;
                $apiController->order();
            } else {
                $apiController->orders();
            }
            break;

        case 'search':
            $apiController->search();
            break;


        case 'docs':
            $apiController->docs();
            break;

        case 'stats':
            $apiController->stats();
            break;

        case 'auth':
            $action = $id ?? '';
            switch ($action) {
                case 'login':
                    $apiController->login();
                    break;
                case 'register':
                    $apiController->register();
                    break;
                case 'logout':
                    $apiController->logout();
                    break;
                case 'me':
                    $apiController->me();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Auth endpoint not found']);
                    break;
            }
            break;

        case 'cart':
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($method === 'GET') {
                $apiController->cart();
            } elseif ($method === 'POST') {
                $apiController->cartAdd();
            } elseif ($method === 'PUT') {
                if ($id) {
                    $_GET['id'] = $id;
                }
                $apiController->cartUpdate();
            } elseif ($method === 'DELETE') {
                if ($id) {
                    $_GET['id'] = $id;
                    $apiController->cartRemove();
                } else {
                    $apiController->cartClear();
                }
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
    exit;
}

if (array_key_exists($request_uri, $routes)) {
    $file = __DIR__ . $routes[$request_uri];
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

$legacy_file = __DIR__ . '/lequocanh/' . $request_uri;
if (file_exists($legacy_file) && is_file($legacy_file)) {

    // Serve static files (CSS, JS, images, etc.)
    $ext = pathinfo($legacy_file, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];
    
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($legacy_file);
        exit;
    }

    if ($ext === 'php') {
        require $legacy_file;
        exit;
    }
}

http_response_code(404);
echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>
    <a href="/">Go to Homepage</a>
</body>
</html>';
