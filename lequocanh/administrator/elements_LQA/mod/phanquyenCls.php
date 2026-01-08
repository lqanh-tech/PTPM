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

$paths = [
    'roleCls.php' => [
        '../../elements_LQA/mod/roleCls.php',
        './elements_LQA/mod/roleCls.php',
        './administrator/elements_LQA/mod/roleCls.php'
    ],
    'userCls.php' => [
        '../../elements_LQA/mod/userCls.php',
        './elements_LQA/mod/userCls.php',
        './administrator/elements_LQA/mod/userCls.php'
    ]
];

foreach ($paths as $file => $filePaths) {
    foreach ($filePaths as $duong_dan) {
        if (file_exists($duong_dan)) {
            require_once $duong_dan;
            break;
        }
    }
}

class PhanQuyen
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

    public function isNhanVien($username)
    {
        error_log("isNhanVien - Kiểm tra username: '$username'");
        
        if (empty($username)) {
            error_log("isNhanVien - Username rỗng, trả về false");
            return false;
        }
        
        $sql = 'SELECT nv.* FROM nhanvien nv
                INNER JOIN user u ON nv.iduser = u.iduser
                WHERE u.username = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $isStaffOldWay = $stmt->rowCount() > 0;
        
        error_log("isNhanVien - Kiểm tra bảng nhanvien: " . ($isStaffOldWay ? 'Có' : 'Không'));

        if ($this->vai_troTableExists() && class_exists('Role') && $this->roleManager) {

            $userSql = "SELECT iduser FROM user WHERE username = ?";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([$username]);
            $user = $userStmt->fetch(PDO::FETCH_OBJ);

            if ($user) {
                $isStaffNewWay = $this->roleManager->userHasRole($user->iduser, 'staff');
                error_log("isNhanVien - Kiểm tra bảng vai_tro: " . ($isStaffNewWay ? 'Có' : 'Không'));

                $result = $isStaffOldWay || $isStaffNewWay;
                error_log("isNhanVien - Kết quả cuối cùng: " . ($result ? 'Là nhân viên' : 'Không phải nhân viên'));
                return $result;
            }
        }

        error_log("isNhanVien - Kết quả cuối cùng (không có vai_tro): " . ($isStaffOldWay ? 'Là nhân viên' : 'Không phải nhân viên'));
        return $isStaffOldWay;
    }

    public function isAdmin($username)
    {

        error_log("Kiểm tra quyền admin cho username: $username");
        error_log("SESSION['ADMIN'] " . (isset($_SESSION['ADMIN']) ? "= '" . $_SESSION['ADMIN'] . "'" : "không tồn tại"));

        $isAdminByUsername = ($username === 'admin');
        $isAdminBySession = (isset($_SESSION['ADMIN']) && $_SESSION['ADMIN'] === $username);
        $isAdminOldWay = $isAdminByUsername || $isAdminBySession;

        error_log("isAdminByUsername: " . ($isAdminByUsername ? "true" : "false"));
        error_log("isAdminBySession: " . ($isAdminBySession ? "true" : "false"));

        $isAdminNewWay = false;
        if ($this->vai_troTableExists() && class_exists('Role') && $this->roleManager) {

            $userSql = "SELECT iduser FROM user WHERE username = ?";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([$username]);
            $user = $userStmt->fetch(PDO::FETCH_OBJ);

            if ($user) {
                $isAdminNewWay = $this->roleManager->userHasRole($user->iduser, 'admin');
                error_log("isAdminNewWay (từ bảng vai_tro): " . ($isAdminNewWay ? "true" : "false"));

                $result = $isAdminOldWay || $isAdminNewWay;
                error_log("Kết quả kiểm tra admin: " . ($result ? "true" : "false"));
                return $result;
            }
        }

        error_log("Kết quả kiểm tra admin (không có trong bảng vai_tro): " . ($isAdminOldWay ? "true" : "false"));
        return $isAdminOldWay;
    }

    public function checkAccess($module, $username)
    {

        error_log("checkAccess - Module: $module, Username: $username");

        if ($this->isAdmin($username)) {
            error_log("User là admin, cho phép truy cập");

            if (class_exists('SecurityLogger')) {
                SecurityLogger::logAccess($username, $module, true, "Admin access granted");
            }
            return true;
        }

        error_log("SESSION['USER'] " . (isset($_SESSION['USER']) ? "= '" . $_SESSION['USER'] . "'" : "không tồn tại"));
        $isCurrentUser = isset($_SESSION['USER']) && $_SESSION['USER'] === $username;
        $isStaff = $this->isNhanVien($username);
        error_log("isCurrentUser: " . ($isCurrentUser ? "true" : "false") . ", isStaff: " . ($isStaff ? "true" : "false"));

        if ($isCurrentUser && $isStaff) {

            $basicModules = [
                'userprofile',
                'userUpdateProfile',
                'thongbao'
            ];

            if (in_array($module, $basicModules)) {
                return true;
            }

            $additionalPaths = [
                'phanHeQuanLyCls.php' => [
                    '../../elements_LQA/mod/phanHeQuanLyCls.php',
                    './elements_LQA/mod/phanHeQuanLyCls.php',
                    './administrator/elements_LQA/mod/phanHeQuanLyCls.php'
                ],
                'nhanvienCls.php' => [
                    '../../elements_LQA/mod/nhanvienCls.php',
                    './elements_LQA/mod/nhanvienCls.php',
                    './administrator/elements_LQA/mod/nhanvienCls.php'
                ]
            ];

            foreach ($additionalPaths as $file => $filePaths) {
                foreach ($filePaths as $duong_dan) {
                    if (file_exists($duong_dan)) {
                        require_once $duong_dan;
                        break;
                    }
                }
            }

            $userObj = new user();
            $userData = $userObj->UserGetbyUsername($username);

            if (!$userData) {
                error_log("Không tìm thấy thông tin user: $username");
                return false;
            }

            $nvObj = new NhanVien();
            $nhanVienList = $nvObj->nhanvienGetAll();
            $idNhanVien = null;

            foreach ($nhanVienList as $nv) {
                if ($nv->iduser == $userData->iduser) {
                    $idNhanVien = $nv->idNhanVien;
                    break;
                }
            }

            if (!$idNhanVien) {
                error_log("Không tìm thấy nhân viên liên kết với user: $username");
                return false;
            }

            $phanHeObj = new PhanHeQuanLy();
            $hasAccess = $phanHeObj->checkNhanVienHasAccess($idNhanVien, $module);

            error_log("Nhân viên - Module: $module, Cho phép: " . ($hasAccess ? 'Có' : 'Không'));
            return $hasAccess;
        }

        $isRegularUser = isset($_SESSION['USER']) && $_SESSION['USER'] === $username;
        error_log("Kiểm tra user thông thường - isRegularUser: " . ($isRegularUser ? "true" : "false"));

        if ($isRegularUser) {

            $userAllowedModules = [
                'userprofile',
                'userUpdateProfile',
                'thongbao',
                'lichsumuahang',
                'don_hang'
            ];

            $hasAccess = in_array($module, $userAllowedModules);
            error_log("User thông thường - Module: $module, Cho phép: " . ($hasAccess ? 'Có' : 'Không'));
            return $hasAccess;
        }

        error_log("Không có quyền truy cập mặc định cho module: $module");
        return false;
    }

    public function checkAccessForEmployee($module, $username)
    {

        error_log("checkAccessForEmployee - Module: $module, Username: $username");

        $basicModules = [
            'userprofile',
            'userUpdateProfile',
            'thongbao'
        ];

        if (in_array($module, $basicModules)) {
            return true;
        }

        try {

            $additionalPaths = [
                'phanHeQuanLyCls.php' => [
                    '../../elements_LQA/mod/phanHeQuanLyCls.php',
                    './elements_LQA/mod/phanHeQuanLyCls.php',
                    './administrator/elements_LQA/mod/phanHeQuanLyCls.php',
                    __DIR__ . '/phanHeQuanLyCls.php'
                ],
                'nhanvienCls.php' => [
                    '../../elements_LQA/mod/nhanvienCls.php',
                    './elements_LQA/mod/nhanvienCls.php',
                    './administrator/elements_LQA/mod/nhanvienCls.php',
                    __DIR__ . '/nhanvienCls.php'
                ]
            ];

            foreach ($additionalPaths as $file => $filePaths) {
                foreach ($filePaths as $duong_dan) {
                    if (file_exists($duong_dan)) {
                        require_once $duong_dan;
                        break;
                    }
                }
            }

            if (!class_exists('user')) {
                error_log("Class user không tồn tại");
                return false;
            }

            $userObj = new user();
            $userData = $userObj->UserGetbyUsername($username);

            if (!$userData) {
                error_log("Không tìm thấy thông tin user: $username");
                return false;
            }

            if (!class_exists('NhanVien')) {
                error_log("Class NhanVien không tồn tại");
                return false;
            }

            $nvObj = new NhanVien();
            $nhanVienList = $nvObj->nhanvienGetAll();
            $idNhanVien = null;

            foreach ($nhanVienList as $nv) {
                if ($nv->iduser == $userData->iduser) {
                    $idNhanVien = $nv->idNhanVien;
                    break;
                }
            }

            if (!$idNhanVien) {
                error_log("Không tìm thấy nhân viên liên kết với user: $username");
                return false;
            }

            if (!class_exists('PhanHeQuanLy')) {
                error_log("Class PhanHeQuanLy không tồn tại");
                return false;
            }

            $phanHeObj = new PhanHeQuanLy();
            $hasAccess = $phanHeObj->checkNhanVienHasAccess($idNhanVien, $module);

            error_log("checkAccessForEmployee - Nhân viên ID: $idNhanVien, Module: $module, Cho phép: " . ($hasAccess ? 'Có' : 'Không'));
            return $hasAccess;
        } catch (Exception $e) {
            error_log("checkAccessForEmployee error: " . $e->getMessage());
            return false;
        }
    }
}
