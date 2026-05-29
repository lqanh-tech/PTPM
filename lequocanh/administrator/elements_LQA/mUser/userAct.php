<?php
// Security includes
require_once __DIR__ . '/../mod/SecurityHelpers.php';
require_once __DIR__ . '/../mod/InputValidator.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed. Vui lòng tải lại trang và thử lại.');
}


ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/logger_config.php';

require_once __DIR__ . '/../../../includes/session_security.php';

if (session_status() === PHP_SESSION_NONE) {
    SessionSecurity::init();
} else {

    SessionSecurity::checkTimeout();
    SessionSecurity::validateSession();
}

require '../../elements_LQA/mod/userCls.php';
require '../../elements_LQA/mod/giohangCls.php';
require_once __DIR__ . '/../mod/EmailService.php';

$nhatKyHelperPaths = [
    __DIR__ . '/../../elements_LQA/mnhatkyhoatdong/nhatKyHoatDongHelper.php',
    __DIR__ . '/../mnhatkyhoatdong/nhatKyHoatDongHelper.php',
    __DIR__ . '/../../mnhatkyhoatdong/nhatKyHoatDongHelper.php',
    './elements_LQA/mnhatkyhoatdong/nhatKyHoatDongHelper.php'
];

$foundNhatKyHelper = false;
foreach ($nhatKyHelperPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundNhatKyHelper = true;
        break;
    }
}

if (!$foundNhatKyHelper) {
    error_log("Không thể tìm thấy file nhatKyHoatDongHelper.php");
}

$requestAction = isset($_REQUEST['reqact']) ? $_REQUEST['reqact'] : '';

