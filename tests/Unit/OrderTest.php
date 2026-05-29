<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Order;

class OrderTest extends TestCase
{
    // ─── Constants ────────────────────────────────────────────────

    public function testStatusConstants(): void
    {
        $this->assertEquals(0, Order::STATUS_PENDING);
        $this->assertEquals(1, Order::STATUS_CONFIRMED);
        $this->assertEquals(2, Order::STATUS_SHIPPING);
        $this->assertEquals(3, Order::STATUS_DELIVERED);
        $this->assertEquals(4, Order::STATUS_CANCELLED);
        $this->assertEquals(5, Order::STATUS_RETURNED);
    }

    // ─── Table Configuration ──────────────────────────────────────

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Order::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('donhang', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Order::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('iddonhang', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(Order::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    // ─── Fillable Attributes ──────────────────────────────────────

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Order::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('iduser', $fillable);
        $this->assertContains('hoten', $fillable);
        $this->assertContains('sodienthoai', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('diachi', $fillable);
        $this->assertContains('tongtien', $fillable);
        $this->assertContains('trangthai', $fillable);
    }

    // ─── Status Methods ───────────────────────────────────────────

    public function testGetStatusLabelReturnsCorrectLabels(): void
    {
        $order = new Order();
        
        $order->trangthai = Order::STATUS_PENDING;
        $this->assertEquals('Chờ xác nhận', $order->getStatusLabel());

        $order->trangthai = Order::STATUS_CONFIRMED;
        $this->assertEquals('Đã xác nhận', $order->getStatusLabel());

        $order->trangthai = Order::STATUS_SHIPPING;
        $this->assertEquals('Đang giao hàng', $order->getStatusLabel());

        $order->trangthai = Order::STATUS_DELIVERED;
        $this->assertEquals('Đã giao hàng', $order->getStatusLabel());

        $order->trangthai = Order::STATUS_CANCELLED;
        $this->assertEquals('Đã hủy', $order->getStatusLabel());

        $order->trangthai = Order::STATUS_RETURNED;
        $this->assertEquals('Đã trả hàng', $order->getStatusLabel());
    }

    public function testGetStatusLabelReturnsUnknownForInvalid(): void
    {
        $order = new Order();
        $order->trangthai = 999;
        $this->assertEquals('Không xác định', $order->getStatusLabel());
    }

    // ─── Cancellation Logic ───────────────────────────────────────

    public function testCanBeCancelledWhenPending(): void
    {
        $order = new Order();
        $order->trangthai = Order::STATUS_PENDING;
        $this->assertTrue($order->canBeCancelled());
    }

    public function testCanBeCancelledWhenConfirmed(): void
    {
        $order = new Order();
        $order->trangthai = Order::STATUS_CONFIRMED;
        $this->assertTrue($order->canBeCancelled());
    }

    public function testCannotBeCancelledWhenShipping(): void
    {
        $order = new Order();
        $order->trangthai = Order::STATUS_SHIPPING;
        $this->assertFalse($order->canBeCancelled());
    }

    public function testCannotBeCancelledWhenDelivered(): void
    {
        $order = new Order();
        $order->trangthai = Order::STATUS_DELIVERED;
        $this->assertFalse($order->canBeCancelled());
    }

    public function testCannotBeCancelledWhenCancelled(): void
    {
        $order = new Order();
        $order->trangthai = Order::STATUS_CANCELLED;
        $this->assertFalse($order->canBeCancelled());
    }

    // ─── Formatting ───────────────────────────────────────────────

    public function testGetFormattedTotalFormatsCorrectly(): void
    {
        $order = new Order();
        $order->tongtien = 1500000;
        $this->assertEquals('1.500.000đ', $order->getFormattedTotal());
    }

    public function testGetFormattedTotalHandlesZero(): void
    {
        $order = new Order();
        $order->tongtien = 0;
        $this->assertEquals('0đ', $order->getFormattedTotal());
    }

    // ─── Static Methods Exist ─────────────────────────────────────

    public function testFindByCustomerMethodExists(): void
    {
        $this->assertTrue(method_exists(Order::class, 'findByCustomer'));
    }

    public function testFindByStatusMethodExists(): void
    {
        $this->assertTrue(method_exists(Order::class, 'findByStatus'));
    }

    public function testItemsMethodExists(): void
    {
        $this->assertTrue(method_exists(Order::class, 'items'));
    }

    public function testCustomerMethodExists(): void
    {
        $this->assertTrue(method_exists(Order::class, 'customer'));
    }
}
