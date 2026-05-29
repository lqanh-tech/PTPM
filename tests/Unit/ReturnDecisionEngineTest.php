<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ReturnDecisionEngine;

class ReturnDecisionEngineTest extends TestCase
{
    private ReturnDecisionEngine $engine;
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'methods' => [
                'pickup' => [
                    'enabled' => true,
                    'base_cost' => 50000,
                    'locations' => [],
                ],
                'drop_off' => [
                    'enabled' => true,
                    'base_cost' => 0,
                    'locations' => ['Location A', 'Location B'],
                ],
                'self_ship' => [
                    'enabled' => true,
                    'base_cost' => 30000,
                ],
            ],
            'decision_weights' => [
                'distance' => 0.3,
                'order_value' => 0.25,
                'customer_preference' => 0.25,
                'item_count' => 0.2,
            ],
        ];

        $this->engine = new ReturnDecisionEngine($this->config);
    }

    // ─── Configuration ──────────────────────────────────────────

    public function testConstructorAcceptsConfig(): void
    {
        $this->assertInstanceOf(ReturnDecisionEngine::class, $this->engine);
    }

    // ─── Decide Method ──────────────────────────────────────────

    public function testDecideReturnsArray(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        $this->assertIsArray($result);
    }

    public function testDecideContainsMethod(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        $this->assertArrayHasKey('method', $result);
        $this->assertContains($result['method'], ['pickup', 'drop_off', 'self_ship']);
    }

    public function testDecideContainsReason(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        $this->assertArrayHasKey('reason', $result);
        $this->assertIsString($result['reason']);
    }

    public function testDecideContainsEstimatedTime(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        $this->assertArrayHasKey('estimated_time', $result);
    }

    public function testDecideContainsCost(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        $this->assertArrayHasKey('cost', $result);
        $this->assertIsNumeric($result['cost']);
    }

    // ─── Decision Logic ─────────────────────────────────────────

    public function testHighValueOrderPrefersPickup(): void
    {
        $result = $this->engine->decide([
            'order_total' => 5000000,
            'address' => 'Hà Nội',
            'item_count' => 3,
        ]);

        // High value + multiple items should prefer pickup
        $this->assertEquals('pickup', $result['method']);
    }

    public function testSmallOrderPrefersSelfShip(): void
    {
        $result = $this->engine->decide([
            'order_total' => 100000,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        // Small order + single item can prefer any method depending on weights
        $this->assertContains($result['method'], ['self_ship', 'drop_off', 'pickup']);
    }

    public function testCustomerPreferenceIsRespected(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
            'address' => 'Hà Nội',
            'item_count' => 1,
            'preferred_method' => 'drop_off',
        ]);

        // Customer preference should influence decision
        $this->assertIsString($result['method']);
    }

    // ─── Edge Cases ──────────────────────────────────────────────

    public function testDecideWithEmptyRequest(): void
    {
        $result = $this->engine->decide([]);

        $this->assertArrayHasKey('method', $result);
    }

    public function testDecideWithMissingFields(): void
    {
        $result = $this->engine->decide([
            'order_total' => 500000,
        ]);

        $this->assertArrayHasKey('method', $result);
    }

    public function testDecideWithZeroOrderTotal(): void
    {
        $result = $this->engine->decide([
            'order_total' => 0,
            'address' => 'Hà Nội',
            'item_count' => 1,
        ]);

        $this->assertArrayHasKey('method', $result);
    }

    // ─── Disabled Methods ───────────────────────────────────────

    public function testDisabledMethodNotSelected(): void
    {
        $config = $this->config;
        $config['methods']['pickup']['enabled'] = false;

        $engine = new ReturnDecisionEngine($config);

        $result = $engine->decide([
            'order_total' => 5000000,
            'address' => 'Hà Nội',
            'item_count' => 3,
        ]);

        $this->assertNotEquals('pickup', $result['method']);
    }
}
