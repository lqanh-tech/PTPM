<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\BaseModel;

/**
 * Test concrete implementation of BaseModel for testing.
 */
class TestModel extends BaseModel
{
    protected static $table = 'test_models';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;
    protected static $fillable = ['name', 'value'];
}

class BaseModelTest extends TestCase
{
    // ─── Configuration ─────────────────────────────────────────

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(TestModel::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('test_models', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(TestModel::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    public function testTimestampsDisabled(): void
    {
        $reflection = new \ReflectionClass(TestModel::class);
        $property = $reflection->getProperty('timestamps');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue());
    }

    public function testFillableAttributes(): void
    {
        $reflection = new \ReflectionClass(TestModel::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('name', $fillable);
        $this->assertContains('value', $fillable);
    }

    // ─── Attribute Management ──────────────────────────────────

    public function testFillSetsAttributes(): void
    {
        $model = new TestModel(['name' => 'test', 'value' => '123']);
        $this->assertEquals('test', $model->name);
        $this->assertEquals('123', $model->value);
    }

    public function testMagicGetterReturnsNullForMissing(): void
    {
        $model = new TestModel();
        $this->assertNull($model->nonexistent);
    }

    public function testMagicSetterSetsAttribute(): void
    {
        $model = new TestModel();
        $model->name = 'test';
        $this->assertEquals('test', $model->name);
    }

    public function testMagicIssetReturnsFalseForMissing(): void
    {
        $model = new TestModel();
        $this->assertFalse(isset($model->nonexistent));
    }

    public function testMagicIssetReturnsTrueForExisting(): void
    {
        $model = new TestModel(['name' => 'test']);
        $this->assertTrue(isset($model->name));
    }

    // ─── Array Conversion ──────────────────────────────────────

    public function testToArrayReturnsAttributes(): void
    {
        $model = new TestModel(['name' => 'test', 'value' => '123']);
        $array = $model->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test', $array['name']);
        $this->assertEquals('123', $array['value']);
    }

    public function testToJsonReturnsJsonString(): void
    {
        $model = new TestModel(['name' => 'test']);
        $json = $model->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('test', $decoded['name']);
    }

    // ─── Dirty Tracking ────────────────────────────────────────

    public function testIsDirtyReturnsFalseForUnchanged(): void
    {
        $model = new TestModel(['name' => 'test']);
        $this->assertFalse($model->isDirty('name'));
    }

    public function testIsDirtyReturnsTrueForChanged(): void
    {
        $model = new TestModel(['name' => 'test']);
        $model->name = 'changed';
        $this->assertTrue($model->isDirty('name'));
    }

    public function testGetDirtyReturnsOnlyChanged(): void
    {
        $model = new TestModel(['name' => 'test', 'value' => '123']);
        $model->name = 'changed';

        $dirty = $model->getDirty();
        $this->assertArrayHasKey('name', $dirty);
        $this->assertArrayNotHasKey('value', $dirty);
    }

    // ─── Exists State ──────────────────────────────────────────

    public function testExistsReturnsFalseByDefault(): void
    {
        $model = new TestModel();
        $this->assertFalse($model->exists());
    }

    public function testGetKeyReturnsNullByDefault(): void
    {
        $model = new TestModel();
        $this->assertNull($model->getKey());
    }

    // ─── Cache Management ──────────────────────────────────────

    public function testClearCacheDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        TestModel::clearCache();
    }

    public function testForgetCacheDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        TestModel::forgetCache(999);
    }

    public function testCacheableDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        TestModel::cacheable(true);
        TestModel::cacheable(false);
    }

    public function testIsCacheEnabledReturnsBool(): void
    {
        $this->assertIsBool(TestModel::isCacheEnabled());
    }

    public function testGetCacheStatsReturnsArray(): void
    {
        $stats = TestModel::getCacheStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('keys', $stats);
    }

    // ─── Validation ────────────────────────────────────────────

    public function testValidateColumnAcceptsValidName(): void
    {
        $reflection = new \ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('validateColumn');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'valid_column');
        $this->assertEquals('valid_column', $result);
    }

    public function testValidateColumnRejectsInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reflection = new \ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('validateColumn');
        $method->setAccessible(true);

        $method->invoke(null, 'invalid;column');
    }

    public function testValidateOperatorAcceptsValid(): void
    {
        $result = TestModel::validateOperator('=');
        $this->assertEquals('=', $result);
    }

    public function testValidateOperatorRejectsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TestModel::validateOperator('DROP TABLE');
    }
}
