<?php
require_once '../../elements_LQA/mod/userCls.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    header('location: ../../userLogin.php');
    exit();
}

error_log("userProfile.php: Người dùng đã đăng nhập với SESSION: " . (isset($_SESSION['USER']) ? "USER=" . $_SESSION['USER'] : "ADMIN=" . $_SESSION['ADMIN']));

$username = isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : $_SESSION['USER'];

$userObj = new user();

$allUsers = $userObj->UserGetAll();
$currentUser = null;

error_log("userProfile.php: Đang tìm user với username: '$username'");

foreach ($allUsers as $user) {
    if ($user->username === $username) {
        $currentUser = $user;
        error_log("userProfile.php: Đã tìm thấy user với ID: " . $user->iduser);
        break;
    }
}

if (!$currentUser) {
    error_log("userProfile.php: Không tìm thấy user với username: '$username'");
    header('location: ../../index.php');
    exit();
}

$genderText = ($currentUser->gioitinh == 1) ? 'Nam' : 'Nữ';

$lastLogin = isset($_COOKIE[$username]) ? $_COOKIE[$username] : 'Không có thông tin';

$accountAge = '';
if (isset($currentUser->ngaydangki)) {
    $registerDate = new DateTime($currentUser->ngaydangki);
    $now = new DateTime();
    $interval = $registerDate->diff($now);

    if ($interval->y > 0) {
        $accountAge = $interval->y . ' năm';
        if ($interval->m > 0) {
            $accountAge .= ', ' . $interval->m . ' tháng';
        }
    } else if ($interval->m > 0) {
        $accountAge = $interval->m . ' tháng';
        if ($interval->d > 0) {
            $accountAge .= ', ' . $interval->d . ' ngày';
        }
    } else {
        $accountAge = $interval->d . ' ngày';
    }
} else {
    $accountAge = 'Không có thông tin';
}

function isNhanVien($iduser)
{
    try {

        error_log("Checking isNhanVien for user ID: " . $iduser);

        $possiblePaths = [
            __DIR__ . '/../../elements_LQA/mod/database.php',
            __DIR__ . '/../mod/database.php',
            './elements_LQA/mod/database.php'
        ];

        $found = false;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $found = true;
                break;
            }
        }

        if (!$found) {
            error_log("Không thể tìm thấy file database.php");
            return false;
        }

        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(*) FROM nhanvien WHERE iduser = ? AND iduser IS NOT NULL";
        $stmt = $db->prepare($sql);
        $stmt->execute([$iduser]);

        $count = $stmt->fetchColumn();
        error_log("Số lượng nhân viên tìm thấy cho user ID $iduser: " . $count);

        return $count > 0;
    } catch (Exception $e) {
        error_log("Lỗi khi kiểm tra nhân viên: " . $e->getMessage());
        return false;
    }
}

$isAdmin = isset($_SESSION['ADMIN']);
$isNhanVien = $isAdmin || isNhanVien($currentUser->iduser);

