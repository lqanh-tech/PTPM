<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Product;
use Database;

class ProductBulkDeleteTest extends TestCase
{
    private \PDO $db;

    protected function setUp(): void
    {
        // Reset mock DB to a fresh in-memory SQLite
        $this->db = Database::getInstance()->getConnection();
        $this->db->exec('PRAGMA foreign_keys = ON');

        // Create minimal schema for tests
        $this->db->exec('DROP TABLE IF EXISTS hanghoa');
        $this->db->exec('DROP TABLE IF EXISTS tonkho');
        $this->db->exec('DROP TABLE IF EXISTS chitietgiohang');
        $this->db->exec('CREATE TABLE hanghoa (idhanghoa INTEGER PRIMARY KEY, tenhanghoa TEXT)');
        $this->db->exec('CREATE TABLE tonkho (id INTEGER PRIMARY KEY AUTOINCREMENT, idhanghoa INTEGER, soLuong INTEGER DEFAULT 0)');
        $this->db->exec('CREATE TABLE chitietgiohang (id INTEGER PRIMARY KEY AUTOINCREMENT, idhanghoa INTEGER)');
    }

    public function testBulkDeleteMethodExists(): void
    {
        $this->assertTrue(
            method_exists(Product::class, 'bulkDelete'),
            'Product::bulkDelete must be defined'
        );
    }

    public function testBulkDeleteEmptyArrayReturnsNoOp(): void
    {
        $result = Product::bulkDelete([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['deleted']);
        $this->assertSame([], $result['errors']);
    }

    public function testBulkDeleteValidIdsDeletesRows(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (2, 'B')");
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (3, 'C')");

        $result = Product::bulkDelete([1, 2]);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['deleted']);
        $this->assertSame([], $result['errors']);

        $remaining = $this->db->query('SELECT idhanghoa FROM hanghoa ORDER BY idhanghoa')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame([3], $remaining);
    }

    public function testBulkDeleteMissingIdsReturnsZeroDeleted(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");

        $result = Product::bulkDelete([99, 100]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['deleted']);
        $this->assertSame([], $result['errors']);
    }

    public function testBulkDeleteRelatedDataBlocksThatId(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (2, 'B')");
        $this->db->exec("INSERT INTO chitietgiohang (idhanghoa) VALUES (1)");

        $result = Product::bulkDelete([1, 2]);

        // id=1 blocked (has cart items), id=2 deleted
        $this->assertIsArray($result);
        $this->assertSame(1, $result['deleted']);
        $this->assertArrayHasKey(1, $result['errors']);
        $this->assertStringContainsString('Related data', $result['errors'][1]);

        $remaining = $this->db->query('SELECT idhanghoa FROM hanghoa ORDER BY idhanghoa')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame([1], $remaining);
    }

    public function testBulkDeleteAllBlockedReturnsFailure(): void
    {
        $this->db->exec("INSERT INTO hanghoa (idhanghoa, tenhanghoa) VALUES (1, 'A')");
        $this->db->exec("INSERT INTO chitietgiohang (idhanghoa) VALUES (1)");

        $result = Product::bulkDelete([1]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['deleted']);
        $this->assertArrayHasKey(1, $result['errors']);
    }
}
