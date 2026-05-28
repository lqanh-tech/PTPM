<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Wishlist;

class WishlistTest extends TestCase
{
    public function testHasAddMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('add'));
    }

    public function testHasRemoveMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('remove'));
    }

    public function testHasGetByUserMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('getByUser'));
    }

    public function testHasIsInWishlistMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('isInWishlist'));
    }

    public function testHasCountForUserMethod(): void
    {
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertTrue($reflection->hasMethod('countForUser'));
    }

    public function testDoesNotExtendBaseModel(): void
    {
        // Wishlist currently doesn't extend BaseModel - this documents that
        $reflection = new \ReflectionClass(Wishlist::class);
        $this->assertFalse($reflection->isSubclassOf(\App\Models\BaseModel::class));
    }
}
