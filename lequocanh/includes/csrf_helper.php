<?php

if (!function_exists('csrf_token')) {

    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {

    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('csrf_meta')) {

    function csrf_meta() {
        return '<meta name="csrf-token" content="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf_token')) {

    function verify_csrf_token($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrf_check')) {

    function csrf_check() {
        if (!verify_csrf_token()) {
            http_response_code(403);
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid CSRF token'
                ]);
            } else {
                echo 'Invalid CSRF token. Please refresh the page and try again.';
            }
            exit;
        }
    }
}

if (!function_exists('regenerate_csrf_token')) {

    function regenerate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
