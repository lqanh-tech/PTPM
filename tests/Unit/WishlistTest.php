<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Wishlist;

class WishlistTest extends TestCase
{
    public function testExtendsBaseModel(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->isSubclassOf(\App\Models\BaseModel::class));
    }

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('wishlist', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    public function testTimestampsEnabled(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue());
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('product_id', $fillable);
    }

    public function testHasAddProductMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('addProduct'));
    }

    public function testHasRemoveProductMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('removeProduct'));
    }

    public function testHasIsWishlistedMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('isWishlisted'));
    }

    public function testHasGetByUserMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('getByUser'));
    }

    public function testHasCountForUserMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('countForUser'));
    }

    public function testHasToggleMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('toggle'));
    }

    public function testHasProductMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('product'));
    }
}
