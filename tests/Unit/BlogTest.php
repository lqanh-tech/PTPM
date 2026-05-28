<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Blog;

class BlogTest extends TestCase
{
    public function testExtendsBaseModel(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $this->assertTrue($reflection->isSubclassOf(\App\Models\BaseModel::class));
    }

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('blog_posts', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    public function testTimestampsEnabled(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue());
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('title', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('content', $fillable);
        $this->assertContains('excerpt', $fillable);
        $this->assertContains('featured_image', $fillable);
        $this->assertContains('author', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function testHasGetPublishedMethod(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $this->assertTrue($reflection->hasMethod('getPublished'));
    }

    public function testHasFindBySlugMethod(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $this->assertTrue($reflection->hasMethod('findBySlug'));
    }

    public function testHasCreatePostMethod(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $this->assertTrue($reflection->hasMethod('createPost'));
    }

    public function testHasCountPublishedMethod(): void
    {
        $reflection = new \ReflectionClass(Blog::class);
        $this->assertTrue($reflection->hasMethod('countPublished'));
    }
}
