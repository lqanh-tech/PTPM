<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

/**
 * Return request model for handling product returns/exchanges.
 * Maps to 'doi_tra' table.
 *
 * @property int $id
 * @property int $ma_don_hang
 * @property string $ma_nguoi_dung
 * @property string $ly_do
 * @property string $loai
 * @property string $hinh_anh
 * @property string $trang_thai
 * @property string $ghi_chu_admin
 * @property string $ngay_tao
 * @property string $ngay_cap_nhat
 */
class ReturnRequest extends BaseModel
{
    protected static $table = 'doi_tra';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'ma_don_hang',
        'ma_nguoi_dung',
        'ly_do',
        'loai',
        'hinh_anh',
        'trang_thai',
        'ghi_chu_admin',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';

    // Type constants
    const TYPE_RETURN = 'return';
    const TYPE_EXCHANGE = 'exchange';

    /**
     * Create a new return request.
     */
    public static function createRequest(int $orderId, string $userId, string $reason, string $type = 'return', ?string $images = null): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "INSERT INTO doi_tra (ma_don_hang, ma_nguoi_dung, ly_do, loai, hinh_anh, trang_thai, ngay_tao) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$orderId, $userId, $reason, $type, $images, self::STATUS_PENDING]);
        } catch (\Exception $e) {
            error_log("ReturnRequest::createRequest error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get return requests by user.
     */
    public static function getByUser(string $userId): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien 
                    FROM doi_tra dt
                    JOIN don_hang dh ON dt.ma_don_hang = dh.id
                    WHERE dt.ma_nguoi_dung = ?
                    ORDER BY dt.ngay_tao DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ReturnRequest::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all return requests (admin view).
     */
    public static function getAllRequests(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien, u.hoten as ten_khach_hang
                    FROM doi_tra dt
                    JOIN don_hang dh ON dt.ma_don_hang = dh.id
                    LEFT JOIN users u ON dt.ma_nguoi_dung = u.username
                    ORDER BY dt.ngay_tao DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ReturnRequest::getAllRequests error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get return request by ID with order details.
     */
    public static function getRequestById(int $id): ?array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien, dh.dia_chi_giao_hang
                    FROM doi_tra dt
                    JOIN don_hang dh ON dt.ma_don_hang = dh.id
                    WHERE dt.id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("ReturnRequest::getRequestById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update return request status.
     */
    public static function updateStatus(int $id, string $status, ?string $adminNote = null): bool
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_PROCESSING, self::STATUS_COMPLETED])) {
            return false;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE doi_tra SET trang_thai = ?, ghi_chu_admin = ?, ngay_cap_nhat = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$status, $adminNote, $id]);
        } catch (\Exception $e) {
            error_log("ReturnRequest::updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get status label in Vietnamese.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_COMPLETED => 'Hoàn thành',
        ];
        return $labels[$this->trang_thai] ?? 'Không xác định';
    }

    /**
     * Get type label in Vietnamese.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_RETURN => 'Trả hàng',
            self::TYPE_EXCHANGE => 'Đổi hàng',
        ];
        return $labels[$this->loai] ?? 'Không xác định';
    }

    /**
     * Check if request can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->trang_thai === self::STATUS_PENDING;
    }

    /**
     * Check if request can be rejected.
     */
    public function canBeRejected(): bool
    {
        return $this->trang_thai === self::STATUS_PENDING;
    }

    /**
     * Check if request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->trang_thai === self::STATUS_COMPLETED;
    }
}
