<?php

require_once __DIR__ . '/csrfProtection.php';
require_once __DIR__ . '/inputValidator.php';

class SecurityEnhancement {
    
    public static function initializePage($requireCSRF = true, $validationRules = []) {

        if (class_exists('SessionManager')) {
            SessionManager::start();
        }
        
        if ($requireCSRF) {
            self::initializeCSRF();
        }
        
        if (!empty($validationRules) && !empty($_POST)) {
            self::validateInput($_POST, $validationRules);
        }
        
        if (class_exists('Logger')) {
            Logger::debug("Security initialized", [
                'csrf_enabled' => $requireCSRF,
                'validation_rules' => !empty($validationRules),
                'page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
    }
    
    private static function initializeCSRF() {

        CSRFProtection::generateToken();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                CSRFProtection::requireValidToken();
            } catch (Exception $e) {
                self::handleSecurityViolation('CSRF', $e->getMessage());
            }
        }
    }
    
    private static function validateInput($data, $rules) {
        $validator = new InputValidator();
        
        if (!$validator->validate($data, $rules)) {
            $errors = $validator->getErrors();
            
            if (class_exists('Logger')) {
                Logger::warning("Input validation failed", [
                    'errors' => $errors,
                    'data_keys' => array_keys($data)
                ]);
            }
            
            self::handleValidationErrors($errors);
        }
    }
    
    private static function handleSecurityViolation($type, $message) {
        if (class_exists('Logger')) {
            Logger::error("Security violation detected", [
                'type' => $type,
                'message' => $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
            ]);
        }
        
        if (class_exists('ResponseManager')) {
            ResponseManager::error('Security violation detected', null, 403);
        } else {
            http_response_code(403);
            die('Security violation detected');
        }
    }
    
    private static function handleValidationErrors($errors) {
        if (class_exists('ResponseManager')) {
            ResponseManager::error('Validation failed', $errors, 400);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
    }
    
    public static function getFormSecurityHeaders() {
        $html = '';
        
        $html .= CSRFProtection::getHiddenField();
        
        $html .= CSRFProtection::getMetaTag();
        
        return $html;
    }
    
    public static function getAjaxSecurityScript() {
        return CSRFProtection::getAjaxScript();
    }
    
    public static function sanitizeFormData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {

            if ($key === CSRFProtection::TOKEN_NAME) {
                continue;
            }
            
            $sanitized[$key] = self::sanitizeByFieldName($key, $value);
        }
        
        return $sanitized;
    }
    
    private static function sanitizeByFieldName($fieldName, $value) {

        if (strpos($fieldName, 'email') !== false) {
            return InputValidator::sanitizeValue($value, 'email');
        }
        
        if (strpos($fieldName, 'phone') !== false || strpos($fieldName, 'dienthoai') !== false) {
            return InputValidator::sanitizeValue($value, 'numeric');
        }
        
        if (strpos($fieldName, 'gia') !== false || strpos($fieldName, 'price') !== false || 
            strpos($fieldName, 'tien') !== false || strpos($fieldName, 'money') !== false) {
            return InputValidator::sanitizeValue($value, 'float');
        }
        
        if (strpos($fieldName, 'soluong') !== false || strpos($fieldName, 'quantity') !== false) {
            return InputValidator::sanitizeValue($value, 'integer');
        }
        
        if (strpos($fieldName, 'id') !== false && is_numeric($value)) {
            return InputValidator::sanitizeValue($value, 'integer');
        }
        
        return InputValidator::sanitizeValue($value, 'string');
    }
    
    public static function getCommonValidationRules() {
        return [
            'product_rules' => [
                'tenhanghoa' => 'required|min_length:2|max_length:255|no_script',
                'gia' => 'required|numeric|min_value:0',
                'soluong' => 'required|integer|min_value:0',
                'mota' => 'max_length:1000|no_script'
            ],
            'user_rules' => [
                'username' => 'required|min_length:3|max_length:50|alpha_numeric',
                'password' => 'required|min_length:6|max_length:255',
                'email' => 'required|email|max_length:255',
                'dienthoai' => 'phone|max_length:15'
            ],
            'order_rules' => [
                'diachi' => 'required|min_length:10|max_length:500|no_script',
                'ghichu' => 'max_length:500|no_script',
                'tongtien' => 'required|numeric|min_value:0'
            ]
        ];
    }
}