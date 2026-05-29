<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\JwtService;

class JwtServiceTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        $this->jwt = new JwtService('test-secret-key-for-testing', 3600);
    }

    // ─── Encode ─────────────────────────────────────────────────

    public function testEncodeReturnsString(): void
    {
        $token = $this->jwt->encode(['user_id' => 1]);
        $this->assertIsString($token);
    }

    public function testEncodeReturnsThreeParts(): void
    {
        $token = $this->jwt->encode(['user_id' => 1]);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function testEncodeIncludesPayloadData(): void
    {
        $token = $this->jwt->encode(['user_id' => 42, 'role' => 'admin']);
        $decoded = $this->jwt->decode($token);
        
        $this->assertEquals(42, $decoded['user_id']);
        $this->assertEquals('admin', $decoded['role']);
    }

    public function testEncodeAddsIatAndExp(): void
    {
        $before = time();
        $token = $this->jwt->encode(['user_id' => 1]);
        $after = time();

        $decoded = $this->jwt->decode($token);
        
        $this->assertGreaterThanOrEqual($before, $decoded['iat']);
        $this->assertLessThanOrEqual($after, $decoded['iat']);
        $this->assertEquals($decoded['iat'] + 3600, $decoded['exp']);
    }

    public function testEncodeWithCustomExpiry(): void
    {
        $token = $this->jwt->encode(['user_id' => 1], 7200);
        $decoded = $this->jwt->decode($token);
        
        $this->assertEquals($decoded['iat'] + 7200, $decoded['exp']);
    }

    // ─── Decode ─────────────────────────────────────────────────

    public function testDecodeReturnsPayload(): void
    {
        $token = $this->jwt->encode(['user_id' => 1, 'email' => 'test@example.com']);
        $decoded = $this->jwt->decode($token);
        
        $this->assertEquals(1, $decoded['user_id']);
        $this->assertEquals('test@example.com', $decoded['email']);
    }

    public function testDecodeThrowsOnInvalidFormat(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token format');
        
        $this->jwt->decode('invalid-token');
    }

    public function testDecodeThrowsOnInvalidSignature(): void
    {
        $token = $this->jwt->encode(['user_id' => 1]);
        
        // Tamper with the token
        $parts = explode('.', $token);
        $parts[2] = 'tampered-signature';
        $tampered = implode('.', $parts);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token signature');
        
        $this->jwt->decode($tampered);
    }

    public function testDecodeThrowsOnExpiredToken(): void
    {
        // Create service with negative expiry (already expired)
        $jwt = new JwtService('test-secret-key-for-testing', -10);
        
        $token = $jwt->encode(['user_id' => 1]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token expired');
        
        $jwt->decode($token);
    }

    // ─── Different Secrets ───────────────────────────────────────

    public function testDifferentSecretsProduceDifferentTokens(): void
    {
        $jwt1 = new JwtService('secret-1');
        $jwt2 = new JwtService('secret-2');
        
        $token1 = $jwt1->encode(['user_id' => 1]);
        $token2 = $jwt2->encode(['user_id' => 1]);
        
        $this->assertNotEquals($token1, $token2);
    }

    public function testTokenFromDifferentSecretFailsDecode(): void
    {
        $jwt1 = new JwtService('secret-1');
        $jwt2 = new JwtService('secret-2');
        
        $token = $jwt1->encode(['user_id' => 1]);
        
        $this->expectException(\Exception::class);
        $jwt2->decode($token);
    }

    // ─── Edge Cases ──────────────────────────────────────────────

    public function testEncodeWithEmptyPayload(): void
    {
        $token = $this->jwt->encode([]);
        $decoded = $this->jwt->decode($token);
        
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
    }

    public function testEncodeWithSpecialCharacters(): void
    {
        $token = $this->jwt->encode(['name' => 'Nguyễn Văn A', 'email' => 'test@domain.com']);
        $decoded = $this->jwt->decode($token);
        
        $this->assertEquals('Nguyễn Văn A', $decoded['name']);
    }

    public function testMultipleTokensAreUnique(): void
    {
        $token1 = $this->jwt->encode(['user_id' => 1]);
        usleep(1000000); // 1 second
        $token2 = $this->jwt->encode(['user_id' => 1]);
        
        // Tokens should be different due to different iat
        $this->assertNotEquals($token1, $token2);
    }
}
