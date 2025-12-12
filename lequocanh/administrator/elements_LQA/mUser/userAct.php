<?php
// Start output buffering to prevent header issues
ob_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

// Include new infrastructure for better logging and session management
require_once __DIR__ . '/../config/logger_config.php';

// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../../elements_LQA/mod/userCls.php';
require '../../elements_LQA/mod/giohangCls.php';

// Tìm đường dẫn đúng đến nhatKyHoatDongHelper.php
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
            // xử lý thêm
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            $hoten = $_REQUEST['hoten'];
            $gioitinh = $_REQUEST['gioitinh'];
            $ngaysinh = $_REQUEST['ngaysinh'];
            $dienthoai = $_REQUEST['dienthoai'];
            $diachi = $_REQUEST['diachi'];
            $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : null;
            $userObj = new user();

            // Kiểm tra username đã tồn tại chưa
            if ($userObj->UserCheckUsername($username)) {
                // Kiểm tra nếu là AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
                    exit();
                } else {
                    header('Location: ../../index.php?req=userview&result=username_exists');
                    exit();
                }
            }

            $kq = $userObj->UserAdd($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $email);

            // Ghi nhật ký thêm mới người dùng
            if ($kq && $foundNhatKyHelper) {
                $currentUser = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
                ghiNhatKyThemMoi($currentUser, 'Khách hàng', $kq, "Thêm khách hàng mới: $hoten ($username)");
            }

            // Kiểm tra nếu là AJAX request
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
            // Kiểm tra đăng nhập
            if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
                echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
                exit();
            }

            // Lấy dữ liệu từ form
            $iduser = isset($_POST['iduser']) ? $_POST['iduser'] : '';
            $passwordold = isset($_POST['passwordold']) ? $_POST['passwordold'] : '';
            $passwordnew = isset($_POST['passwordnew']) ? $_POST['passwordnew'] : '';

            // Log password change attempt (without sensitive data)
            Logger::info("Password change request", ['user_id' => $iduser]);

            // Validate dữ liệu
            if (empty($iduser) || empty($passwordold) || empty($passwordnew)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
                exit();
            }

            // Đổi mật khẩu
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
            $iduser = $_REQUEST['iduser'];
            $userObj = new user();
            $user = $userObj->UserGetByid($iduser);

            // Kiểm tra quyền admin
            if (!isset($_SESSION['ADMIN'])) {
                header('location: ../../index.php?req=userview&result=not_authorized');
                exit();
            }

            // Kiểm tra nếu là tài khoản admin
            if ($user && $user->username === 'admin') {
                $admin_password = isset($_REQUEST['admin_password']) ? $_REQUEST['admin_password'] : '';

                // Kiểm tra mật khẩu admin từ database
                if (!$userObj->UserCheckLogin('admin', $admin_password)) {
                    header('location: ../../index.php?req=userview&result=invalid_admin_pass');
                    exit();
                }
            }

            $kq = $userObj->UserDelete($iduser);

            // Ghi nhật ký xóa người dùng
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
            $iduser = $_REQUEST['iduser'];
            $setlock = $_REQUEST['setlock'];
            $userObj = new user();
            $user = $userObj->UserGetbyId($iduser);

            // Kiểm tra nếu là tài khoản admin
            if ($user && $user->username === 'admin') {
                $admin_password = isset($_REQUEST['admin_password']) ? $_REQUEST['admin_password'] : '';

                // Kiểm tra mật khẩu admin từ database
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
            $iduser = $_REQUEST['iduser'];
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            $hoten = $_REQUEST['hoten'];
            $gioitinh = $_REQUEST['gioitinh'];
            $ngaysinh = $_REQUEST['ngaysinh'];
            $diachi = $_REQUEST['diachi'];
            $dienthoai = $_REQUEST['dienthoai'];
            $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : null;
            $verify_password = isset($_REQUEST['verify_password']) ? $_REQUEST['verify_password'] : '';

            $userObj = new user();
            $user = $userObj->UserGetbyId($iduser);

            if (!$user) {
                header('location: ../../index.php?req=userview&result=user_not_found');
                exit();
            }

            // Kiểm tra nếu là tài khoản admin
            if ($user->username === 'admin') {
                // Kiểm tra mật khẩu xác thực
                if ($verify_password !== 'lequocanh') {
                    header('location: ../../index.php?req=userview&result=invalid_verify_pass');
                    exit();
                }

                // Nếu không nhập mật khẩu mới, giữ nguyên mật khẩu cũ
                if (empty($password)) {
                    $password = $user->password;
                }
            }

            // Validate dữ liệu
            if (empty($username) || empty($hoten) || empty($ngaysinh) || empty($diachi) || empty($dienthoai)) {
                header('location: ../../index.php?req=userview&result=missing_data');
                exit();
            }

            // Kiểm tra username đã tồn tại chưa (trừ username hiện tại)
            if ($username !== $user->username && $userObj->UserCheckUsername($username)) {
                header('Location: ../../index.php?req=userview&result=username_exists');
                exit();
            }

            $result = $userObj->UserUpdate($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $iduser, $email);

            // Ghi nhật ký cập nhật người dùng
            if ($result && $foundNhatKyHelper) {
                $currentUser = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
                ghiNhatKyCapNhat($currentUser, 'Khách hàng', $iduser, "Cập nhật thông tin khách hàng: $hoten ($username)");
            }

            if ($result) {
                header('location: ../../index.php?req=userview&result=ok');
            } else {
                header('location: ../../index.php?req=userview&result=failed');
            }
            exit();

        case 'checklogin':
            $username = trim($_REQUEST['username']); // Loại bỏ khoảng trắng thừa
            $password = $_REQUEST['password'];

            // Log login attempt (without password for security)
            Logger::info("Login attempt", ['username' => $username]);

            // Kiểm tra trực tiếp trong cơ sở dữ liệu
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM user WHERE username = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                Logger::debug("User found in database", ['username' => $username, 'user_id' => $user['iduser'] ?? 'unknown']);

                // Kiểm tra mật khẩu
                if ($user['password'] === $password) {
                    Logger::debug("Password verification successful", ['username' => $username]);

                    // Kiểm tra setlock
                    if ($user['setlock'] == 1) {
                        Logger::debug("Account already activated", ['username' => $username]);
                    } else {
                        Logger::info("Account auto-activation", ['username' => $username, 'previous_setlock' => $user['setlock']]);

                        // Tự động kích hoạt tài khoản
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

                // Kiểm tra xem có user nào gần giống không (chỉ trong development)
                if (Logger::DEBUG <= 1) { // Only in debug mode
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
                // Kiểm tra xem user có phải là admin hoặc manager không
                $isAdminUser = ($username == 'admin' || strpos($username, 'manager') !== false);

                if ($isAdminUser) {
                    $_SESSION['ADMIN'] = $username;
                    Logger::info("Admin session established", ['username' => $username, 'role' => 'admin']);

                    // Ghi nhật ký đăng nhập
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

                    // Chuyển giỏ hàng từ session sang database
                    $giohang = new GioHang();
                    $giohang->migrateSessionCartToDatabase($username);

                    // Kiểm tra xem có URL chuyển hướng sau đăng nhập không
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
                    // Đảm bảo dừng thực thi script sau khi chuyển hướng
                    exit();
                } else {
                    $_SESSION['USER'] = $username;
                    Logger::info("User session established", ['username' => $username, 'role' => 'user']);

                    // Ghi nhật ký đăng nhập
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

                    // Chuyển giỏ hàng từ session sang database
                    $giohang = new GioHang();
                    $giohang->migrateSessionCartToDatabase($username);

                    // Thiết lập múi giờ Việt Nam
                    date_default_timezone_set('Asia/Ho_Chi_Minh');

                    // Đặt cookie sau khi đăng nhập thành công
                    $time_login = date('H:i - d/m/Y');
                    setcookie($username, $time_login, time() + (86400 * 30), '/');
                    Logger::debug("User cookie set", ['username' => $username]);

                    // Kiểm tra xem có URL chuyển hướng sau đăng nhập không
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        Logger::info("User redirect to saved URL", ['username' => $username, 'url' => $redirect_url]);
                        header('Location: ' . $redirect_url);
                    } else {
                        // Sử dụng đường dẫn tuyệt đối
                        $redirect_url = 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh/index.php';
                        Logger::info("User redirect to homepage", ['username' => $username]);
                        header('Location: ' . $redirect_url);
                    }
                    // Đảm bảo dừng thực thi script sau khi chuyển hướng
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

            // Thiết lập múi giờ Việt Nam
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $time_login = date('H:i - d/m/Y');
            $namelogin = '';

            if (isset($_SESSION['USER'])) {
                $namelogin = $_SESSION['USER'];
                error_log("Đăng xuất USER: " . $namelogin);

                // Ghi nhật ký đăng xuất
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

                // Ghi nhật ký đăng xuất
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

            // Chỉ set cookie nếu có tên đăng nhập
            if (!empty($namelogin)) {
                // Chỉnh sửa tên cookie
                $namelogin = str_replace(' ', '-', $namelogin);
                $namelogin = str_replace('"', '', $namelogin);
                setcookie($namelogin, $time_login, time() + (86400 * 30), '/'); // 1 tháng
            }

            // Xóa session
            unset($_SESSION['USER']);
            unset($_SESSION['ADMIN']);
            session_destroy();

            error_log("Đã xóa session, chuyển hướng người dùng...");

            // Lưu trữ thông tin trước khi xóa session
            $isAdmin = isset($_SESSION['ADMIN']);

            // Chuyển hướng về trang chủ sau khi đăng xuất
            if ($isAdmin) {
                error_log("Chuyển hướng đến trang admin");
                header('location: ../../index.php');
            } else {
                error_log("Chuyển hướng đến trang chủ");
                header('location: ../../../index.php');
            }
            exit(); // Đảm bảo dừng thực thi script sau khi chuyển hướng
            break;

        case 'checkadmin':
            $admin_password = isset($_REQUEST['admin_password']) ? $_REQUEST['admin_password'] : '';

            // Kiểm tra mật khẩu admin
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

// Clean and flush output buffer
ob_end_clean();
