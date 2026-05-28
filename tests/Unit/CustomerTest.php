<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Customer;

class CustomerTest extends TestCase
{
    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(Customer::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('user', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(Customer::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('iduser', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(Customer::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(Customer::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('username', $fillable);
        $this->assertContains('hoten', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }
}
