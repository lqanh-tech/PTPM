<?php

/**
 * Class KhachHang
 * Lớp xử lý thông tin khách hàng
 */
class KhachHang
{
    private $id;
    private $username;
    private $hoten;
    private $gioitinh;
    private $ngaysinh;
    private $diachi;
    private $dienthoai;
    private $email;
    private $ghichu;
    private $ngaytao;
    private $ngaycapnhat;

    private $conn;

    /**
     * Khởi tạo đối tượng KhachHang
     */
    public function __construct()
    {
        // Kết nối database
        require_once __DIR__ . '/database.php';
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * Kiểm tra xem username có phải là nhân viên không
     * @param string $username Username cần kiểm tra
     * @return bool True nếu là nhân viên, False nếu không phải
     */
    private function isNhanVien($username)
    {
        $sql = 'SELECT nv.* FROM nhanvien nv
                INNER JOIN user u ON nv.iduser = u.iduser
                WHERE u.username = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Lấy danh sách tất cả khách hàng (người dùng không phải admin và không phải nhân viên)
     * @return array Danh sách khách hàng
     */
    public function getAll()
    {
        // Lấy danh sách tất cả người dùng không phải admin
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.username != 'admin'
                ORDER BY u.hoten ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lọc ra những người không phải nhân viên
        $customers = [];
        foreach ($users as $user) {
            if (!$this->isNhanVien($user['username'])) {
                $customers[] = $user;
            }
        }

        return $customers;
    }

    /**
     * Lấy thông tin khách hàng theo ID
     * @param int $id ID của khách hàng
     * @return array|false Thông tin khách hàng hoặc false nếu không tìm thấy
     */
    public function getById($id)
    {
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.iduser = ? AND u.username != 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra xem người dùng có phải là nhân viên không
        if ($user && $this->isNhanVien($user['username'])) {
            return false; // Không trả về thông tin nếu là nhân viên
        }

        return $user;
    }

    /**
     * Lấy thông tin khách hàng theo username
     * @param string $username Username của khách hàng
     * @return array|false Thông tin khách hàng hoặc false nếu không tìm thấy
     */
    public function getByUsername($username)
    {
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.username = ? AND u.username != 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra xem người dùng có phải là nhân viên không
        if ($user && $this->isNhanVien($user['username'])) {
            return false; // Không trả về thông tin nếu là nhân viên
        }

        return $user;
    }

    /**
     * Tìm kiếm khách hàng theo từ khóa
     * @param string $keyword Từ khóa tìm kiếm
     * @param string $field Trường cần tìm kiếm (all, hoten, dienthoai, email, diachi)
     * @return array Danh sách khách hàng tìm thấy
     */
    public function search($keyword, $field = 'all')
    {
        // Lấy danh sách tất cả người dùng không phải admin
        if ($field == 'all') {
            $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                           u.ngaydangki as ngaytao, u.setlock
                    FROM user u
                    WHERE u.username != 'admin' AND (u.hoten LIKE ? OR u.dienthoai LIKE ? OR u.email LIKE ? OR u.diachi LIKE ?)
                    ORDER BY u.hoten ASC";
            $params = ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"];
        } else {
            $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                           u.ngaydangki as ngaytao, u.setlock
                    FROM user u
                    WHERE u.username != 'admin' AND u.$field LIKE ?
                    ORDER BY u.hoten ASC";
            $params = ["%$keyword%"];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lọc ra những người không phải nhân viên
        $customers = [];
        foreach ($users as $user) {
            if (!$this->isNhanVien($user['username'])) {
                $customers[] = $user;
            }
        }

        return $customers;
    }

    /**
     * Thêm khách hàng mới
     * @param array $data Dữ liệu khách hàng
     * @return int|false ID của khách hàng mới hoặc false nếu thất bại
     */
    public function add($data)
    {
        // Kiểm tra xem username đã tồn tại chưa
        $checkSql = "SELECT iduser FROM user WHERE username = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([$data['username']]);

        if ($checkStmt->rowCount() > 0) {
            // Username đã tồn tại
            return false;
        }

        // Thêm người dùng mới vào bảng user
        $sql = "INSERT INTO user (username, password, hoten, gioitinh, ngaysinh, diachi, dienthoai, email, setlock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";

        // Tạo mật khẩu mặc định (có thể thay đổi theo yêu cầu)
        $defaultPassword = md5('123456');

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['username'],
            $defaultPassword,
            $data['hoten'],
            $data['gioitinh'],
            $data['ngaysinh'],
            $data['diachi'],
            $data['dienthoai'],
            $data['email']
        ]);

        if ($result) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Cập nhật thông tin khách hàng
     * @param int $id ID của khách hàng
     * @param array $data Dữ liệu cập nhật
     * @return bool Kết quả cập nhật
     */
    public function update($id, $data)
    {
        // Kiểm tra xem người dùng có phải là nhân viên không
        $user = $this->getById($id);
        if (!$user) {
            return false;
        }

        $sql = "UPDATE user
                SET hoten = ?, gioitinh = ?, ngaysinh = ?, diachi = ?, dienthoai = ?, email = ?
                WHERE iduser = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['hoten'],
            $data['gioitinh'],
            $data['ngaysinh'],
            $data['diachi'],
            $data['dienthoai'],
            $data['email'],
            $id
        ]);
    }

