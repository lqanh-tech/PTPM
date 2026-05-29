<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lequocanh/app/Services/ImageOptimizer.php';

class ImageOptimizerTest extends TestCase
{
    // ─── Constructor ─────────────────────────────────────────────

    public function testConstructorWithDefaults(): void
    {
        $optimizer = new \ImageOptimizer();
        $this->assertInstanceOf(\ImageOptimizer::class, $optimizer);
    }

    public function testConstructorWithCustomParams(): void
    {
        $optimizer = new \ImageOptimizer(90, 800, 600);
        $this->assertInstanceOf(\ImageOptimizer::class, $optimizer);
    }

    // ─── Method Existence ───────────────────────────────────────

    public function testConvertToWebPMethodExists(): void
    {
        $this->assertTrue(method_exists(\ImageOptimizer::class, 'convertToWebP'));
    }

    public function testGetOptimizedPathMethodExists(): void
    {
        $this->assertTrue(method_exists(\ImageOptimizer::class, 'getOptimizedPath'));
    }

    public function testGenerateSrcSetMethodExists(): void
    {
        $this->assertTrue(method_exists(\ImageOptimizer::class, 'generateSrcSet'));
    }

    // ─── Edge Cases ──────────────────────────────────────────────

    public function testConvertToWebPReturnsNullForMissingFile(): void
    {
        $optimizer = new \ImageOptimizer();
        $result = $optimizer->convertToWebP('/nonexistent/path/image.jpg');
        $this->assertNull($result);
    }

    public function testGetOptimizedPathReturnsString(): void
    {
        $optimizer = new \ImageOptimizer();
        $result = $optimizer->getOptimizedPath('/nonexistent/path/image.jpg');
        $this->assertIsString($result);
    }

    // ─── Configuration ──────────────────────────────────────────

    public function testDefaultQuality(): void
    {
        $optimizer = new \ImageOptimizer();
        $reflection = new \ReflectionClass($optimizer);
        $prop = $reflection->getProperty('quality');
        $prop->setAccessible(true);
        $this->assertEquals(85, $prop->getValue($optimizer));
    }

    public function testDefaultMaxWidth(): void
    {
        $optimizer = new \ImageOptimizer();
        $reflection = new \ReflectionClass($optimizer);
        $prop = $reflection->getProperty('maxWidth');
        $prop->setAccessible(true);
        $this->assertEquals(1200, $prop->getValue($optimizer));
    }

    public function testDefaultMaxHeight(): void
    {
        $optimizer = new \ImageOptimizer();
        $reflection = new \ReflectionClass($optimizer);
        $prop = $reflection->getProperty('maxHeight');
        $prop->setAccessible(true);
        $this->assertEquals(1200, $prop->getValue($optimizer));
    }

    public function testCustomQuality(): void
    {
        $optimizer = new \ImageOptimizer(95);
        $reflection = new \ReflectionClass($optimizer);
        $prop = $reflection->getProperty('quality');
        $prop->setAccessible(true);
        $this->assertEquals(95, $prop->getValue($optimizer));
    }
}
