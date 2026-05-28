<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\UserRateLimiter;

/**
 * Test cases for UserRateLimiter
 */
class UserRateLimiterTest extends TestCase
{
    private UserRateLimiter $limiter;
    private string $testCacheDir;

    protected function setUp(): void
    {
        $this->testCacheDir = sys_get_temp_dir() . '/ratelimit_test_' . uniqid();
        mkdir($this->testCacheDir, 0755, true);
        $this->limiter = new UserRateLimiter($this->testCacheDir, 1); // Always cleanup for tests
    }

    protected function tearDown(): void
    {
        // Cleanup test cache directory
        $files = glob($this->testCacheDir . '/*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->testCacheDir);
    }

    /**
     * Test check returns true when within limit
     */
    public function testCheckReturnsTrueWhenWithinLimit(): void
    {
        $result = $this->limiter->check('test', 'user1', 5, 60);
        $this->assertTrue($result);
    }

    /**
     * Test check returns false when limit exceeded
     */
    public function testCheckReturnsFalseWhenLimitExceeded(): void
    {
        // Make 5 requests (limit is 5)
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('test', 'user1', 5, 60);
        }

        // 6th request should fail
        $result = $this->limiter->check('test', 'user1', 5, 60);
        $this->assertFalse($result);
    }

    /**
     * Test remaining returns correct count
     */
    public function testRemainingReturnsCorrectCount(): void
    {
        // Initial remaining should be max
        $remaining = $this->limiter->remaining('test', 'user1', 5, 60);
        $this->assertEquals(5, $remaining);

        // After 1 request
        $this->limiter->check('test', 'user1', 5, 60);
        $remaining = $this->limiter->remaining('test', 'user1', 5, 60);
        $this->assertEquals(4, $remaining);

        // After 3 requests
        $this->limiter->check('test', 'user1', 5, 60);
        $this->limiter->check('test', 'user1', 5, 60);
        $remaining = $this->limiter->remaining('test', 'user1', 5, 60);
        $this->assertEquals(2, $remaining);
    }

    /**
     * Test retryAfter returns 0 when no limit
     */
    public function testRetryAfterReturns0WhenNoLimit(): void
    {
        $retryAfter = $this->limiter->retryAfter('test', 'user1', 60);
        $this->assertEquals(0, $retryAfter);
    }

    /**
     * Test retryAfter returns positive when limited
     */
    public function testRetryAfterReturnsPositiveWhenLimited(): void
    {
        // Exceed limit
        for ($i = 0; $i < 6; $i++) {
            $this->limiter->check('test', 'user1', 5, 60);
        }

        $retryAfter = $this->limiter->retryAfter('test', 'user1', 60);
        $this->assertGreaterThan(0, $retryAfter);
    }

    /**
     * Test reset clears rate limit
     */
    public function testResetClearsRateLimit(): void
    {
        // Exceed limit
        for ($i = 0; $i < 6; $i++) {
            $this->limiter->check('test', 'user1', 5, 60);
        }

        // Verify limited
        $this->assertFalse($this->limiter->check('test', 'user1', 5, 60));

        // Reset
        $this->limiter->reset('test', 'user1');

        // Should work again
        $this->assertTrue($this->limiter->check('test', 'user1', 5, 60));
    }

    /**
     * Test getHeaders returns correct structure
     */
    public function testGetHeadersReturnsCorrectStructure(): void
    {
        $headers = $this->limiter->getHeaders('test', 'user1', 60, 60);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertEquals(60, $headers['X-RateLimit-Limit']);
        $this->assertEquals(60, $headers['X-RateLimit-Remaining']);
    }

    /**
     * Test different actions are independent
     */
    public function testDifferentActionsAreIndependent(): void
    {
        // Limit 'api' action
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('api', 'user1', 5, 60);
        }

        // 'search' action should still work
        $result = $this->limiter->check('search', 'user1', 5, 60);
        $this->assertTrue($result);
    }

    /**
     * Test different users are independent
     */
    public function testDifferentUsersAreIndependent(): void
    {
        // Limit user1
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('api', 'user1', 5, 60);
        }

        // user2 should still work
        $result = $this->limiter->check('api', 'user2', 5, 60);
        $this->assertTrue($result);
    }

    /**
     * Test default parameters
     */
    public function testDefaultParameters(): void
    {
        // Default: 60 requests per 60 seconds
        $result = $this->limiter->check('api', 'user1');
        $this->assertTrue($result);

        $remaining = $this->limiter->remaining('api', 'user1');
        $this->assertEquals(59, $remaining);
    }

    /**
     * Test custom limit
     */
    public function testCustomLimit(): void
    {
        // Custom: 3 requests per 60 seconds
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($this->limiter->check('custom', 'user1', 3, 60));
        }

        // 4th should fail
        $this->assertFalse($this->limiter->check('custom', 'user1', 3, 60));
    }
}
