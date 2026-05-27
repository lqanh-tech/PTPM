<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Payment model for tracking payment transactions.
 * Maps to 'thanhtoan' table.
 *
 * @property int $id
 * @property int $iddonhang
 * @property string $phuongthuc
 * @property float $sotien
 * @property int $trangthai
 * @property string $transaction_id
 * @property string $ngaythanhtoan
 * @property string $ghichu
 */
class Payment extends BaseModel
{
    protected static $table = 'thanhtoan';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;
    protected static $fillable = [
        'iddonhang',
        'phuongthuc',
        'sotien',
        'trangthai',
        'transaction_id',
        'ngaythanhtoan',
        'ghichu',
    ];

    // Payment status constants
    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_FAILED = 2;
    const STATUS_REFUNDED = 3;

    // Payment method constants
    const METHOD_COD = 'cod';
    const METHOD_MOMO = 'momo';
    const METHOD_BANK = 'bank';
    const METHOD_VNPAY = 'vnpay';

    /**
     * Find payment by order ID.
     */
    public static function findByOrder(int $orderId): ?self
    {
        $results = self::where('iddonhang', '=', $orderId);
        return $results[0] ?? null;
    }

    /**
     * Find payments by status.
     *
     * @return Payment[]
     */
    public static function findByStatus(int $status): array
    {
        return self::where('trangthai', '=', $status);
    }

    /**
     * Get the order associated with this payment.
     */
    public function order(): ?Order
    {
        return Order::find((int)$this->iddonhang);
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Chờ thanh toán',
            self::STATUS_COMPLETED => 'Đã thanh toán',
            self::STATUS_FAILED => 'Thanh toán thất bại',
            self::STATUS_REFUNDED => 'Đã hoàn tiền',
        ];
        return $labels[(int)$this->trangthai] ?? 'Không xác định';
    }

    /**
     * Get human-readable method label.
     */
    public function getMethodLabel(): string
    {
        $labels = [
            self::METHOD_COD => 'Thanh toán khi nhận hàng',
            self::METHOD_MOMO => 'Ví MoMo',
            self::METHOD_BANK => 'Chuyển khoản ngân hàng',
            self::METHOD_VNPAY => 'VNPay',
        ];
        return $labels[$this->phuongthuc] ?? $this->phuongthuc;
    }

    /**
     * Mark payment as completed.
     */
    public function markCompleted(string $transactionId = ''): bool
    {
        $this->trangthai = self::STATUS_COMPLETED;
        $this->ngaythanhtoan = date('Y-m-d H:i:s');
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        return $this->save();
    }

    /**
     * Mark payment as failed.
     */
    public function markFailed(string $reason = ''): bool
    {
        $this->trangthai = self::STATUS_FAILED;
        if ($reason) {
            $this->ghichu = $reason;
        }
        return $this->save();
    }
}
