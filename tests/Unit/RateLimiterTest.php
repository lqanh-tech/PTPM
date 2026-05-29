<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\RateLimiter;

class RateLimiterTest extends TestCase
{
    // ─── Singleton ──────────────────────────────────────────────

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = RateLimiter::getInstance();
        $instance2 = RateLimiter::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testGetInstanceReturnsRateLimiter(): void
    {
        $instance = RateLimiter::getInstance();
        $this->assertInstanceOf(RateLimiter::class, $instance);
    }

    // ─── Configuration ──────────────────────────────────────────

    public function testDefaultMaxRequests(): void
    {
        $limiter = RateLimiter::getInstance();
        
        $reflection = new \ReflectionClass($limiter);
        $property = $reflection->getProperty('maxRequests');
        $property->setAccessible(true);
        
        $this->assertEquals(60, $property->getValue($limiter));
    }

    public function testDefaultWindowSeconds(): void
    {
        $limiter = RateLimiter::getInstance();
        
        $reflection = new \ReflectionClass($limiter);
        $property = $reflection->getProperty('windowSeconds');
        $property->setAccessible(true);
        
        $this->assertEquals(60, $property->getValue($limiter));
    }

    // ─── Method Existence ───────────────────────────────────────

    public function testIsAllowedMethodExists(): void
    {
        $this->assertTrue(method_exists(RateLimiter::class, 'isAllowed'));
    }

    public function testGetRemainingMethodExists(): void
    {
        $this->assertTrue(method_exists(RateLimiter::class, 'getRemaining'));
    }

    public function testGetResetTimeMethodExists(): void
    {
        $this->assertTrue(method_exists(RateLimiter::class, 'getResetTime'));
    }

    // ─── Type Checks ────────────────────────────────────────────

    public function testIsAllowedReturnsBool(): void
    {
        $limiter = RateLimiter::getInstance();
        $result = $limiter->isAllowed('test-user');
        
        $this->assertIsBool($result);
    }

    public function testGetRemainingReturnsInt(): void
    {
        $limiter = RateLimiter::getInstance();
        $result = $limiter->getRemaining('test-user');
        
        $this->assertIsInt($result);
    }

    public function testGetResetTimeReturnsInt(): void
    {
        $limiter = RateLimiter::getInstance();
        $result = $limiter->getResetTime('test-user');
        
        $this->assertIsInt($result);
    }

    // ─── Edge Cases ──────────────────────────────────────────────

    public function testDifferentIdentifiersTracked(): void
    {
        $limiter = RateLimiter::getInstance();
        
        $remaining1 = $limiter->getRemaining('user-test-1');
        $remaining2 = $limiter->getRemaining('user-test-2');
        
        // Both should have remaining requests
        $this->assertGreaterThanOrEqual(0, $remaining1);
        $this->assertLessThanOrEqual(60, $remaining1);
        $this->assertGreaterThanOrEqual(0, $remaining2);
        $this->assertLessThanOrEqual(60, $remaining2);
    }

    public function testEmptyIdentifier(): void
    {
        $limiter = RateLimiter::getInstance();
        
        $result = $limiter->isAllowed('');
        $this->assertIsBool($result);
    }

    public function testSpecialCharactersInIdentifier(): void
    {
        $limiter = RateLimiter::getInstance();
        
        $result = $limiter->isAllowed('user@domain.com');
        $this->assertIsBool($result);
    }
}
