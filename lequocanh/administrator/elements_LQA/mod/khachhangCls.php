<?php

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

    public function __construct()
    {

        require_once __DIR__ . '/database.php';
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    private function isNhanVien($username)
    {
        $sql = 'SELECT nv.* FROM nhanvien nv
                INNER JOIN user u ON nv.iduser = u.iduser
                WHERE u.username = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    public function getAll()
    {

        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.username != 'admin'
                ORDER BY u.hoten ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $customers = [];
        foreach ($users as $user) {
            if (!$this->isNhanVien($user['username'])) {
                $customers[] = $user;
            }
        }

        return $customers;
    }

    public function getById($id)
    {
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.iduser = ? AND u.username != 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $this->isNhanVien($user['username'])) {
            return false;
        }

        return $user;
    }

    public function getByUsername($username)
    {
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai, u.email,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.username = ? AND u.username != 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $this->isNhanVien($user['username'])) {
            return false;
        }

        return $user;
    }

    public function search($keyword, $field = 'all')
    {

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

        $customers = [];
        foreach ($users as $user) {
            if (!$this->isNhanVien($user['username'])) {
                $customers[] = $user;
            }
        }

        return $customers;
    }

    public function add($data)
    {

        $checkSql = "SELECT iduser FROM user WHERE username = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([$data['username']]);

        if ($checkStmt->rowCount() > 0) {

            return false;
        }

        $sql = "INSERT INTO user (username, password, hoten, gioitinh, ngaysinh, diachi, dienthoai, email, setlock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";

        $defaultPassword = password_hash('123456', PASSWORD_BCRYPT, ['cost' => 12]);

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

    public function update($id, $data)
    {

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

    public function delete($id)
    {

        $sql = "UPDATE user SET setlock = 0 WHERE iduser = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function getOrderHistory($username, $limit = 5)
    {

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

    public function getPurchasedProducts($username, $limit = 5)
    {

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

    public function getTotalSpent($username)
    {

        $stmt = $this->conn->query("SHOW TABLES LIKE 'don_hang'");
        $donHangExists = $stmt->rowCount() > 0;

        if (!$donHangExists) {

            $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
            $ordersExists = $stmt->rowCount() > 0;

            if (!$ordersExists) {
                return 0;
            }

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

    public function getOrderCount($username)
    {

        $stmt = $this->conn->query("SHOW TABLES LIKE 'don_hang'");
        $donHangExists = $stmt->rowCount() > 0;

        if (!$donHangExists) {

            $stmt = $this->conn->query("SHOW TABLES LIKE 'orders'");
            $ordersExists = $stmt->rowCount() > 0;

            if (!$ordersExists) {
                return 0;
            }

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
