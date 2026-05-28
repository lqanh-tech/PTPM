<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Order item model representing line items in an order.
 * Maps to 'donhang_chitiet' or 'order_items' table.
 *
 * @property int $id
 * @property int $iddonhang
 * @property int $idhanghoa
 * @property string $tenhanghoa
 * @property int $soluong
 * @property float $dongia
 * @property float $thanhtien
 * @property string $mausac
 * @property string $kichco
 */
class OrderItem extends BaseModel
{
    protected static $table = 'donhang_chitiet';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;
    protected static $fillable = [
        'iddonhang',
        'idhanghoa',
        'tenhanghoa',
        'soluong',
        'dongia',
        'thanhtien',
        'mausac',
        'kichco',
    ];

    /**
     * Find all items for a specific order.
     *
     * @return OrderItem[]
     */
    public static function findByOrder(int $orderId): array
    {
        return self::where('iddonhang', '=', $orderId);
    }

    /**
     * Get the product associated with this item.
     * @return Product|null
     */
    public function product(): ?Product
    {
        return Product::find((int)$this->idhanghoa);
    }

    /**
     * Get the parent order.
     * @return Order|null
     */
    public function order(): ?Order
    {
        return Order::find((int)$this->iddonhang);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(): string
    {
        return number_format((float)$this->dongia, 0, ',', '.') . 'đ';
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotal(): string
    {
        return number_format((float)$this->thanhtien, 0, ',', '.') . 'đ';
    }
}
