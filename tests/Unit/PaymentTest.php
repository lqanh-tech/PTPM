<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Payment;

class PaymentTest extends TestCase
{
    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Payment::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('thanhtoan', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Payment::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(Payment::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Payment::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('iddonhang', $fillable);
        $this->assertContains('phuongthuc', $fillable);
        $this->assertContains('sotien', $fillable);
        $this->assertContains('trangthai', $fillable);
        $this->assertContains('transaction_id', $fillable);
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals(0, Payment::STATUS_PENDING);
        $this->assertEquals(1, Payment::STATUS_COMPLETED);
        $this->assertEquals(2, Payment::STATUS_FAILED);
        $this->assertEquals(3, Payment::STATUS_REFUNDED);
    }
}