if ($requestAction) {
    switch ($requestAction) {
        case 'addnew':
            // Validate và sanitize input
            $username = sanitize($_REQUEST['username'] ?? '', 'text');
            $password = $_REQUEST['password'] ?? ''; // Không sanitize password
            $hoten = sanitize($_REQUEST['hoten'] ?? '', 'text');
            $gioitinh = sanitize($_REQUEST['gioitinh'] ?? '1', 'int');
            $ngaysinh = sanitize($_REQUEST['ngaysinh'] ?? '', 'text');
            $dienthoai = sanitize($_REQUEST['dienthoai'] ?? '', 'phone');
            $diachi = sanitize($_REQUEST['diachi'] ?? '', 'text');
            $email = sanitize($_REQUEST['email'] ?? '', 'email');
            
            // Validate required fields
            if (empty($username) || empty($password) || empty($hoten) || empty($dienthoai)) {
                if (is_ajax()) {
                    header('Content-Type: application/json');
                    echo safe_json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
                    exit();
                } else {
                    header('Location: ../../index.php?req=userview&result=missing_fields');
                    exit();
                }
            }
            
            $userObj = new user();

            if ($userObj->UserCheckUsername($username)) {

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
                    exit();
                } else {
                    header('Location: ../../index.php?req=userview&result=username_exists');
                    exit();
                }
            }

            $kq = $userObj->UserAdd($username, $password, $hoten, $gioitinh, $ngaysinh ?: '1990-01-01', $diachi, $dienthoai, $email);

            if ($kq && $foundNhatKyHelper) {
                $currentUser = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
                ghiNhatKyThemMoi($currentUser, 'Khách hàng', $kq, "Thêm khách hàng mới: $hoten ($username)");
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                if ($kq) {
                    echo json_encode(['success' => true, 'message' => 'Thêm người dùng thành công']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Thêm người dùng thất bại']);
                }
                exit();
            } else {
                if ($kq) {
                    header('Location: ../../index.php?req=userview&result=ok');
                } else {
                    header('Location: ../../index.php?req=userview&result=notok');
                }
            }
            break;

        case 'changepassword':

            if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
                echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
                exit();
            }

            $iduser = sanitizeInput($_POST['iduser'] ?? '', 'int');
            $passwordold = $_POST['passwordold'] ?? ''; // Don't sanitize password
            $passwordnew = $_POST['passwordnew'] ?? ''; // Don't sanitize password

            Logger::info("Password change request", ['user_id' => $iduser]);

            if (empty($iduser) || empty($passwordold) || empty($passwordnew)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
                exit();
            }

            $userObj = new user();
            $result = $userObj->UserChangePassword($iduser, $passwordold, $passwordnew);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác']);
            }
            exit();
            break;

        case 'deleteuser':
            $iduser = sanitizeInput($_REQUEST['iduser'] ?? '', 'int');
            $userObj = new user();
            $user = $userObj->UserGetByid($iduser);

            if (!isset($_SESSION['ADMIN'])) {
                header('location: ../../index.php?req=userview&result=not_authorized');
                exit();
            }

            if ($user && $user->username === 'admin') {
                $admin_password = $_REQUEST['admin_password'] ?? ''; // Don't sanitize password

                if (!$userObj->UserCheckLogin('admin', $admin_password)) {
                    header('location: ../../index.php?req=userview&result=invalid_admin_pass');
                    exit();
                }
            }

            $kq = $userObj->UserDelete($iduser);

            if ($kq && $foundNhatKyHelper) {
                $currentUser = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
                ghiNhatKyXoa($currentUser, 'Khách hàng', $iduser, "Xóa khách hàng: " . ($user ? $user->hoten : "ID $iduser"));
            }

            if ($kq) {
                header('location: ../../index.php?req=userview&result=ok');
            } else {
                header('location: ../../index.php?req=userview&result=notok');
            }
            break;

        case 'setlock':
            $iduser = sanitizeInput($_REQUEST['iduser'] ?? '', 'int');
            $setlock = sanitizeInput($_REQUEST['setlock'] ?? '', 'int');
            $userObj = new user();
            $user = $userObj->UserGetbyId($iduser);

            if ($user && $user->username === 'admin') {
                $admin_password = $_REQUEST['admin_password'] ?? ''; // Don't sanitize password

                if (!$userObj->UserCheckLogin('admin', $admin_password)) {
                    header('location: ../../index.php?req=userview&result=invalid_admin_pass');
                    exit();
                }
            }

            $newStatus = $setlock == 1 ? 0 : 1;
            $kq = $userObj->UserSetActive($iduser, $newStatus);
            if ($kq) {
                header('location: ../../index.php?req=userview&result=ok');
            } else {
                header('location: ../../index.php?req=userview&result=notok');
            }
            break;

        case 'updateuser':
            $iduser = sanitizeInput($_REQUEST['iduser'] ?? '', 'int');
            $username = sanitizeInput($_REQUEST['username'] ?? '', 'text');
            $password = $_REQUEST['password'] ?? ''; // Don't sanitize password
            $hoten = sanitizeInput($_REQUEST['hoten'] ?? '', 'text');
            $gioitinh = sanitizeInput($_REQUEST['gioitinh'] ?? '1', 'int');
            $ngaysinh = sanitizeInput($_REQUEST['ngaysinh'] ?? '', 'text');
            $diachi = sanitizeInput($_REQUEST['diachi'] ?? '', 'text');
            $dienthoai = sanitizeInput($_REQUEST['dienthoai'] ?? '', 'phone');
            $email = sanitizeInput($_REQUEST['email'] ?? '', 'email');
            $verify_password = $_REQUEST['verify_password'] ?? ''; // Don't sanitize password

            $userObj = new user();
            $user = $userObj->UserGetbyId($iduser);

            if (!$user) {
                header('location: ../../index.php?req=userview&result=user_not_found');
                exit();
            }

            if ($user->username === 'admin') {

                if ($verify_password !== 'lequocanh') {
                    header('location: ../../index.php?req=userview&result=invalid_verify_pass');
                    exit();
                }

                if (empty($password)) {
                    $password = $user->password;
                }
            }

            if (empty($username) || empty($hoten) || empty($ngaysinh) || empty($diachi) || empty($dienthoai)) {
                header('location: ../../index.php?req=userview&result=missing_data');
                exit();
            }

            if ($username !== $user->username && $userObj->UserCheckUsername($username)) {
                header('Location: ../../index.php?req=userview&result=username_exists');
                exit();
            }

            $oldEmail = isset($user->email) ? $user->email : '';
            
            $result = $userObj->UserUpdate($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $iduser, $email);

            if ($result && $foundNhatKyHelper) {
                $currentUser = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
                ghiNhatKyCapNhat($currentUser, 'Khách hàng', $iduser, "Cập nhật thông tin khách hàng: $hoten ($username)");
            }
            
            if ($result && !empty($email) && $email !== $oldEmail) {
                try {
                    $emailService = new EmailService();
                    $emailService->sendEmailUpdateNotification($email, $hoten, $username);
                    error_log("userAct.php - Đã gửi email thông báo cập nhật email mới cho: " . $email);
                } catch (Exception $e) {
                    error_log("userAct.php - Lỗi gửi email thông báo: " . $e->getMessage());
                }
            }

            if ($result) {
                header('location: ../../index.php?req=userview&result=ok');
            } else {
                header('location: ../../index.php?req=userview&result=failed');
            }
            exit();

        case 'checklogin':
            // Validate và sanitize input
            $username = sanitize($_REQUEST['username'] ?? '', 'text');
            $password = $_REQUEST['password'] ?? ''; // Không sanitize password
            
            // Validate không để trống
            if (empty($username) || empty($password)) {
                Logger::warning("Login attempt with empty credentials");
                header('Location: ../../userLogin.php?error=empty');
                exit();
            }
            
            // Validate username format
            if (!preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
                Logger::warning("Login attempt with invalid username format", ['username' => $username]);
                header('Location: ../../userLogin.php?error=invalid');
                exit();
            }

            Logger::info("Login attempt", ['username' => $username]);

            $db = Database::getInstance()->getConnection();
            $sql = "SELECT iduser, username, password, hoten, email, sodienthoai, diachi, role, trangthai, setlock, avatar_url, auth_provider, created_at FROM user WHERE username = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                Logger::debug("User found in database", ['username' => $username, 'user_id' => $user['iduser'] ?? 'unknown']);

                if (password_verify($password, $user['password'])) {
                    Logger::debug("Password verification successful", ['username' => $username]);

                    if ($user['setlock'] == 1) {
                        Logger::debug("Account already activated", ['username' => $username]);
                    } else {
                        Logger::info("Account auto-activation", ['username' => $username, 'previous_setlock' => $user['setlock']]);

                        $update_sql = "UPDATE user SET setlock = 1 WHERE iduser = ?";
                        $update_stmt = $db->prepare($update_sql);
                        $update_stmt->execute([$user['iduser']]);
                        Logger::info("Account activated successfully", ['username' => $username]);
                    }
                } else {
                    Logger::warning("Password verification failed", ['username' => $username]);
                }
            } else {
                Logger::warning("User not found in database", ['username' => $username]);

                if (Logger::DEBUG <= 1) {
                    $sql_like = "SELECT username FROM user WHERE username LIKE ?";
                    $stmt_like = $db->prepare($sql_like);
                    $stmt_like->execute(['%' . $username . '%']);
                    $similar_users = $stmt_like->fetchAll(PDO::FETCH_COLUMN);

                    if (count($similar_users) > 0) {
                        Logger::debug("Similar usernames found", ['similar_count' => count($similar_users)]);
                    }
                }
            }

            $userObj = new user();
            $kq = $userObj->UserCheckLogin($username, $password);
            if ($kq) {

                $isAdminUser = ($username == 'admin' || strpos($username, 'manager') !== false);

                // Kiểm tra nếu là nhân viên
                $isStaffUser = false;
                $staffFirstModule = '';
                if (!$isAdminUser) {
                    $pqPaths = [
                        __DIR__ . '/../mod/phanquyenCls.php',
                        __DIR__ . '/../../elements_LQA/mod/phanquyenCls.php',
                        './elements_LQA/mod/phanquyenCls.php'
                    ];
                    foreach ($pqPaths as $pqp) {
                        if (file_exists($pqp)) {
                            require_once $pqp;
                            break;
                        }
                    }
                    if (class_exists('PhanQuyen')) {
                        $phanQuyen = new PhanQuyen();
                        $isStaffUser = $phanQuyen->isNhanVien($username);
                        if ($isStaffUser) {
                            $phPaths = [
                                __DIR__ . '/../mod/phanHeQuanLyCls.php',
                                __DIR__ . '/../../elements_LQA/mod/phanHeQuanLyCls.php',
                                './elements_LQA/mod/phanHeQuanLyCls.php'
                            ];
                            foreach ($phPaths as $php) {
                                if (file_exists($php)) {
                                    require_once $php;
                                    break;
                                }
                            }
                            if (class_exists('PhanHeQuanLy') && class_exists('user') && class_exists('NhanVien')) {
                                $userData = (new user())->UserGetbyUsername($username);
                                if ($userData) {
                                    $nvObj = new NhanVien();
                                    $allNv = $nvObj->nhanvienGetAll();
                                    $idNhanVien = null;
                                    foreach ($allNv as $nv) {
                                        if ($nv->iduser == $userData->iduser) {
                                            $idNhanVien = $nv->idNhanVien;
                                            break;
                                        }
                                    }
                                    if ($idNhanVien) {
                                        $phanHeObj = new PhanHeQuanLy();
                                        $assignedModules = $phanHeObj->getPhanHeByNhanVienId($idNhanVien);
                                        if (!empty($assignedModules)) {
                                            $staffFirstModule = $assignedModules[0]->maPhanHe;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($isAdminUser) {
                    $_SESSION['ADMIN'] = $username;
                    Logger::info("Admin session established", ['username' => $username, 'role' => 'admin']);
                    
                    SessionSecurity::onLogin($user['iduser'] ?? 0, $username);

                    if ($foundNhatKyHelper) {
                        $result = ghiNhatKyDangNhap($username);
                        if (!$result) {
                            error_log("Lỗi khi ghi nhật ký đăng nhập cho user: $username");
                        } else {
                            error_log("Đã ghi nhật ký đăng nhập thành công cho user: $username, ID: $result");
                        }
                    } else {
                        error_log("Không tìm thấy file nhatKyHoatDongHelper.php khi đăng nhập");
                    }

                    $giohang = new GioHang();
                    $giohang->migrateSessionCartToDatabase($username);

                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        Logger::info("Admin redirect to saved URL", ['username' => $username, 'url' => $redirect_url]);
                        header('Location: ' . $redirect_url);
                    } else {
                        $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh/administrator/index.php?req=userview&result=ok';
                        Logger::info("Admin redirect to default admin page", ['username' => $username]);
                        header('Location: ' . $redirect_url);
                    }

                    exit();
                } else {
                    $_SESSION['USER'] = $username;
                    Logger::info("User session established", ['username' => $username, 'role' => 'user']);
                    
                    SessionSecurity::onLogin($user['iduser'] ?? 0, $username);

                    if ($foundNhatKyHelper) {
                        $result = ghiNhatKyDangNhap($username);
                        if (!$result) {
                            error_log("Lỗi khi ghi nhật ký đăng nhập cho user: $username");
                        } else {
                            error_log("Đã ghi nhật ký đăng nhập thành công cho user: $username, ID: $result");
                        }
                    } else {
                        error_log("Không tìm thấy file nhatKyHoatDongHelper.php khi đăng nhập");
                    }

                    $giohang = new GioHang();
                    $giohang->migrateSessionCartToDatabase($username);

                    date_default_timezone_set('Asia/Ho_Chi_Minh');

                    $time_login = date('H:i - d/m/Y');
                    setcookie($username, $time_login, time() + (86400 * 30), '/');
                    Logger::debug("User cookie set", ['username' => $username]);

                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        Logger::info("User redirect to saved URL", ['username' => $username, 'url' => $redirect_url]);
                        header('Location: ' . $redirect_url);
                    } else if ($isStaffUser) {
                        // Nhân viên: chuyển đến admin panel module đầu tiên được phân quyền
                        $firstReq = !empty($staffFirstModule) ? $staffFirstModule : 'userprofile';
                        $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh/administrator/index.php?req=' . urlencode($firstReq);
                        Logger::info("Staff redirect to admin panel", ['username' => $username, 'module' => $firstReq]);
                        header('Location: ' . $redirect_url);
                    } else {

                        $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh/index.php';
                        Logger::info("User redirect to homepage", ['username' => $username]);
                        header('Location: ' . $redirect_url);
                    }

                    exit();
                }
            } else {
                Logger::warning("Login failed", ['username' => $username]);
                $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh/administrator/userLogin.php?error=1';
                Logger::info("Redirect to login page with error", ['username' => $username]);
                header('Location: ' . $redirect_url);
                exit();
            }
            break;

        case 'userlogout':
            Logger::info("Processing logout request");

            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $time_login = date('H:i - d/m/Y');
            $namelogin = '';

            if (isset($_SESSION['USER'])) {
                $namelogin = $_SESSION['USER'];
                error_log("Đăng xuất USER: " . $namelogin);

                if ($foundNhatKyHelper) {
                    $result = ghiNhatKyDangXuat($namelogin);
                    if (!$result) {
                        error_log("Lỗi khi ghi nhật ký đăng xuất cho user: $namelogin");
                    } else {
                        error_log("Đã ghi nhật ký đăng xuất thành công cho user: $namelogin, ID: $result");
                    }
                } else {
                    error_log("Không tìm thấy file nhatKyHoatDongHelper.php khi đăng xuất");
                }
            }
            if (isset($_SESSION['ADMIN'])) {
                $namelogin = $_SESSION['ADMIN'];
                error_log("Đăng xuất ADMIN: " . $namelogin);

                if ($foundNhatKyHelper) {
                    $result = ghiNhatKyDangXuat($namelogin);
                    if (!$result) {
                        error_log("Lỗi khi ghi nhật ký đăng xuất cho admin: $namelogin");
                    } else {
                        error_log("Đã ghi nhật ký đăng xuất thành công cho admin: $namelogin, ID: $result");
                    }
                } else {
                    error_log("Không tìm thấy file nhatKyHoatDongHelper.php khi đăng xuất admin");
                }
            }

            if (!empty($namelogin)) {

                $namelogin = str_replace(' ', '-', $namelogin);
                $namelogin = str_replace('"', '', $namelogin);
                setcookie($namelogin, $time_login, time() + (86400 * 30), '/');
            }

            SessionSecurity::onLogout();

            error_log("Đã xóa session, chuyển hướng người dùng...");

            $isAdmin = isset($_SESSION['ADMIN']);

            if ($isAdmin) {
                error_log("Chuyển hướng đến trang admin");
                header('location: ../../index.php');
            } else {
                error_log("Chuyển hướng đến trang chủ");
                header('location: ../../../index.php');
            }
            exit();
            break;

        case 'checkadmin':
            $admin_password = isset($_REQUEST['admin_password']) ? $_REQUEST['admin_password'] : '';

            if ($admin_password === 'lequocanh') {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu không chính xác']);
            }
            exit();
            break;

        default:
            header('Location: ../../index.php?req=userview');
            break;
    }
} else {
    header('Location: ../../index.php?req=userview');
}

ob_end_clean();
