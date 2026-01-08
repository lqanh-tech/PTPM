<?php

define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'lequocanh');
define('VERIFY_PASSWORD', getenv('VERIFY_PASSWORD') ?: 'lequocanh');

define('SESSION_TIMEOUT', 3600);
define('SESSION_NAME', 'LEQUOCANH_SESSION');

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('PASSWORD_MIN_LENGTH', 6);

define('DEFAULT_PROFIT_MARGIN', 20);
define('MAX_CART_ITEMS', 100);
define('ORDER_TIMEOUT', 1800);

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', '/uploads/');

define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEOUT', 30);

define('DEFAULT_PAGE_SIZE', 25);
define('MAX_PAGE_SIZE', 100);

define('LOG_MAX_FILES', 30);
define('LOG_MAX_SIZE', 10 * 1024 * 1024);

define('IS_DEVELOPMENT', getenv('APP_ENV') === 'development');
define('IS_PRODUCTION', getenv('APP_ENV') === 'production' || getenv('APP_ENV') === false);

define('MOD_PATH', __DIR__ . '/../mod/');
define('VIEW_PATH', __DIR__ . '/../');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('LOG_DIR', __DIR__ . '/../logs/');

$dirs = [LOG_DIR, UPLOAD_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}