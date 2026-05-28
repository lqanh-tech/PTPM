<?php

declare(strict_types=1);

namespace App\Services;

use Database;
use PDO;

class CategoryService
{
    private static ?self $instance = null;
    private PDO $db;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __clone() {}

    /**
     * Get all categories ordered by name.
     */
    public function getAllCategories(): array
    {
        $sql = "SELECT idloaihang, tenloaihang, mota, hinhanh
                FROM loaihang 
                ORDER BY tenloaihang ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get category by ID.
     */
    public function getCategoryById(int $categoryId): ?object
    {
        $sql = "SELECT idloaihang, tenloaihang, mota, hinhanh
                FROM loaihang 
                WHERE idloaihang = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get category by slug.
     */
    public function getCategoryBySlug(string $slug): ?object
    {
        $sql = "SELECT idloaihang, tenloaihang, mota, hinhanh
                FROM loaihang 
                WHERE tenloaihang = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get categories with product count.
     */
    public function getCategoriesWithProductCount(): array
    {
        $sql = "SELECT l.idloaihang, l.tenloaihang, l.mota, l.hinhanh, l.slug,
                       COUNT(h.idhanghoa) as product_count
                FROM loaihang l
                LEFT JOIN hanghoa h ON l.idloaihang = h.idloaihang AND (h.trang_thai IS NULL OR h.trang_thai != 2)
                GROUP BY l.idloaihang, l.tenloaihang, l.mota, l.hinhanh, l.slug
                ORDER BY l.tenloaihang ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get categories with product count (only categories with products).
     */
    public function getActiveCategories(): array
    {
        $sql = "SELECT l.idloaihang, l.tenloaihang, l.mota, l.hinhanh, l.slug,
                       COUNT(h.idhanghoa) as product_count
                FROM loaihang l
                INNER JOIN hanghoa h ON l.idloaihang = h.idloaihang AND (h.trang_thai IS NULL OR h.trang_thai != 2)
                GROUP BY l.idloaihang, l.tenloaihang, l.mota, l.hinhanh, l.slug
                HAVING product_count > 0
                ORDER BY l.tenloaihang ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Create new category.
     */
    public function createCategory(array $data): int
    {
        $sql = "INSERT INTO loaihang (tenloaihang, mota, hinhanh) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['tenloaihang'],
            $data['mota'] ?? '',
            $data['hinhanh'] ?? null,
            $data['slug'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update category.
     */
    public function updateCategory(int $categoryId, array $data): bool
    {
        $allowedFields = ['tenloaihang', 'mota', 'hinhanh', 'slug'];
        $updateFields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateFields[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $params[] = $categoryId;
        $sql = "UPDATE loaihang SET " . implode(', ', $updateFields) . " WHERE idloaihang = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete category.
     */
    public function deleteCategory(int $categoryId): array
    {
        // Check if category has products
        $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idloaihang = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        if (($result->count ?? 0) > 0) {
            return [
                'success' => false,
                'message' => 'Không thể xóa danh mục vì còn sản phẩm liên quan',
                'product_count' => $result->count,
            ];
        }

        $sql = "DELETE FROM loaihang WHERE idloaihang = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);

        return [
            'success' => true,
            'message' => 'Xóa danh mục thành công',
        ];
    }

    /**
     * Search categories by keyword.
     */
    public function searchCategories(string $keyword): array
    {
        $sql = "SELECT idloaihang, tenloaihang, mota, hinhanh
                FROM loaihang 
                WHERE tenloaihang LIKE ? OR mota LIKE ?
                ORDER BY tenloaihang ASC
                LIMIT 20";

        $searchTerm = "%{$keyword}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get category count.
     */
    public function getCategoryCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM loaihang";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return (int) ($result->count ?? 0);
    }
}

if (!function_exists('getCategoryService')) {
    function getCategoryService(): CategoryService
    {
        return CategoryService::getInstance();
    }
}
