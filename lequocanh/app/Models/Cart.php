<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Cart model for shopping cart functionality.
 * Maps to 'giohang' table in legacy database.
 *
 * @property int $idgiohang
 * @property int $iduser
 * @property int $idhanghoa
 * @property int $soluong
 * @property string $ngaythem
 */
class Cart extends BaseModel
{
    protected static $table = 'giohang';
    protected static $primaryKey = 'idgiohang';
    protected static $timestamps = false;
    protected static $fillable = [
        'iduser',
        'idhanghoa',
        'soluong',
        'ngaythem',
    ];

    /**
     * Find cart items for a user.
     *
     * @return Cart[]
     */
    public static function findByUser(int $userId): array
    {
        return self::where('iduser', '=', $userId);
    }

    /**
     * Find specific cart item for user and product.
     */
    public static function findItem(int $userId, int $productId): ?self
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM " . static::$table . " WHERE iduser = ? AND idhanghoa = ? LIMIT 1"
        );
        $stmt->execute([$userId, $productId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $model = new static($row);
        $model->exists = true;
        return $model;
    }

    /**
     * Get the product in this cart item.
     */
    public function product(): ?Product
    {
        return Product::find((int)$this->idhanghoa);
    }

    /**
     * Get cart item count for a user.
     */
    public static function countForUser(int $userId): int
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM " . static::$table . " WHERE iduser = ?"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Clear all items for a user.
     */
    public static function clearForUser(int $userId): bool
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "DELETE FROM " . static::$table . " WHERE iduser = ?"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Add item to cart (or update quantity if exists).
     */
    public static function addOrUpdate(int $userId, int $productId, int $quantity = 1): self
    {
        $existing = self::findItem($userId, $productId);

        if ($existing) {
            $existing->soluong = (int)$existing->soluong + $quantity;
            $existing->save();
            return $existing;
        }

        return self::create([
            'iduser' => $userId,
            'idhanghoa' => $productId,
            'soluong' => $quantity,
            'ngaythem' => date('Y-m-d H:i:s'),
        ]);
    }
}
