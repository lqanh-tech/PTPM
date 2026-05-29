<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\OrderItem;

class OrderItemTest extends TestCase
{
    // ─── Table Configuration ──────────────────────────────────────

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(OrderItem::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('donhang_chitiet', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(OrderItem::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(OrderItem::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    // ─── Fillable Attributes ──────────────────────────────────────

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(OrderItem::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('iddonhang', $fillable);
        $this->assertContains('idhanghoa', $fillable);
        $this->assertContains('tenhanghoa', $fillable);
        $this->assertContains('soluong', $fillable);
        $this->assertContains('dongia', $fillable);
        $this->assertContains('thanhtien', $fillable);
    }

    // ─── Formatting ───────────────────────────────────────────────

    public function testGetFormattedPriceFormatsCorrectly(): void
    {
        $item = new OrderItem();
        $item->dongia = 250000;
        $this->assertEquals('250.000đ', $item->getFormattedPrice());
    }

    public function testGetFormattedPriceHandlesZero(): void
    {
        $item = new OrderItem();
        $item->dongia = 0;
        $this->assertEquals('0đ', $item->getFormattedPrice());
    }

    public function testGetFormattedSubtotalFormatsCorrectly(): void
    {
        $item = new OrderItem();
        $item->thanhtien = 750000;
        $this->assertEquals('750.000đ', $item->getFormattedSubtotal());
    }

    public function testGetFormattedSubtotalHandlesZero(): void
    {
        $item = new OrderItem();
        $item->thanhtien = 0;
        $this->assertEquals('0đ', $item->getFormattedSubtotal());
    }

    // ─── Static Methods Exist ─────────────────────────────────────

    public function testFindByOrderMethodExists(): void
    {
        $this->assertTrue(method_exists(OrderItem::class, 'findByOrder'));
    }

    public function testProductMethodExists(): void
    {
        $this->assertTrue(method_exists(OrderItem::class, 'product'));
    }

    public function testOrderMethodExists(): void
    {
        $this->assertTrue(method_exists(OrderItem::class, 'order'));
    }
}
