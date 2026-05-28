<?php
declare(strict_types=1);

namespace App\Models;


/**
 * Order model for e-commerce orders.
 * Maps to 'donhang' table in legacy database.
 *
 * @property int $iddonhang
 * @property int $iduser
 * @property string $hoten
 * @property string $sodienthoai
 * @property string $email
 * @property string $diachi
 * @property string $ghichu
 * @property float $tongtien
 * @property int $trangthai
 * @property string $phuongthucthanhtoan
 * @property string $ngaydat
 * @property string $ngaygiao
 * @property string $magiamgia
 * @property float $phi_ship
 */
class Order extends BaseModel
{
    protected static $table = 'donhang';
    protected static $primaryKey = 'iddonhang';
    protected static $timestamps = false;
    protected static $fillable = [
        'iduser',
        'hoten',
        'sodienthoai',
        'email',
        'diachi',
        'ghichu',
        'tongtien',
        'trangthai',
        'phuongthucthanhtoan',
        'ngaydat',
        'ngaygiao',
        'magiamgia',
        'phi_ship',
    ];

    // Order status constants
    const STATUS_PENDING = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_SHIPPING = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_CANCELLED = 4;
    const STATUS_RETURNED = 5;

    /**
     * Find orders by customer ID.
     *
     * @return Order[]
     */
    public static function findByCustomer(int $customerId): array
    {
        return self::where('iduser', '=', $customerId);
    }

    /**
     * Find orders by status.
     *
     * @return Order[]
     */
    public static function findByStatus(int $status): array
    {
        return self::where('trangthai', '=', $status);
    }

    /**
     * Get order items.
     *
     * @return OrderItem[]
     */
    public function items(): array
    {
        return OrderItem::findByOrder((int)$this->getKey());
    }

    /**
     * Get customer who placed this order.
     * @return Customer|null
     */
    public function customer(): ?Customer
    {
        return Customer::find((int)$this->iduser);
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_SHIPPING => 'Đang giao hàng',
            self::STATUS_DELIVERED => 'Đã giao hàng',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_RETURNED => 'Đã trả hàng',
        ];
        return $labels[(int)$this->trangthai] ?? 'Không xác định';
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array((int)$this->trangthai, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Get formatted total with currency.
     */
    public function getFormattedTotal(): string
    {
        return number_format((float)$this->tongtien, 0, ',', '.') . 'đ';
    }
}
