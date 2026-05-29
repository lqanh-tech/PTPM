<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Lightweight JWT service - no external dependencies.
 * Uses HMAC-SHA256 for signing.
 */
class JwtService
{
    private string $secret;
    private int $defaultExpiry;

    public function __construct(?string $secret = null, int $defaultExpiry = 3600)
    {
        $this->secret = $secret ?: $this->getSecret();
        $this->defaultExpiry = $defaultExpiry;
    }

    /**
     * Generate a JWT token.
     */
    public function encode(array $payload, ?int $expiry = null): string
    {
        $expiry = $expiry ?? $this->defaultExpiry;

        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payloadEncoded}", $this->secret, true)
        );

        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Decode and validate a JWT token.
     *
     * @return array Decoded payload
     * @throws \Exception If token is invalid or expired
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid token signature');
        }

        $payloadData = json_decode($this->base64UrlDecode($payload), true);

        if (!is_array($payloadData)) {
            throw new \Exception('Invalid token payload');
        }

        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            throw new \Exception('Token expired');
        }

        return $payloadData;
    }

    /**
     * Generate a refresh token with longer expiry.
     */
    public function encodeRefresh(array $payload, int $expiry = 604800): string
    {
        $payload['type'] = 'refresh';
        return $this->encode($payload, $expiry);
    }

    /**
     * Extract token from Authorization header.
     */
    public static function extractFromHeader(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(\S+)/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '';

        if (empty($secret) || strlen($secret) < 32) {
            // Generate from app-specific data as fallback
            $secret = hash('sha256', __DIR__ . ($_SERVER['SERVER_NAME'] ?? 'localhost') . 'app_secret_key');
        }

        return $secret;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