    /**
     * Xóa khách hàng
     * @param int $id ID của khách hàng
     * @return bool Kết quả xóa
     */
    public function delete($id)
    {
        // Không thực sự xóa người dùng, chỉ vô hiệu hóa tài khoản
        $sql = "UPDATE user SET setlock = 0 WHERE iduser = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Lấy lịch sử mua hàng của khách hàng
     * @param string $username Username của khách hàng
     * @param int $limit Số lượng đơn hàng tối đa
     * @return array Danh sách đơn hàng
     */
    public function getOrderHistory($username, $limit = 5)
    {
        // Kiểm tra xem bảng orders có tồn tại không
        $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
        $ordersExists = $stmt->rowCount() > 0;

        $stmt = $this->conn->query("SHOW TABLES LIKE 'order_items'");
        $orderItemsExists = $stmt->rowCount() > 0;

        if (!$ordersExists || !$orderItemsExists) {
            return [];
        }

        try {
            $sql = "SELECT o.*,
                        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                   FROM orders o
                   WHERE o.user_id = ?
                   ORDER BY o.created_at DESC
                   LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy lịch sử mua hàng: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy sản phẩm đã mua của khách hàng
     * @param string $username Username của khách hàng
     * @param int $limit Số lượng sản phẩm tối đa
     * @return array Danh sách sản phẩm
     */
    public function getPurchasedProducts($username, $limit = 5)
    {
        // Kiểm tra xem bảng orders, order_items và hanghoa có tồn tại không
        $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
        $ordersExists = $stmt->rowCount() > 0;

        $stmt = $this->conn->query("SHOW TABLES LIKE 'order_items'");
        $orderItemsExists = $stmt->rowCount() > 0;

        $stmt = $this->conn->query("SHOW TABLES LIKE 'hanghoa'");
        $hanghoaExists = $stmt->rowCount() > 0;

        if (!$ordersExists || !$orderItemsExists || !$hanghoaExists) {
            return [];
        }

        try {
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh, h.gia,
                           COUNT(DISTINCT o.id) as order_count,
                           SUM(oi.quantity) as total_quantity,
                           MAX(o.created_at) as last_purchase_date
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN hanghoa h ON oi.product_id = h.idhanghoa
                    WHERE o.user_id = ? AND o.status = 'approved'
                    GROUP BY h.idhanghoa
                    ORDER BY last_purchase_date DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy sản phẩm đã mua: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tổng chi tiêu của khách hàng
     * @param string $username Username của khách hàng
     * @return float Tổng chi tiêu
     */
    public function getTotalSpent($username)
    {
        // Kiểm tra xem bảng don_hang có tồn tại không
        $stmt = $this->conn->query("SHOW TABLES LIKE 'don_hang'");
        $donHangExists = $stmt->rowCount() > 0;

        if (!$donHangExists) {
            // Nếu không có bảng don_hang, thử kiểm tra bảng orders
            $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
            $ordersExists = $stmt->rowCount() > 0;

            if (!$ordersExists) {
                return 0;
            }

            // Sử dụng bảng orders (fallback)
            try {
                $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_spent
                        FROM orders
                        WHERE user_id = ? AND status = 'approved'";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$username]);
                return $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'];
            } catch (PDOException $e) {
                error_log("Lỗi khi lấy tổng chi tiêu từ bảng orders: " . $e->getMessage());
                return 0;
            }
        }

        // Sử dụng bảng don_hang (preferred)
        try {
            $sql = "SELECT COALESCE(SUM(tong_tien), 0) as total_spent
                    FROM don_hang
                    WHERE ma_nguoi_dung = ? AND trang_thai = 'approved'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'];
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy tổng chi tiêu từ bảng don_hang: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy số lượng đơn hàng của khách hàng
     * @param string $username Username của khách hàng
     * @return int Số lượng đơn hàng
     */
    public function getOrderCount($username)
    {
        // Kiểm tra xem bảng don_hang có tồn tại không
        $stmt = $this->conn->query("SHOW TABLES LIKE 'don_hang'");
        $donHangExists = $stmt->rowCount() > 0;

        if (!$donHangExists) {
            // Nếu không có bảng don_hang, thử kiểm tra bảng orders
            $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
            $ordersExists = $stmt->rowCount() > 0;

            if (!$ordersExists) {
                return 0;
            }

            // Sử dụng bảng orders (fallback)
            try {
                $sql = "SELECT COUNT(*) as order_count
                        FROM orders
                        WHERE user_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$username]);
                return $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];
            } catch (PDOException $e) {
                error_log("Lỗi khi lấy số lượng đơn hàng từ bảng orders: " . $e->getMessage());
                return 0;
            }
        }

        // Sử dụng bảng don_hang (preferred)
        try {
            $sql = "SELECT COUNT(*) as order_count
                    FROM don_hang
                    WHERE ma_nguoi_dung = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy số lượng đơn hàng từ bảng don_hang: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Định dạng giới tính
     * @param int $gioitinh Giới tính (0: Nữ, 1: Nam, 2: Khác)
     * @return string Giới tính đã định dạng
     */
    public static function formatGender($gioitinh)
    {
        switch ($gioitinh) {
            case 0:
                return 'Nữ';
            case 1:
                return 'Nam';
            case 2:
                return 'Khác';
            default:
                return 'Không xác định';
        }
    }
}
