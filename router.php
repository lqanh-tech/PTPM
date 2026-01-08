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
    'admin' => '/lequocanh/administrator/index.php',
    'admin/login' => '/lequocanh/administrator/userLogin.php',
    'admin/logout' => '/lequocanh/administrator/userLogout.php',
    'api/momo/callback' => '/lequocanh/api/momo_callback.php',
    'api/momo/ipn' => '/lequocanh/api/momo_ipn.php',
];

if (array_key_exists($request_uri, $routes)) {
    $file = __DIR__ . $routes[$request_uri];
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

$legacy_file = __DIR__ . '/lequocanh/' . $request_uri;
if (file_exists($legacy_file) && is_file($legacy_file)) {

    if (pathinfo($legacy_file, PATHINFO_EXTENSION) === 'php') {
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
