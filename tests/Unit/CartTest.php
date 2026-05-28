<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Cart;

class CartTest extends TestCase
{
    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('giohang', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('idgiohang', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('iduser', $fillable);
        $this->assertContains('idhanghoa', $fillable);
        $this->assertContains('soluong', $fillable);
        $this->assertContains('ngaythem', $fillable);
    }

    public function testHasFindByUserMethod(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $this->assertTrue($reflection->hasMethod('findByUser'));
    }

    public function testHasFindItemMethod(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $this->assertTrue($reflection->hasMethod('findItem'));
    }

    public function testHasAddOrUpdateMethod(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $this->assertTrue($reflection->hasMethod('addOrUpdate'));
    }

    public function testHasClearForUserMethod(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $this->assertTrue($reflection->hasMethod('clearForUser'));
    }

    public function testHasCountForUserMethod(): void
    {
        $reflection = new \ReflectionClass(Cart::class);
        $this->assertTrue($reflection->hasMethod('countForUser'));
    }
}
