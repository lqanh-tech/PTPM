<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

/**
 * Wishlist Model - Quản lý sản phẩm yêu thích
 * Maps to 'wishlist' table.
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $created_at
 */
class Wishlist extends BaseModel
{
    protected static $table = 'wishlist';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;

    protected static $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * Add product to wishlist (ignore if exists).
     */
    public static function addProduct(int $userId, int $productId): bool
    {
        try {
            $existing = self::findItem($userId, $productId);
            if ($existing) {
                return true;
            }

            self::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            return true;
        } catch (\Exception $e) {
            Logger::error('Wishlist::addProduct', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Remove product from wishlist.
     */
    public static function removeProduct(int $userId, int $productId): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM " . static::$table . " WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$userId, $productId]);
        } catch (\Exception $e) {
            Logger::error('Wishlist::removeProduct', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if product is in user's wishlist.
     */
    public static function isWishlisted(int $userId, int $productId): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM " . static::$table . " WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Find specific wishlist item.
     */
    public static function findItem(int $userId, int $productId): ?self
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, user_id, product_id, created_at FROM " . static::$table . " WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $model = new static($row);
        $model->exists = true;
        return $model;
    }

    /**
     * Get wishlist items with product details for a user.
     */
    public static function getByUser(int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT w.id, w.product_id, w.created_at, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.hinhanh
                FROM " . static::$table . " w
                JOIN hanghoa h ON w.product_id = h.idhanghoa
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count wishlist items for a user.
     */
    public static function countForUser(int $userId): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM " . static::$table . " WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Toggle product in wishlist (add if missing, remove if exists).
     */
    public static function toggle(int $userId, int $productId): array
    {
        if (self::isWishlisted($userId, $productId)) {
            self::removeProduct($userId, $productId);
            return ['success' => true, 'action' => 'removed', 'wishlisted' => false];
        }

        self::addProduct($userId, $productId);
        return ['success' => true, 'action' => 'added', 'wishlisted' => true];
    }

    /**
     * Get the product for this wishlist item.
     */
    public function product(): ?Product
    {
        return Product::find((int) $this->product_id);
    }
}
