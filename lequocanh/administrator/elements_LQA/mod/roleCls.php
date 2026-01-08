<?php

$possible_paths = array(
    dirname(__FILE__) . '/database.php',
    dirname(dirname(dirname(__FILE__))) . '/elements_LQA/mod/database.php',
    dirname(dirname(dirname(dirname(__FILE__)))) . '/administrator/elements_LQA/mod/database.php'
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

class Role
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->createTablesIfNotExist();
    }

    private function createTablesIfNotExist()
    {
        try {

            $checkRolesTable = "SHOW TABLES LIKE 'vai_tro'";
            $stmt = $this->db->prepare($checkRolesTable);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                $createRolesTable = "CREATE TABLE `vai_tro` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ten_vai_tro` varchar(50) NOT NULL,
                    `mo_ta` text,
                    `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `ten_vai_tro` (`ten_vai_tro`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                $this->db->exec($createRolesTable);

                $insertDefaultRoles = "INSERT INTO `vai_tro` (`ten_vai_tro`, `mo_ta`) VALUES
                    ('admin', 'Quản trị viên - có toàn quyền trên hệ thống'),
                    ('staff', 'Nhân viên - có quyền quản lý sản phẩm, đơn hàng'),
                    ('customer', 'Khách hàng - chỉ có quyền mua hàng và quản lý tài khoản cá nhân');";

                $this->db->exec($insertDefaultRoles);
            }

            $checkUserRolesTable = "SHOW TABLES LIKE 'user_vai_tro'";
            $stmt = $this->db->prepare($checkUserRolesTable);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                $createUserRolesTable = "CREATE TABLE `user_vai_tro` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ma_nguoi_dung` int(11) NOT NULL,
                    `ma_vai_tro` int(11) NOT NULL,
                    `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `user_role_unique` (`ma_nguoi_dung`,`ma_vai_tro`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                $this->db->exec($createUserRolesTable);

                $adminUserSql = "SELECT iduser FROM user WHERE username = 'admin' LIMIT 1";
                $stmt = $this->db->prepare($adminUserSql);
                $stmt->execute();
                $adminUser = $stmt->fetch(PDO::FETCH_OBJ);

                if ($adminUser) {
                    $adminRoleSql = "SELECT id FROM vai_tro WHERE ten_vai_tro = 'admin' LIMIT 1";
                    $stmt = $this->db->prepare($adminRoleSql);
                    $stmt->execute();
                    $adminRole = $stmt->fetch(PDO::FETCH_OBJ);

                    if ($adminRole) {
                        $assignAdminRole = "INSERT INTO user_vai_tro (ma_nguoi_dung, ma_vai_tro) VALUES (?, ?)";
                        $stmt = $this->db->prepare($assignAdminRole);
                        $stmt->execute([$adminUser->iduser, $adminRole->id]);
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Lỗi khi tạo bảng: " . $e->getMessage());
        }
    }

    public function getAllRoles()
    {
        try {
            $sql = "SELECT * FROM vai_tro ORDER BY ten_vai_tro";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách vai trò: " . $e->getMessage());
            return [];
        }
    }

    public function getRoleById($id)
    {
        try {
            $sql = "SELECT * FROM vai_tro WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy vai trò theo ID: " . $e->getMessage());
            return null;
        }
    }

    public function getRoleByName($roleName)
    {
        try {
            $sql = "SELECT * FROM vai_tro WHERE ten_vai_tro = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleName]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy vai trò theo tên: " . $e->getMessage());
            return null;
        }
    }

    public function addRole($roleName, $mo_ta)
    {
        try {
            $sql = "INSERT INTO vai_tro (ten_vai_tro, mo_ta) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleName, $mo_ta]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi thêm vai trò: " . $e->getMessage());
            return false;
        }
    }

    public function updateRole($id, $roleName, $mo_ta)
    {
        try {
            $sql = "UPDATE vai_tro SET ten_vai_tro = ?, mo_ta = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleName, $mo_ta, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi cập nhật vai trò: " . $e->getMessage());
            return false;
        }
    }

    public function deleteRole($id)
    {
        try {

            $checkSql = "SELECT COUNT(*) as count FROM user_vai_tro WHERE ma_vai_tro = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_OBJ);

            if ($result->count > 0) {

                return false;
            }

            $sql = "DELETE FROM vai_tro WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi xóa vai trò: " . $e->getMessage());
            return false;
        }
    }

    public function assignRoleToUser($userId, $roleId)
    {
        try {

            $checkSql = "SELECT COUNT(*) as count FROM user_vai_tro WHERE ma_nguoi_dung = ? AND ma_vai_tro = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$userId, $roleId]);
            $result = $checkStmt->fetch(PDO::FETCH_OBJ);

            if ($result->count > 0) {

                return true;
            }

            $sql = "INSERT INTO user_vai_tro (ma_nguoi_dung, ma_vai_tro) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $roleId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi gán vai trò cho người dùng: " . $e->getMessage());
            return false;
        }
    }

    public function removeRoleFromUser($userId, $roleId)
    {
        try {
            $sql = "DELETE FROM user_vai_tro WHERE ma_nguoi_dung = ? AND ma_vai_tro = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $roleId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi xóa vai trò của người dùng: " . $e->getMessage());
            return false;
        }
    }

    public function getUserRoles($userId)
    {
        try {

            $sql = "SELECT r.* FROM vai_tro r
                    INNER JOIN user_vai_tro ur ON r.id = ur.ma_vai_tro
                    WHERE ur.ma_nguoi_dung = ?
                    ORDER BY r.ten_vai_tro";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách vai trò của người dùng: " . $e->getMessage());
            return [];
        }
    }

    public function userHasRole($userId, $roleName)
    {
        try {

            $sql = "SELECT COUNT(*) as count FROM user_vai_tro ur
                    INNER JOIN vai_tro r ON ur.ma_vai_tro = r.id
                    WHERE ur.ma_nguoi_dung = ? AND r.ten_vai_tro = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $roleName]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result->count > 0;
        } catch (PDOException $e) {
            error_log("Lỗi khi kiểm tra vai trò của người dùng: " . $e->getMessage());
            return false;
        }
    }

    public function isAdmin($userId)
    {
        return $this->userHasRole($userId, 'admin');
    }

    public function isStaff($userId)
    {
        return $this->userHasRole($userId, 'staff');
    }

    public function isCustomer($userId)
    {
        return $this->userHasRole($userId, 'customer');
    }

    public function getPrimaryRole($userId)
    {
        if ($this->isAdmin($userId)) {
            return 'admin';
        } elseif ($this->isStaff($userId)) {
            return 'staff';
        } elseif ($this->isCustomer($userId)) {
            return 'customer';
        } else {
            return 'unknown';
        }
    }
}
