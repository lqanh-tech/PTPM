<?php
/**
 * Security Helper Functions
 * Các hàm tiện ích để áp dụng bảo mật dễ dàng
 */

/**
 * Escape output - sử dụng khi hiển thị dữ liệu
 * Thay thế cho echo trực tiếp
 */
function e($data) {
    if (is_array($data)) {
        return array_map('e', $data);
    }
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Escape và echo
 */
function ee($data) {
    echo e($data);
}

/**
 * Sanitize input
 */
function sanitize($input, $type = 'text') {
    require_once __DIR__ . '/InputValidator.php';
    
    switch ($type) {
        case 'email':
            return InputValidator::validateEmail($input);
        case 'url':
            return InputValidator::validateUrl($input);
        case 'int':
            return InputValidator::validateInt($input);
        case 'float':
            return InputValidator::validateFloat($input);
        case 'phone':
            return InputValidator::validatePhone($input);
        case 'html':
            return InputValidator::sanitizeText($input, true);
        default:
            return InputValidator::sanitizeText($input);
    }
}

/**
 * CSRF Token field cho form
 */
function csrf_field() {
    $securityPaths = [
        __DIR__ . '/../../../security.php',
        __DIR__ . '/../../../../security.php',
        __DIR__ . '/../../../../../security.php',
    ];
    
    foreach ($securityPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    if (class_exists('Security')) {
        $token = Security::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
    }
    
    return '';
}

/**
 * Validate CSRF Token
 */
function csrf_check() {
    $securityPaths = [
        __DIR__ . '/../../../security.php',
        __DIR__ . '/../../../../security.php',
        __DIR__ . '/../../../../../security.php',
    ];
    
    foreach ($securityPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    if (!class_exists('Security')) {
        http_response_code(500);
        die('Security class not found');
    }
    
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    return true;
}

/**
 * Sanitize array recursively
 */
function sanitize_array($array) {
    return array_map(function($item) {
        if (is_array($item)) {
            return sanitize_array($item);
        }
        return sanitize($item);
    }, $array);
}

/**
 * Safe JSON encode
 */
function safe_json_encode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Check if request is AJAX
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Validate origin (CORS)
 */
function validate_origin($allowed_origins = []) {
    $securityPaths = [
        __DIR__ . '/../../../security.php',
        __DIR__ . '/../../../../security.php',
        __DIR__ . '/../../../../../security.php',
    ];
    
    foreach ($securityPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    if (class_exists('Security')) {
        return Security::validateOrigin($allowed_origins);
    }
    
    return true;
}
