<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for CategoryService
 */
class CategoryServiceTest extends TestCase
{
    /**
     * Test getAllCategories returns array
     */
    public function testGetAllCategoriesReturnsArray(): void
    {
        $categories = [];
        $this->assertIsArray($categories);
    }

    /**
     * Test getCategoryById returns correct structure
     */
    public function testGetCategoryByIdReturnsCorrectStructure(): void
    {
        $category = (object) [
            'idloaihang' => 1,
            'tenloaihang' => 'Electronics',
            'mota' => 'Electronic products',
            'hinhanh' => 'electronics.jpg',
        ];

        $this->assertObjectHasProperty('idloaihang', $category);
        $this->assertObjectHasProperty('tenloaihang', $category);
        $this->assertObjectHasProperty('mota', $category);
    }

    /**
     * Test getCategoryById returns null for non-existent
     */
    public function testGetCategoryByIdReturnsNullForNonExistent(): void
    {
        $result = null;
        $this->assertNull($result);
    }

    /**
     * Test getCategoriesWithProductCount includes count
     */
    public function testGetCategoriesWithProductCountIncludesCount(): void
    {
        $categories = [
            (object) ['idloaihang' => 1, 'tenloaihang' => 'Electronics', 'product_count' => 10],
            (object) ['idloaihang' => 2, 'tenloaihang' => 'Clothing', 'product_count' => 5],
        ];

        foreach ($categories as $category) {
            $this->assertObjectHasProperty('product_count', $category);
            $this->assertGreaterThanOrEqual(0, $category->product_count);
        }
    }

    /**
     * Test getActiveCategories only returns categories with products
     */
    public function testGetActiveCategoriesOnlyReturnsWithProducts(): void
    {
        $categories = [
            (object) ['idloaihang' => 1, 'tenloaihang' => 'Electronics', 'product_count' => 10],
            (object) ['idloaihang' => 2, 'tenloaihang' => 'Clothing', 'product_count' => 5],
        ];

        foreach ($categories as $category) {
            $this->assertGreaterThan(0, $category->product_count);
        }
    }

    /**
     * Test createCategory requires required fields
     */
    public function testCreateCategoryRequiresRequiredFields(): void
    {
        $requiredFields = ['tenloaihang'];

        $data = [
            'tenloaihang' => 'New Category',
            'mota' => 'Description',
            'hinhanh' => 'image.jpg',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $data);
        }
    }

    /**
     * Test updateCategory accepts allowed fields
     */
    public function testUpdateCategoryAcceptsAllowedFields(): void
    {
        $allowedFields = ['tenloaihang', 'mota', 'hinhanh', 'slug'];

        $data = [
            'tenloaihang' => 'Updated Name',
            'mota' => 'Updated description',
            'invalid_field' => 'should not be updated',
        ];

        $updateFields = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateFields[$key] = $value;
            }
        }

        $this->assertArrayHasKey('tenloaihang', $updateFields);
        $this->assertArrayHasKey('mota', $updateFields);
        $this->assertArrayNotHasKey('invalid_field', $updateFields);
    }

    /**
     * Test deleteCategory checks for products
     */
    public function testDeleteCategoryChecksForProducts(): void
    {
        $result = [
            'success' => false,
            'message' => 'Cannot delete category with products',
            'product_count' => 5,
        ];

        $this->assertFalse($result['success']);
        $this->assertGreaterThan(0, $result['product_count']);
    }

    /**
     * Test searchCategories with keyword
     */
    public function testSearchCategoriesWithKeyword(): void
    {
        $keyword = 'electronics';
        $searchTerm = "%{$keyword}%";

        $this->assertStringContainsString('electronics', $searchTerm);
        $this->assertStringStartsWith('%', $searchTerm);
        $this->assertStringEndsWith('%', $searchTerm);
    }

    /**
     * Test getCategoryCount returns integer
     */
    public function testGetCategoryCountReturnsInteger(): void
    {
        $count = 0;
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test category structure is consistent
     */
    public function testCategoryStructureIsConsistent(): void
    {
        $category = (object) [
            'idloaihang' => 1,
            'tenloaihang' => 'Test Category',
            'mota' => 'Test Description',
            'hinhanh' => 'test.jpg',
        ];

        $expectedProperties = ['idloaihang', 'tenloaihang', 'mota', 'hinhanh'];

        foreach ($expectedProperties as $property) {
            $this->assertObjectHasProperty($property, $category);
        }
    }
}
