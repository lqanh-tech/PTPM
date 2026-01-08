<?php

$environment = getenv('APP_ENV') ?: 'production';
$isDevelopment = (
    $environment === 'development' || 
    $environment === 'dev' ||
    getenv('DEBUG') === 'true' ||
    (isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
    ))
);

define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'lequocanh');
define('VERIFY_PASSWORD', getenv('VERIFY_PASSWORD') ?: 'lequocanh');
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_ROLE', 'admin');

define('SESSION_TIMEOUT', 3600);
define('SESSION_REGENERATE_INTERVAL', 300);
define('SESSION_USE_STRICT_MODE', true);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SECURE', !$isDevelopment);
define('SESSION_COOKIE_SAMESITE', 'Lax');

define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_MIXED_CASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', false);

define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 3600);

define('XSS_FILTER_ENABLED', true);
define('HTML_PURIFIER_ENABLED', false);

define('USE_PREPARED_STATEMENTS', true);
define('VALIDATE_ALL_INPUTS', true);

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('ALLOWED_UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('DISALLOWED_UPLOAD_EXTENSIONS', ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'pht', 'phar', 'exe', 'scr', 'dll', 'msi', 'vbs', 'bat', 'cmd', 'sh', 'js']);

if (SESSION_USE_STRICT_MODE) {
    ini_set('session.use_strict_mode', 1);
}

session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => '',
    'secure' => SESSION_COOKIE_SECURE,
    'httponly' => SESSION_COOKIE_HTTPONLY,
    'samesite' => SESSION_COOKIE_SAMESITE
]);

if (!headers_sent()) {

    if (!$isDevelopment) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");
    }
    
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    if (!$isDevelopment) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:

            $output = filter_var($input, FILTER_UNSAFE_RAW);

            $output = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
            return $output;
    }
}

function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    } else if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRY) {

        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    
    if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRY) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function getCsrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}