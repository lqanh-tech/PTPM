<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CacheManager;

class CacheManagerTest extends TestCase
{
    public function testHasGetInstanceMethod(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $this->assertTrue($reflection->hasMethod('getInstance'));
    }

    public function testHasRememberMethod(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $this->assertTrue($reflection->hasMethod('remember'));
    }

    public function testHasGetMethod(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $this->assertTrue($reflection->hasMethod('get'));
    }

    public function testHasSetMethod(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $this->assertTrue($reflection->hasMethod('set'));
    }

    public function testHasDeleteMethod(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $this->assertTrue($reflection->hasMethod('delete'));
    }

    public function testHasFlushMethod(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $this->assertTrue($reflection->hasMethod('flush'));
    }

    public function testDefaultTTL(): void
    {
        $reflection = new \ReflectionClass(CacheManager::class);
        $property = $reflection->getProperty('defaultTTL');
        $property->setAccessible(true);

        // Get instance to check default value
        $instance = CacheManager::getInstance();
        $this->assertEquals(300, $property->getValue($instance));
    }

    public function testSingletonPattern(): void
    {
        $instance1 = CacheManager::getInstance();
        $instance2 = CacheManager::getInstance();
        $this->assertSame($instance1, $instance2);
    }
}
