<?php

class TokenAuth
{
    private static $secretKey = 'lequocanh_notification_secret_2025';

    public static function generateToken($username)
    {
        $payload = [
            'username' => $username,
            'timestamp' => time(),
            'expires' => time() + (24 * 60 * 60)
        ];

        $data = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $data, self::$secretKey);

        return $data . '.' . $signature;
    }

    public static function verifyToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        list($data, $signature) = $parts;

        $expectedSignature = hash_hmac('sha256', $data, self::$secretKey);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $payload = json_decode(base64_decode($data), true);
        if (!$payload) {
            return false;
        }

        if (isset($payload['expires']) && $payload['expires'] < time()) {
            return false;
        }

        return $payload;
    }

    public static function getUserFromRequest()
    {

        $token = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (strpos($auth, 'Bearer ') === 0) {
                $token = substr($auth, 7);
            }
        }

        if (!$token && isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            $token = $_SERVER['HTTP_X_AUTH_TOKEN'];
        }

        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token && isset($_POST['token'])) {
            $token = $_POST['token'];
        }

        if (!$token && isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        }

        if ($token) {
            $payload = self::verifyToken($token);
            if ($payload && isset($payload['username'])) {
                return $payload['username'];
            }
        }

        return false;
    }

    public static function setTokenCookie($username)
    {
        $token = self::generateToken($username);

        if (headers_sent($file, $line)) {
            error_log("Cannot set cookie, headers already sent at $file:$line");
            return $token;
        }

        $expires = time() + (24 * 60 * 60);

        setcookie('auth_token', $token, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return $token;
    }

    public static function clearTokenCookie()
    {
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
