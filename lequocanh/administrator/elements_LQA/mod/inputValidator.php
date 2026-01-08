<?php
/**
 * Input Validator - Validate và sanitize dữ liệu người dùng
 * Áp dụng các biện pháp phòng chống XSS
 */

class InputValidator {
    
    /**
     * Validate và sanitize text input
     */
    public static function sanitizeText($input, $allowHtml = false) {
        if ($allowHtml) {
            // Cho phép một số HTML tags an toàn
            return strip_tags($input, '<p><br><strong><em><ul><ol><li>');
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
    }
    
    /**
     * Validate số nguyên
     */
    public static function validateInt($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) return false;
        
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        
        return $value;
    }
    
    /**
     * Validate số thực
     */
    public static function validateFloat($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false) return false;
        
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        
        return $value;
    }
    
    /**
     * Sanitize cho SQL (sử dụng với prepared statements)
     */
    public static function sanitizeForSQL($value) {
        return addslashes(strip_tags(trim($value)));
    }
    
    /**
     * Validate phone number (VN)
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
            return $phone;
        }
        return false;
    }
    
    /**
     * Encode output để hiển thị an toàn
     */
    public static function encodeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'encodeOutput'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate JSON
     */
    public static function validateJson($json) {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
