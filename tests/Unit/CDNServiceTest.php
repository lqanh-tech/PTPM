<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CDNService;

class CDNServiceTest extends TestCase
{
    // ─── Constructor ─────────────────────────────────────────────

    public function testConstructorWithDefaults(): void
    {
        $cdn = new CDNService();
        $this->assertInstanceOf(CDNService::class, $cdn);
    }

    public function testConstructorWithUrl(): void
    {
        $cdn = new CDNService('https://cdn.example.com');
        $this->assertInstanceOf(CDNService::class, $cdn);
    }

    public function testConstructorWithAllParams(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true, true);
        $this->assertInstanceOf(CDNService::class, $cdn);
    }

    // ─── URL Method ─────────────────────────────────────────────

    public function testUrlReturnsPathWhenDisabled(): void
    {
        $cdn = new CDNService('', false);
        $this->assertEquals('/images/test.jpg', $cdn->url('/images/test.jpg'));
    }

    public function testUrlReturnsPathWhenEmptyUrl(): void
    {
        $cdn = new CDNService('', true);
        $this->assertEquals('/images/test.jpg', $cdn->url('/images/test.jpg'));
    }

    public function testUrlReturnsCdnUrlWhenEnabled(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true);
        $this->assertEquals('https://cdn.example.com/images/test.jpg', $cdn->url('/images/test.jpg'));
    }

    public function testUrlHandlesMissingLeadingSlash(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true);
        $this->assertEquals('https://cdn.example.com/images/test.jpg', $cdn->url('images/test.jpg'));
    }

    public function testUrlTrimsTrailingSlashFromCdnUrl(): void
    {
        $cdn = new CDNService('https://cdn.example.com/', true);
        $this->assertEquals('https://cdn.example.com/images/test.jpg', $cdn->url('/images/test.jpg'));
    }

    // ─── Image Method ───────────────────────────────────────────

    public function testImageReturnsPathWhenDisabled(): void
    {
        $cdn = new CDNService('', false, false);
        $this->assertEquals('/images/test.jpg', $cdn->image('/images/test.jpg'));
    }

    public function testImageReturnsCdnUrlWhenOptimizationDisabled(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true, false);
        $this->assertEquals('https://cdn.example.com/images/test.jpg', $cdn->image('/images/test.jpg'));
    }

    public function testImageReturnsOptimizedUrl(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true, true);
        $result = $cdn->image('/images/test.jpg', 800, 80, 'auto');
        
        $this->assertStringContainsString('https://cdn.example.com/cdn-cgi/image/', $result);
        $this->assertStringContainsString('width=800', $result);
        $this->assertStringContainsString('quality=80', $result);
        $this->assertStringContainsString('format=auto', $result);
    }

    public function testImageDefaultParams(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true, true);
        $result = $cdn->image('/images/test.jpg');
        
        $this->assertStringContainsString('quality=80', $result);
        $this->assertStringContainsString('format=auto', $result);
    }

    public function testImageWithWidth(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true, true);
        $result = $cdn->image('/images/test.jpg', 1200);
        
        $this->assertStringContainsString('width=1200', $result);
    }

    public function testImageWithoutWidth(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true, true);
        $result = $cdn->image('/images/test.jpg', 0);
        
        $this->assertStringNotContainsString('width=', $result);
    }

    // ─── Edge Cases ──────────────────────────────────────────────

    public function testUrlWithEmptyPath(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true);
        $this->assertEquals('https://cdn.example.com/', $cdn->url(''));
    }

    public function testUrlWithSpecialCharacters(): void
    {
        $cdn = new CDNService('https://cdn.example.com', true);
        $result = $cdn->url('/images/file with spaces.jpg');
        
        $this->assertStringContainsString('file with spaces', $result);
    }
}