error_log("User ID: " . $currentUser->iduser . ", isNhanVien: " . ($isNhanVien ? 'true' : 'false'));

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../../../index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Thông tin tài khoản</li>
                    </ol>
                </nav>

                <div class="admin-title text-center mb-4">
                    <h1>Thông Tin Tài Khoản</h1>
                </div>
            </div>
        </div>

        <div class="user-profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($currentUser->hoten); ?></h2>
                    <p class="username">@<?php echo htmlspecialchars($currentUser->username); ?></p>
                    <div class="account-status <?php echo $currentUser->setlock == 1 ? 'active' : 'inactive'; ?>">
                        <?php echo $currentUser->setlock == 1 ? 'Đang hoạt động' : 'Đã khóa'; ?>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-primary">ID: <?php echo htmlspecialchars($currentUser->iduser); ?></span>
                    </div>
                </div>

                <div class="profile-body">
                    <div class="profile-section">
                        <h3>Thông tin cá nhân</h3>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-id-card"></i> ID:</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser->iduser); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-venus-mars"></i> Giới tính:</div>
                            <div class="info-value"><?php echo $genderText; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-birthday-cake"></i> Ngày sinh:</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser->ngaysinh); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ:</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser->diachi); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> Số điện thoại:</div>
                            <div class="info-value"><?php echo htmlspecialchars($currentUser->dienthoai); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> Email:</div>
                            <div class="info-value">
                                <?php 
                                if (!empty($currentUser->email)) {
                                    echo htmlspecialchars($currentUser->email);
                                } else {
                                    echo '<span class="text-muted">Chưa cập nhật</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>Thông tin tài khoản</h3>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-lock"></i> Mật khẩu:</div>
                            <div class="info-value password-field">
                                <span class="password-dots">••••••••</span>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-clock"></i> Đăng nhập gần đây:</div>
                            <div class="info-value"><?php echo $lastLogin; ?></div>
                        </div>
                        <?php if (isset($currentUser->ngaydangki)): ?>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-calendar-alt"></i> Ngày đăng ký:</div>
                                <div class="info-value"><?php echo htmlspecialchars($currentUser->ngaydangki); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-history"></i> Thời gian sử dụng:</div>
                                <div class="info-value"><?php echo $accountAge; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="../../elements_LQA/mUser/updateProfile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Cập nhật thông tin
                    </a>

                    <button type="button" id="change-password-btn" class="btn btn-warning">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>

                    <?php if ($isNhanVien || isset($_SESSION['ADMIN'])): ?>
                        <a href="../../index.php" class="btn btn-info">
                            <i class="fas fa-user-cog"></i> Đến trang quản trị
                        </a>
                    <?php endif; ?>

                    <a href="userAct.php?reqact=userlogout" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>

                    <a href="../../../index.php" class="btn btn-success">
                        <i class="fas fa-store"></i> Quay lại trang mua hàng
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal đổi mật khẩu -->
        <div class="modal" id="changePasswordModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Đổi mật khẩu</h5>
                        <button type="button" class="close" id="modalCloseBtn" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="password-result-message" class="alert" style="display:none;"></div>
                        <form id="changePasswordForm">
                            <input type="hidden" name="iduser" value="<?php echo $currentUser->iduser; ?>">

                            <div class="form-group">
                                <label for="old-password">Mật khẩu hiện tại</label>
                                <input type="password" class="form-control" id="old-password" name="passwordold" required>
                            </div>

                            <div class="form-group">
                                <label for="new-password">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new-password" name="passwordnew" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm-password">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm-password" name="passwordconfirm" required>
                            </div>

                            <div class="alert alert-danger" id="password-mismatch" style="display: none;">
                                Mật khẩu xác nhận không khớp với mật khẩu mới
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" id="cancelBtn">Hủy</button>
                                <button type="submit" class="btn btn-primary" id="submit-change-password">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .user-profile-container {
                padding: 20px;
                display: flex;
                justify-content: center;
            }

            .profile-card {
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 800px;
                overflow: hidden;
            }

            .profile-header {
                background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                color: white;
                padding: 30px;
                text-align: center;
                position: relative;
            }

            .profile-avatar {
                font-size: 80px;
                margin-bottom: 15px;
            }

            .profile-header h2 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }

            .username {
                opacity: 0.8;
                margin: 5px 0 15px;
                font-size: 16px;
            }

            .account-status {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
            }

            .account-status.active {
                background-color: #4CAF50;
            }

            .account-status.inactive {
                background-color: #F44336;
            }

            .profile-body {
                padding: 20px;
            }

            .profile-section {
                margin-bottom: 30px;
            }

            .profile-section h3 {
                color: #333;
                border-bottom: 2px solid #eee;
                padding-bottom: 10px;
                margin-bottom: 15px;
                font-size: 20px;
            }

            .info-item {
                display: flex;
                margin-bottom: 12px;
                align-items: center;
            }

            .info-label {
                flex: 0 0 170px;
                font-weight: 600;
                color: #555;
            }

            .info-label i {
                width: 20px;
                text-align: center;
                margin-right: 8px;
                color: #2575fc;
            }

            .info-value {
                flex: 1;
            }

            .password-field {
                display: flex;
                align-items: center;
            }

            .toggle-password {
                margin-left: 10px;
                cursor: pointer;
                color: #777;
            }

            .toggle-password:hover {
                color: #2575fc;
            }

            .profile-actions {
                padding: 20px;
                background-color: #f9f9f9;
                display: flex;
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }

            .profile-actions .btn {
                padding: 10px 20px;
                border-radius: 4px;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 7px;
            }

            .profile-actions .btn i {
                font-size: 16px;
            }

            @media (max-width: 768px) {
                .info-item {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .info-label {
                    margin-bottom: 5px;
                }

                .profile-actions {
                    flex-direction: column;
                }

                .profile-actions .btn {
                    width: 100%;
                    margin-bottom: 10px;
                }
            }

            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.4);
            }

            .modal.show {
                display: block;
            }

            .modal-dialog {
                margin: 10% auto;
                width: 90%;
                max-width: 500px;
            }

            .modal-content {
                background-color: #fefefe;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                animation: modal-appear 0.3s ease;
            }

            @keyframes modal-appear {
                from {
                    opacity: 0;
                    transform: translateY(-50px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #dee2e6;
            }

            .modal-title {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
            }

            .close {
                background: none;
                border: none;
                font-size: 24px;
                font-weight: 700;
                color: #666;
                cursor: pointer;
            }

            .modal-body {
                padding: 20px;
            }

            .modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #dee2e6;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }

            .alert {
                padding: 12px 15px;
                margin-bottom: 15px;
                border-radius: 4px;
                border: 1px solid transparent;
            }

            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border-color: #c3e6cb;
            }

            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border-color: #f5c6cb;
            }

            .alert-info {
                background-color: #d1ecf1;
                color: #0c5460;
                border-color: #bee5eb;
            }
        </style>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            $(document).ready(function() {

                $('.toggle-password').click(function() {
                    var passwordField = $(this).prev('.password-dots');
                    var eyeIcon = $(this);

                    if (passwordField.html() === '••••••••') {
                        passwordField.html('<?php echo htmlspecialchars($currentUser->password); ?>');
                        eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        passwordField.html('••••••••');
                        eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });

                $('#change-password-btn').click(function() {

                    $('#changePasswordForm')[0].reset();
                    $('#password-mismatch').hide();
                    $('#password-result-message').hide();
                    $('#submit-change-password').prop('disabled', false);

                    $('#changePasswordModal').addClass('show');
                });

                $('#modalCloseBtn, #cancelBtn').click(function() {
                    $('#changePasswordModal').removeClass('show');
                });

                $(window).click(function(event) {
                    if ($(event.target).is('#changePasswordModal')) {
                        $('#changePasswordModal').removeClass('show');
                    }
                });

                $('#confirm-password, #new-password').on('keyup', function() {
                    if ($('#new-password').val() !== '' && $('#confirm-password').val() !== '') {
                        if ($('#new-password').val() !== $('#confirm-password').val()) {
                            $('#password-mismatch').show();
                            $('#submit-change-password').prop('disabled', true);
                        } else {
                            $('#password-mismatch').hide();
                            $('#submit-change-password').prop('disabled', false);
                        }
                    }
                });

                $('#changePasswordForm').submit(function(e) {
                    e.preventDefault();

                    if ($('#new-password').val() !== $('#confirm-password').val()) {
                        $('#password-mismatch').show();
                        return false;
                    }

                    $('#password-result-message')
                        .removeClass('alert-danger alert-success')
                        .addClass('alert-info')
                        .html('Đang xử lý yêu cầu...')
                        .show();

                    $('#submit-change-password').prop('disabled', true).text('Đang xử lý...');

                    var iduser = $('input[name="iduser"]').val();
                    var passwordold = $('#old-password').val();
                    var passwordnew = $('#new-password').val();

                    console.log("Đang gửi yêu cầu đổi mật khẩu cho user ID: " + iduser);

                    $.ajax({
                        url: 'userAct.php?reqact=changepassword',
                        type: 'POST',
                        data: {
                            iduser: iduser,
                            passwordold: passwordold,
                            passwordnew: passwordnew,
                            passwordconfirm: passwordnew
                        },
                        success: function(response) {
                            console.log("Nhận phản hồi: ", response);

                            $('#password-result-message')
                                .removeClass('alert-danger alert-info')
                                .addClass('alert-success')
                                .html('Đổi mật khẩu thành công!')
                                .show();

                            setTimeout(function() {
                                $('#changePasswordModal').removeClass('show');

                                if ($('.password-dots').text() !== '••••••••') {
                                    $('.password-dots').text(passwordnew);
                                }
                            }, 2000);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("Lỗi AJAX:", textStatus, errorThrown);
                            $('#password-result-message')
                                .removeClass('alert-success alert-info')
                                .addClass('alert-danger')
                                .html('Lỗi kết nối đến máy chủ: ' + textStatus)
                                .show();

                            $('#submit-change-password').prop('disabled', false).text('Lưu thay đổi');
                        }
                    });
                });
            });
        </script>
</body>

</html>