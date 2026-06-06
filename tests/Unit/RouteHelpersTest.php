<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lequocanh/includes/route_helpers.php';
require_once __DIR__ . '/../../lequocanh/config/ConfigManager.php';

class RouteHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure ConfigManager has a base URL
        $config = \ConfigManager::getInstance();
        if (!$config->get('base_url')) {
            $config->set('base_url', 'https://example.com');
        }
    }

    public function testRouteWithEmptyString(): void
    {
        $result = route('');
        $this->assertStringContainsString('example.com', $result);
    }

    public function testRouteWithPath(): void
    {
        $result = route('product/123');
        $this->assertStringContainsString('example.com', $result);
        $this->assertStringContainsString('/product/123', $result);
    }

    public function testRouteWithLeadingSlash(): void
    {
        $result = route('/product/123');
        $this->assertStringContainsString('/product/123', $result);
        $this->assertStringNotContainsString('//product', $result);
    }

    public function testAssetUrl(): void
    {
        $result = asset_url('css/app.css');
        $this->assertStringContainsString('example.com', $result);
        $this->assertStringContainsString('/lequocanh/public_files/css/app.css', $result);
    }

    public function testAdminUrl(): void
    {
        $result = admin_url('index.php?req=hanghoaview');
        $this->assertStringContainsString('example.com', $result);
        $this->assertStringContainsString('/lequocanh/administrator/index.php?req=hanghoaview', $result);
    }
}
