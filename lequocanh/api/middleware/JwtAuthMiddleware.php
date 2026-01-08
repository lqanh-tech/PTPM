<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware {
    private $secret;
    
    public function __construct() {

        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? '';
        
        if (empty($secret) || $secret === 'your-secret-key-here' || strlen($secret) < 32) {
            error_log('SECURITY WARNING: JWT_SECRET is not properly configured!');

            $secret = hash('sha256', __DIR__ . $_SERVER['SERVER_NAME'] . 'fallback_secret_change_me');
        }
        
        $this->secret = $secret;
    }
    
    public function handle() {
        $token = $this->extractToken();
        
        if (!$token) {
            $this->unauthorized('Token not provided');
        }
        
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $_REQUEST['user'] = (array)$decoded;
            
        } catch (Exception $e) {
            $this->unauthorized('Invalid token: ' . $e->getMessage());
        }
    }
    
    private function extractToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        $allowQueryToken = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        if ($allowQueryToken && isset($_GET['token'])) {
            error_log('WARNING: Token passed via query parameter - not recommended for production');
            return $_GET['token'];
        }
        
        return null;
    }
    
    private function unauthorized($message) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message
        ]);
        exit;
    }
    
    public static function generateToken($payload, $expiry = 3600) {

        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? '';
        
        if (empty($secret) || $secret === 'your-secret-key-here' || strlen($secret) < 32) {
            error_log('SECURITY WARNING: JWT_SECRET is not properly configured for token generation!');
            $secret = hash('sha256', __DIR__ . $_SERVER['SERVER_NAME'] . 'fallback_secret_change_me');
        }
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        
        return JWT::encode($payload, $secret, 'HS256');
    }
}
