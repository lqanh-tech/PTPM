<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$possible_paths = array(
    dirname(__FILE__) . '/../mod/database.php',
    dirname(dirname(__FILE__)) . '/mod/database.php',
    dirname(dirname(dirname(__FILE__))) . '/elements_LQA/mod/database.php'
);

$database_file = null;
foreach ($possible_paths as $duong_dan) {
    if (file_exists($duong_dan)) {
        $database_file = $duong_dan;
        break;
    }
}

if ($database_file === null) {
    die("Không thể tìm thấy file database.php");
}

require_once $database_file;

class SimpleDB
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new SimpleDB();
        }
        return self::$instance;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryOne($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function queryValue($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result ? $result[0] : null;
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}

class KhachHang
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = SimpleDB::getInstance();
        } catch (Exception $e) {
            die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
        }
    }

    private function isNhanVien($username)
    {

        $userSql = 'SELECT iduser FROM user WHERE username = ?';
        $userId = $this->db->queryValue($userSql, [$username]);

        if (!$userId) {
            return false;
        }

        $sql = 'SELECT nv.* FROM nhanvien nv WHERE nv.iduser = ?';
        $result = $this->db->query($sql, [$userId]);
        $isStaffOld = count($result) > 0;

        $roleSql = 'SELECT COUNT(*) as count FROM user_vai_tro ur
                    INNER JOIN vai_tro vt ON ur.ma_vai_tro = vt.id
                    WHERE ur.ma_nguoi_dung = ? AND vt.ten_vai_tro = "staff"';
        $isStaffNew = $this->db->queryValue($roleSql, [$userId]) > 0;

        return $isStaffOld || $isStaffNew;
    }

    public function getAll()
    {

        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.username != 'admin'
                AND u.iduser NOT IN (
                    SELECT DISTINCT nv.iduser
                    FROM nhanvien nv
                    WHERE nv.iduser IS NOT NULL
                )
                AND u.iduser NOT IN (
                    SELECT DISTINCT ur.ma_nguoi_dung
                    FROM user_vai_tro ur
                    INNER JOIN vai_tro vt ON ur.ma_vai_tro = vt.id
                    WHERE vt.ten_vai_tro IN ('admin', 'staff')
                )
                ORDER BY u.hoten ASC";

        return $this->db->query($sql);
    }

    public function getById($id)
    {
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.iduser = ? AND u.username != 'admin'";
        $user = $this->db->queryOne($sql, [$id]);

        if ($user && $this->isNhanVien($user['username'])) {
            return false;
        }

        return $user;
    }

    public function getByUsername($username)
    {
        $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai,
                       u.ngaydangki as ngaytao, u.setlock
                FROM user u
                WHERE u.username = ? AND u.username != 'admin'";
        $user = $this->db->queryOne($sql, [$username]);

        if ($user && $this->isNhanVien($user['username'])) {
            return false;
        }

        return $user;
    }

    public function search($keyword, $field = 'all')
    {

        if ($field == 'all') {
            $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai,
                           u.ngaydangki as ngaytao, u.setlock
                    FROM user u
                    WHERE u.username != 'admin' AND (u.hoten LIKE ? OR u.dienthoai LIKE ? OR u.diachi LIKE ?)
                    ORDER BY u.hoten ASC";
            $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
        } else {
            $sql = "SELECT u.iduser as id, u.username, u.hoten, u.gioitinh, u.ngaysinh, u.diachi, u.dienthoai,
                           u.ngaydangki as ngaytao, u.setlock
                    FROM user u
                    WHERE u.username != 'admin' AND u.$field LIKE ?
                    ORDER BY u.hoten ASC";
            $params = ["%$keyword%"];
        }

        $users = $this->db->query($sql, $params);

        $customers = [];
        foreach ($users as $user) {
            if (!$this->isNhanVien($user['username'])) {
                $customers[] = $user;
            }
        }

        return $customers;
    }

    private function assignCustomerRole($userId)
    {
        try {

            $roleSql = "SELECT id FROM vai_tro WHERE ten_vai_tro = 'customer'";
            $roleId = $this->db->queryValue($roleSql);

            if (!$roleId) {

                $createRoleSql = "INSERT INTO vai_tro (ten_vai_tro, mo_ta) VALUES ('customer', 'Khách hàng - chỉ có quyền mua hàng và quản lý tài khoản cá nhân')";
                $this->db->execute($createRoleSql);
                $roleId = $this->db->lastInsertId();
            }

            $checkSql = "SELECT COUNT(*) as count FROM user_vai_tro WHERE ma_nguoi_dung = ? AND ma_vai_tro = ?";
            $exists = $this->db->queryValue($checkSql, [$userId, $roleId]);

            if ($exists > 0) {
                return true;
            }

            $assignSql = "INSERT INTO user_vai_tro (ma_nguoi_dung, ma_vai_tro) VALUES (?, ?)";
            $result = $this->db->execute($assignSql, [$userId, $roleId]);

            return $result > 0;
        } catch (Exception $e) {
            error_log("Lỗi khi gán vai trò customer: " . $e->getMessage());
            return false;
        }
    }

    public function add($data)
    {

        $checkSql = "SELECT iduser FROM user WHERE username = ?";
        $existingUser = $this->db->queryOne($checkSql, [$data['username']]);

        if ($existingUser) {

            return false;
        }

        $sql = "INSERT INTO user (username, password, hoten, gioitinh, ngaysinh, diachi, dienthoai, setlock)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";

        $defaultPassword = password_hash('123456', PASSWORD_BCRYPT, ['cost' => 12]);

        $params = [
            $data['username'],
            $defaultPassword,
            $data['hoten'],
            $data['gioitinh'],
            $data['ngaysinh'],
            $data['diachi'],
            $data['dienthoai']
        ];

        $result = $this->db->execute($sql, $params);

        if ($result) {
            $userId = $this->db->lastInsertId();

            $this->assignCustomerRole($userId);

            return $userId;
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
                SET hoten = ?, gioitinh = ?, ngaysinh = ?, diachi = ?, dienthoai = ?
                WHERE iduser = ?";

        $params = [
            $data['hoten'],
            $data['gioitinh'],
            $data['ngaysinh'],
            $data['diachi'],
            $data['dienthoai'],
            $id
        ];

        $result = $this->db->execute($sql, $params);
        return $result > 0;
    }

    public function delete($id)
    {

        $sql = "UPDATE user SET setlock = 0 WHERE iduser = ?";
        $result = $this->db->execute($sql, [$id]);
        return $result > 0;
    }

    public function getOrderHistory($username, $limit = 5)
    {

        try {
            $sql = "SELECT o.*,
                        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                   FROM orders o
                   WHERE o.user_id = ?
                   ORDER BY o.created_at DESC
                   LIMIT ?";
            return $this->db->query($sql, [$username, $limit]);
        } catch (Exception $e) {
            error_log("Lỗi khi lấy lịch sử mua hàng: " . $e->getMessage());
            return [];
        }
    }

    public function getPurchasedProducts($username, $limit = 5)
    {
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
            return $this->db->query($sql, [$username, $limit]);
        } catch (Exception $e) {
            error_log("Lỗi khi lấy sản phẩm đã mua: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalSpent($username)
    {
        try {
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_spent
                    FROM orders
                    WHERE user_id = ? AND status = 'approved'";
            return $this->db->queryValue($sql, [$username]);
        } catch (Exception $e) {
            error_log("Lỗi khi lấy tổng chi tiêu: " . $e->getMessage());
            return 0;
        }
    }

    public function getOrderCount($username)
    {
        try {
            $sql = "SELECT COUNT(*) as order_count
                    FROM orders
                    WHERE user_id = ?";
            return $this->db->queryValue($sql, [$username]);
        } catch (Exception $e) {
            error_log("Lỗi khi lấy số lượng đơn hàng: " . $e->getMessage());
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
