<?php
// Danh sách các đường dẫn có thể đến file database.php
$paths = [
    '../../elements_LQA/mod/database.php',
    './elements_LQA/mod/database.php',
    './administrator/elements_LQA/mod/database.php',
    '../mod/database.php'
];

$loaded = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    error_log("Không thể tải file database.php trong thongbaoCls.php");
}

class ThongBao
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lấy số lượng thông báo đơn hàng chưa đọc của người dùng
     *
     * @param string $userId Username của người dùng
     * @return int Số lượng thông báo chưa đọc
     */
    public function getUnreadNotificationCount($userId)
    {
        try {
            // Kiểm tra xem cột notification_hidden có tồn tại không
            $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'notification_hidden'";
            $checkColumnStmt = $this->db->prepare($checkColumnSql);
            $checkColumnStmt->execute();
            $hasNotificationHiddenColumn = ($checkColumnStmt->rowCount() > 0);

            // Tạo câu truy vấn SQL dựa trên việc có cột notification_hidden hay không
            if ($hasNotificationHiddenColumn) {
                // Lấy số lượng đơn hàng có trạng thái mới (pending, approved, cancelled) mà người dùng chưa đọc và chưa ẩn
                $sql = "SELECT COUNT(*) as count FROM don_hang
                        WHERE ma_nguoi_dung = ?
                        AND (notification_hidden = 0 OR notification_hidden IS NULL)
                        AND (
                            (trang_thai = 'pending' AND pending_read = 0) OR
                            (trang_thai = 'approved' AND approved_read = 0) OR
                            (trang_thai = 'cancelled' AND cancelled_read = 0)
                        )";
            } else {
                // Lấy số lượng đơn hàng có trạng thái mới (pending, approved, cancelled) mà người dùng chưa đọc
                $sql = "SELECT COUNT(*) as count FROM don_hang
                        WHERE ma_nguoi_dung = ?
                        AND (
                            (trang_thai = 'pending' AND pending_read = 0) OR
                            (trang_thai = 'approved' AND approved_read = 0) OR
                            (trang_thai = 'cancelled' AND cancelled_read = 0)
                        )";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy số lượng thông báo: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy danh sách thông báo đơn hàng của người dùng
     *
     * @param string $userId Username của người dùng
     * @return array Danh sách thông báo
     */
    public function getUserNotifications($userId)
    {
        try {
            // Kiểm tra xem cột notification_hidden có tồn tại không
            $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'notification_hidden'";
            $checkColumnStmt = $this->db->prepare($checkColumnSql);
            $checkColumnStmt->execute();
            $hasNotificationHiddenColumn = ($checkColumnStmt->rowCount() > 0);

            // Tạo câu truy vấn SQL dựa trên việc có cột notification_hidden hay không
            if ($hasNotificationHiddenColumn) {
                $sql = "SELECT id, ma_don_hang_text as order_code, trang_thai as status, tong_tien as total_amount, ngay_tao as created_at, ngay_cap_nhat as updated_at,
                        CASE
                            WHEN trang_thai = 'pending' THEN pending_read
                            WHEN trang_thai = 'approved' THEN approved_read
                            WHEN trang_thai = 'cancelled' THEN cancelled_read
                        END as is_read
                        FROM don_hang
                        WHERE ma_nguoi_dung = ? AND (notification_hidden = 0 OR notification_hidden IS NULL)
                        ORDER BY ngay_cap_nhat DESC
                        LIMIT 10";
            } else {
                $sql = "SELECT id, ma_don_hang_text as order_code, trang_thai as status, tong_tien as total_amount, ngay_tao as created_at, ngay_cap_nhat as updated_at,
                        CASE
                            WHEN trang_thai = 'pending' THEN pending_read
                            WHEN trang_thai = 'approved' THEN approved_read
                            WHEN trang_thai = 'cancelled' THEN cancelled_read
                        END as is_read
                        FROM don_hang
                        WHERE ma_nguoi_dung = ?
                        ORDER BY ngay_cap_nhat DESC
                        LIMIT 10";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách thông báo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Đánh dấu thông báo đơn hàng đã đọc
     *
     * @param int $orderId ID của đơn hàng
     * @param string $status Trạng thái của đơn hàng (pending, approved, cancelled)
     * @param string $userId Username của người dùng
     * @return bool Kết quả cập nhật
     */
    public function markNotificationAsRead($orderId, $status, $userId)
    {
        try {
            // Kiểm tra xem đơn hàng có thuộc về người dùng không
            $checkSql = "SELECT id FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$orderId, $userId]);

            if ($checkStmt->rowCount() == 0) {
                // Đơn hàng không thuộc về người dùng này
                error_log("Người dùng $userId không có quyền đánh dấu đã đọc đơn hàng $orderId");
                return false;
            }

            $field = '';
            switch ($status) {
                case 'pending':
                    $field = 'pending_read';
                    break;
                case 'approved':
                    $field = 'approved_read';
                    break;
                case 'cancelled':
                    $field = 'cancelled_read';
                    break;
                default:
                    return false;
            }

            $sql = "UPDATE don_hang SET $field = 1 WHERE id = ? AND ma_nguoi_dung = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$orderId, $userId]);
        } catch (PDOException $e) {
            error_log("Lỗi khi đánh dấu thông báo đã đọc: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Đánh dấu tất cả thông báo đơn hàng của người dùng đã đọc
     *
     * @param string $userId Username của người dùng
     * @return bool Kết quả cập nhật
     */
    public function markAllNotificationsAsRead($userId)
    {
        try {
            $sql = "UPDATE don_hang SET
                    pending_read = 1,
                    approved_read = 1,
                    cancelled_read = 1
                    WHERE ma_nguoi_dung = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Lỗi khi đánh dấu tất cả thông báo đã đọc: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa tất cả thông báo đã đọc của người dùng
     *
     * @param string $userId Username của người dùng
     * @return bool Kết quả xóa
     */
    public function deleteReadNotifications($userId)
    {
        try {
            // Lưu ý: Chúng ta không thực sự xóa đơn hàng, chỉ đánh dấu là đã ẩn khỏi thông báo
            // Thêm cột notification_hidden vào bảng don_hang nếu chưa có
            $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'notification_hidden'";
            $checkColumnStmt = $this->db->prepare($checkColumnSql);
            $checkColumnStmt->execute();

            if ($checkColumnStmt->rowCount() == 0) {
                // Cột chưa tồn tại, thêm vào
                $addColumnSql = "ALTER TABLE don_hang ADD COLUMN notification_hidden TINYINT(1) NOT NULL DEFAULT 0";
                $this->db->exec($addColumnSql);
                error_log("Đã thêm cột notification_hidden vào bảng don_hang");
            }

            // Đánh dấu các thông báo đã đọc là đã ẩn
            $sql = "UPDATE don_hang SET notification_hidden = 1
                    WHERE ma_nguoi_dung = ? AND (
                        (trang_thai = 'pending' AND pending_read = 1) OR
                        (trang_thai = 'approved' AND approved_read = 1) OR
                        (trang_thai = 'cancelled' AND cancelled_read = 1)
                    )";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Lỗi khi xóa thông báo đã đọc: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa một thông báo cụ thể
     *
     * @param int $orderId ID của đơn hàng
     * @param string $userId Username của người dùng
     * @return bool Kết quả xóa
     */
    public function deleteNotification($orderId, $userId)
    {
        try {
            // Kiểm tra xem đơn hàng có thuộc về người dùng không
            $checkSql = "SELECT id FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$orderId, $userId]);

            if ($checkStmt->rowCount() == 0) {
                // Đơn hàng không thuộc về người dùng này
                error_log("Người dùng $userId không có quyền xóa thông báo đơn hàng $orderId");
                return false;
            }

            // Kiểm tra xem cột notification_hidden có tồn tại không
            $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'notification_hidden'";
            $checkColumnStmt = $this->db->prepare($checkColumnSql);
            $checkColumnStmt->execute();

            if ($checkColumnStmt->rowCount() == 0) {
                // Cột chưa tồn tại, thêm vào
                $addColumnSql = "ALTER TABLE don_hang ADD COLUMN notification_hidden TINYINT(1) NOT NULL DEFAULT 0";
                $this->db->exec($addColumnSql);
                error_log("Đã thêm cột notification_hidden vào bảng don_hang");
            }

            // Đánh dấu thông báo là đã ẩn
            $sql = "UPDATE don_hang SET notification_hidden = 1 WHERE id = ? AND ma_nguoi_dung = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$orderId, $userId]);
        } catch (PDOException $e) {
            error_log("Lỗi khi xóa thông báo: " . $e->getMessage());
            return false;
        }
    }
}
?>
