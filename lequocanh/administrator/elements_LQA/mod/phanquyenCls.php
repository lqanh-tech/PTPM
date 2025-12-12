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

// Tìm đường dẫn đúng đến các file cần thiết
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

    /**
     * Kiểm tra xem bảng vai_tro đã tồn tại chưa
     */
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

    /**
     * Kiểm tra xem username có phải là nhân viên không
     */
    public function isNhanVien($username)
    {
        error_log("isNhanVien - Kiểm tra username: '$username'");
        
        if (empty($username)) {
            error_log("isNhanVien - Username rỗng, trả về false");
            return false;
        }
        
        // Kiểm tra trong bảng nhanvien (cách cũ)
        $sql = 'SELECT nv.* FROM nhanvien nv
                INNER JOIN user u ON nv.iduser = u.iduser
                WHERE u.username = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $isStaffOldWay = $stmt->rowCount() > 0;
        
        error_log("isNhanVien - Kiểm tra bảng nhanvien: " . ($isStaffOldWay ? 'Có' : 'Không'));

        // Kiểm tra trong bảng vai_tro (cách mới)
        if ($this->vai_troTableExists() && class_exists('Role') && $this->roleManager) {
            // Lấy user ID từ username
            $userSql = "SELECT iduser FROM user WHERE username = ?";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([$username]);
            $user = $userStmt->fetch(PDO::FETCH_OBJ);

            if ($user) {
                $isStaffNewWay = $this->roleManager->userHasRole($user->iduser, 'staff');
                error_log("isNhanVien - Kiểm tra bảng vai_tro: " . ($isStaffNewWay ? 'Có' : 'Không'));
                // Trả về true nếu một trong hai cách kiểm tra cho kết quả là nhân viên
                $result = $isStaffOldWay || $isStaffNewWay;
                error_log("isNhanVien - Kết quả cuối cùng: " . ($result ? 'Là nhân viên' : 'Không phải nhân viên'));
                return $result;
            }
        }

        error_log("isNhanVien - Kết quả cuối cùng (không có vai_tro): " . ($isStaffOldWay ? 'Là nhân viên' : 'Không phải nhân viên'));
        return $isStaffOldWay;
    }

    /**
     * Kiểm tra xem username có phải là admin không
     */
    public function isAdmin($username)
    {
        // Ghi log để debug
        error_log("Kiểm tra quyền admin cho username: $username");
        error_log("SESSION['ADMIN'] " . (isset($_SESSION['ADMIN']) ? "= '" . $_SESSION['ADMIN'] . "'" : "không tồn tại"));

        // Kiểm tra theo cách cũ - Sửa lỗi: chỉ kiểm tra SESSION['ADMIN'] nếu nó khớp với username hiện tại
        $isAdminByUsername = ($username === 'admin');
        $isAdminBySession = (isset($_SESSION['ADMIN']) && $_SESSION['ADMIN'] === $username);
        $isAdminOldWay = $isAdminByUsername || $isAdminBySession;

        error_log("isAdminByUsername: " . ($isAdminByUsername ? "true" : "false"));
        error_log("isAdminBySession: " . ($isAdminBySession ? "true" : "false"));

        // Kiểm tra trong bảng vai_tro (cách mới)
        $isAdminNewWay = false;
        if ($this->vai_troTableExists() && class_exists('Role') && $this->roleManager) {
            // Lấy user ID từ username
            $userSql = "SELECT iduser FROM user WHERE username = ?";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([$username]);
            $user = $userStmt->fetch(PDO::FETCH_OBJ);

            if ($user) {
                $isAdminNewWay = $this->roleManager->userHasRole($user->iduser, 'admin');
                error_log("isAdminNewWay (từ bảng vai_tro): " . ($isAdminNewWay ? "true" : "false"));
                // Trả về true nếu một trong hai cách kiểm tra cho kết quả là admin
                $result = $isAdminOldWay || $isAdminNewWay;
                error_log("Kết quả kiểm tra admin: " . ($result ? "true" : "false"));
                return $result;
            }
        }

        error_log("Kết quả kiểm tra admin (không có trong bảng vai_tro): " . ($isAdminOldWay ? "true" : "false"));
        return $isAdminOldWay;
    }

    /**
     * Kiểm tra quyền truy cập vào một chức năng cụ thể
     */
    public function checkAccess($module, $username)
    {
        // Log để debug
        error_log("checkAccess - Module: $module, Username: $username");

        // Nếu là Admin thì có quyền truy cập toàn bộ
        if ($this->isAdmin($username)) {
            error_log("User là admin, cho phép truy cập");
            // Ghi log nếu SecurityLogger tồn tại
            if (class_exists('SecurityLogger')) {
                SecurityLogger::logAccess($username, $module, true, "Admin access granted");
            }
            return true;
        }

        // Nếu là nhân viên - Sửa lỗi: chỉ kiểm tra SESSION['USER'] nếu nó khớp với username hiện tại
        error_log("SESSION['USER'] " . (isset($_SESSION['USER']) ? "= '" . $_SESSION['USER'] . "'" : "không tồn tại"));
        $isCurrentUser = isset($_SESSION['USER']) && $_SESSION['USER'] === $username;
        $isStaff = $this->isNhanVien($username);
        error_log("isCurrentUser: " . ($isCurrentUser ? "true" : "false") . ", isStaff: " . ($isStaff ? "true" : "false"));

        if ($isCurrentUser && $isStaff) {
            // Các module cơ bản mà tất cả nhân viên đều có quyền truy cập
            $basicModules = [
                'userprofile',
                'userUpdateProfile',
                'thongbao'
            ];

            if (in_array($module, $basicModules)) {
                return true;
            }

            // Kiểm tra quyền truy cập dựa trên phần hệ được gán
            // Tìm đường dẫn đúng đến các file cần thiết
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

            // Lấy ID nhân viên từ username
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

            // Kiểm tra quyền truy cập vào module cụ thể
            $phanHeObj = new PhanHeQuanLy();
            $hasAccess = $phanHeObj->checkNhanVienHasAccess($idNhanVien, $module);

            error_log("Nhân viên - Module: $module, Cho phép: " . ($hasAccess ? 'Có' : 'Không'));
            return $hasAccess;
        }

        // Nếu là user thông thường (không phải admin và không phải nhân viên)
        $isRegularUser = isset($_SESSION['USER']) && $_SESSION['USER'] === $username;
        error_log("Kiểm tra user thông thường - isRegularUser: " . ($isRegularUser ? "true" : "false"));

        if ($isRegularUser) {
            // Chỉ cho phép xem hồ sơ cá nhân và các chức năng liên quan đến người dùng
            $userAllowedModules = [
                'userprofile',
                'userUpdateProfile',
                'thongbao',
                'lichsumuahang',
                'don_hang'  // Cho phép người dùng xem đơn hàng của mình
            ];

            $hasAccess = in_array($module, $userAllowedModules);
            error_log("User thông thường - Module: $module, Cho phép: " . ($hasAccess ? 'Có' : 'Không'));
            return $hasAccess;
        }

        // Mặc định không có quyền truy cập
        error_log("Không có quyền truy cập mặc định cho module: $module");
        return false;
    }

    /**
     * Kiểm tra quyền truy cập cho nhân viên (không kiểm tra session, chỉ kiểm tra database)
     * Dùng cho việc hiển thị menu
     */
    public function checkAccessForEmployee($module, $username)
    {
        // Log để debug
        error_log("checkAccessForEmployee - Module: $module, Username: $username");

        // Các module cơ bản mà tất cả nhân viên đều có quyền truy cập
        $basicModules = [
            'userprofile',
            'userUpdateProfile',
            'thongbao'
        ];

        if (in_array($module, $basicModules)) {
            return true;
        }

        // Kiểm tra quyền truy cập dựa trên phần hệ được gán trong database
        try {
            // Tìm đường dẫn đúng đến các file cần thiết
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

            // Lấy ID nhân viên từ username
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

            // Kiểm tra quyền truy cập vào module cụ thể
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
