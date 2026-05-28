<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Banner;

class BannerTest extends TestCase
{
    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Banner::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('banners', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Banner::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    public function testTimestampsEnabled(): void
    {
        $reflection = new \ReflectionClass(Banner::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue());
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Banner::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('title', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('image_url', $fillable);
        $this->assertContains('link_url', $fillable);
        $this->assertContains('position', $fillable);
        $this->assertContains('is_active', $fillable);
    }
}
