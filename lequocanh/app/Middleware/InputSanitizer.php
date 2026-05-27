<?php
declare(strict_types=1);

namespace App\Middleware;

class InputSanitizer
{
    private static ?InputSanitizer $instance = null;
    
    private function __construct() {}
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Sanitize all input data
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize single value
     */
    public function sanitizeValue($value)
    {
        if (is_array($value)) {
            return $this->sanitize($value);
        }
        
        if (is_string($value)) {
            return $this->sanitizeString($value);
        }
        
        return $value;
    }
    
    /**
     * Sanitize string
     */
    public function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Remove control characters (except newline, carriage return, tab)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        return $value;
    }
    
    /**
     * Sanitize HTML
     */
    public function sanitizeHTML(string $html): string
    {
        // Allow only safe tags
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><a><img>';
        
        return strip_tags($html, $allowedTags);
    }
    
    /**
     * Sanitize email
     */
    public function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize URL
     */
    public function sanitizeURL(string $url): string
    {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitize integer
     */
    public function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     */
    public function sanitizeFloat($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove directory traversal
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        return $filename;
    }
    
    /**
     * Sanitize phone number
     */
    public function sanitizePhone(string $phone): string
    {
        // Keep only digits, +, -, (, ), space
        return preg_replace('/[^0-9+\-\(\)\s]/', '', $phone);
    }
    
    /**
     * Check for SQL injection patterns
     */
    public function hasSQLInjection(string $value): bool
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER)\b)/i',
            '/(--|\/\*|\*\/|;)/',
            '/(\b(OR|AND)\b\s+\d+\s*=\s*\d+)/i',
            '/(\'|")(.*?)(\bOR\b|\bAND\b)(.*?)(\'|")/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for XSS patterns
     */
    public function hasXSS(string $value): bool
    {
        $patterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/data:text\/html/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize request data
     */
    public function sanitizeRequest(): array
    {
        $data = [];
        
        // Sanitize GET
        if (!empty($_GET)) {
            $data['get'] = $this->sanitize($_GET);
        }
        
        // Sanitize POST
        if (!empty($_POST)) {
            $data['post'] = $this->sanitize($_POST);
        }
        
        return $data;
    }
}