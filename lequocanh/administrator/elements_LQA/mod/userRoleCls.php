<?php
$s = '../../elements_LQA/mod/database.php';
if (file_exists($s)) {
    $f = $s;
} else {
    $f = './elements_LQA/mod/database.php';
    if (!file_exists($f)) {
        $f = './administrator/elements_LQA/mod/database.php';
    }
}
require_once $f;

$rolePaths = [
    '../../elements_LQA/mod/roleCls.php',
    './elements_LQA/mod/roleCls.php',
    './administrator/elements_LQA/mod/roleCls.php'
];

foreach ($rolePaths as $duong_dan) {
    if (file_exists($duong_dan)) {
        require_once $duong_dan;
        break;
    }
}

class UserRole
{
    private $db;
    private $roleManager;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        if (class_exists('Role')) {
            $this->roleManager = new Role();
        }
    }

    private function vai_troTableExists()
    {
        try {
            $sql = "SHOW TABLES LIKE 'vai_tro'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi kiểm tra bảng vai_tro: " . $e->getMessage());
            return false;
        }
    }

    private function userRolesTableExists()
    {
        try {
            $sql = "SHOW TABLES LIKE 'user_vai_tro'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Lỗi kiểm tra bảng user_vai_tro: " . $e->getMessage());
            return false;
        }
    }

    public function assignDefaultRole($userId, $roleName = 'customer')
    {

        if (!$this->roleManager) {
            error_log("Không tìm thấy Role Manager");
            return false;
        }

        if (!$this->vai_troTableExists() || !$this->userRolesTableExists()) {
            error_log("Bảng vai_tro hoặc user_vai_tro chưa tồn tại, đang tạo...");

            $this->createTablesIfNotExist();
        }

        try {

            $role = $this->roleManager->getRoleByName($roleName);

            if (!$role) {
                error_log("Không tìm thấy vai trò: $roleName, đang tạo mới...");

                $mo_ta = '';
                switch ($roleName) {
                    case 'admin':
                        $mo_ta = 'Quản trị viên - có toàn quyền trên hệ thống';
                        break;
                    case 'staff':
                        $mo_ta = 'Nhân viên - có quyền quản lý sản phẩm, đơn hàng';
                        break;
                    case 'customer':
                        $mo_ta = 'Khách hàng - chỉ có quyền mua hàng và quản lý tài khoản cá nhân';
                        break;
                    default:
                        $mo_ta = 'Vai trò tùy chỉnh';
                }

                $addResult = $this->roleManager->addRole($roleName, $mo_ta);
                if (!$addResult) {
                    error_log("Không thể tạo vai trò mới: $roleName");
                    return false;
                }

                $role = $this->roleManager->getRoleByName($roleName);
                if (!$role) {
                    error_log("Không thể lấy vai trò vừa tạo: $roleName");
                    return false;
                }
            }

            $result = $this->roleManager->assignRoleToUser($userId, $role->id);
            if ($result) {
                error_log("Đã gán vai trò $roleName (ID: {$role->id}) cho người dùng ID: $userId");
            } else {
                error_log("Không thể gán vai trò $roleName (ID: {$role->id}) cho người dùng ID: $userId");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Lỗi khi gán vai trò mặc định: " . $e->getMessage());
            return false;
        }
    }

    private function createTablesIfNotExist()
    {
        try {

            if (!$this->vai_troTableExists()) {
                $createRolesTable = "CREATE TABLE `vai_tro` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ten_vai_tro` varchar(50) NOT NULL,
                    `mo_ta` text,
                    `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `ten_vai_tro` (`ten_vai_tro`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                $this->db->exec($createRolesTable);
                error_log("Đã tạo bảng vai_tro");

                $insertDefaultRoles = "INSERT INTO `vai_tro` (`ten_vai_tro`, `mo_ta`) VALUES
                    ('admin', 'Quản trị viên - có toàn quyền trên hệ thống'),
                    ('staff', 'Nhân viên - có quyền quản lý sản phẩm, đơn hàng'),
                    ('customer', 'Khách hàng - chỉ có quyền mua hàng và quản lý tài khoản cá nhân');";

                $this->db->exec($insertDefaultRoles);
                error_log("Đã thêm các vai trò mặc định");
            }

            if (!$this->userRolesTableExists()) {
                $createUserRolesTable = "CREATE TABLE `user_vai_tro` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ma_nguoi_dung` int(11) NOT NULL,
                    `ma_vai_tro` int(11) NOT NULL,
                    `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `user_role_unique` (`ma_nguoi_dung`,`ma_vai_tro`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                $this->db->exec($createUserRolesTable);
                error_log("Đã tạo bảng user_vai_tro");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Lỗi khi tạo bảng: " . $e->getMessage());
            return false;
        }
    }

    public function assignStaffRole($userId)
    {
        return $this->assignDefaultRole($userId, 'staff');
    }

    public function assignAdminRole($userId)
    {
        return $this->assignDefaultRole($userId, 'admin');
    }
}
