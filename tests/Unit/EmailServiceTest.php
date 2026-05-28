<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\EmailService;

class EmailServiceTest extends TestCase
{
    public function testHasSendOrderConfirmationMethod(): void
    {
        $reflection = new \ReflectionClass(EmailService::class);
        $this->assertTrue($reflection->hasMethod('sendOrderConfirmation'));
    }

    public function testHasSendOrderStatusUpdateMethod(): void
    {
        $reflection = new \ReflectionClass(EmailService::class);
        $this->assertTrue($reflection->hasMethod('sendOrderStatusUpdate'));
    }

    public function testSendOrderConfirmationSignature(): void
    {
        $method = new \ReflectionMethod(EmailService::class, 'sendOrderConfirmation');
        $params = $method->getParameters();

        $this->assertCount(3, $params);
        $this->assertEquals('toEmail', $params[0]->getName());
        $this->assertEquals('customerName', $params[1]->getName());
        $this->assertEquals('order', $params[2]->getName());
    }
}
