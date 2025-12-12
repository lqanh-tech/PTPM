<?php
/**
 * Password Helper Class
 * Sử dụng Bcrypt để hash và verify password
 */
class PasswordHelper
{
    /**
     * Hash password sử dụng Bcrypt
     * 
     * @param string $password Password cần hash
     * @return string Password đã được hash
     */
    public static function hash($password)
    {
        // Sử dụng PASSWORD_BCRYPT với cost factor 12
        // Cost càng cao thì càng an toàn nhưng càng chậm
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password với hash
     * 
     * @param string $password Password cần kiểm tra
     * @param string $hash Hash đã lưu trong database
     * @return bool True nếu password khớp, false nếu không
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Kiểm tra xem hash có cần rehash không
     * (Ví dụ: khi thay đổi cost factor)
     * 
     * @param string $hash Hash cần kiểm tra
     * @return bool True nếu cần rehash
     */
    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Kiểm tra xem password có phải là plain text không
     * (Để hỗ trợ migration từ plain text sang bcrypt)
     * 
     * @param string $password Password cần kiểm tra
     * @return bool True nếu là plain text
     */
    public static function isPlainText($password)
    {
        // Bcrypt hash luôn bắt đầu với $2y$ và có độ dài 60 ký tự
        return !preg_match('/^\$2[ayb]\$.{56}$/', $password);
    }
}
