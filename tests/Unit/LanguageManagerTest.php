<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lequocanh/app/Services/LanguageManager.php';

class LanguageManagerTest extends TestCase
{
    // ─── Singleton ──────────────────────────────────────────────

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = \LanguageManager::getInstance();
        $instance2 = \LanguageManager::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function testGetInstanceReturnsLanguageManager(): void
    {
        $instance = \LanguageManager::getInstance();
        $this->assertInstanceOf(\LanguageManager::class, $instance);
    }

    // ─── Get Translation ────────────────────────────────────────

    public function testGetReturnsKeyIfNotFound(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->get('nonexistent_key_12345');
        $this->assertEquals('nonexistent_key_12345', $result);
    }

    public function testGetReturnsString(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->get('some_key');
        $this->assertIsString($result);
    }

    public function testGetWithReplaceParameters(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->get('Hello :name', ['name' => 'World']);
        $this->assertEquals('Hello World', $result);
    }

    public function testGetWithMultipleReplacements(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->get(':greeting :name', ['greeting' => 'Hi', 'name' => 'User']);
        $this->assertEquals('Hi User', $result);
    }

    // ─── Current Language ───────────────────────────────────────

    public function testGetCurrentLangReturnsString(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->getCurrentLang();
        $this->assertIsString($result);
    }

    public function testGetCurrentLangReturnsViOrEn(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->getCurrentLang();
        $this->assertContains($result, ['vi', 'en']);
    }

    // ─── Method Existence ───────────────────────────────────────

    public function testGetMethodExists(): void
    {
        $this->assertTrue(method_exists(\LanguageManager::class, 'get'));
    }

    public function testSetLanguageMethodExists(): void
    {
        $this->assertTrue(method_exists(\LanguageManager::class, 'setLanguage'));
    }

    public function testGetCurrentLangMethodExists(): void
    {
        $this->assertTrue(method_exists(\LanguageManager::class, 'getCurrentLang'));
    }

    // ─── Edge Cases ──────────────────────────────────────────────

    public function testGetWithEmptyKey(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->get('');
        $this->assertEquals('', $result);
    }

    public function testGetWithNoReplacements(): void
    {
        $manager = \LanguageManager::getInstance();
        $result = $manager->get('Hello World');
        $this->assertEquals('Hello World', $result);
    }
}
