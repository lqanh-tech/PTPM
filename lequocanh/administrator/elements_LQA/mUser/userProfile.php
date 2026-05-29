<?php
/**
 * User Profile - Trang thông tin cá nhân
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    header('Location: ../../userLogin.php');
    exit();
}

require_once __DIR__ . '/../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$userId = $_SESSION['USER'];
$message = '';
$messageType = '';

// Lấy thông tin user
$stmt = $conn->prepare("SELECT id, username, password, hoten, email, sodienthoai, diachi, role, trangthai, setlock, avatar_url, auth_provider, created_at FROM users WHERE username = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $message = 'CSRF token không hợp lệ';
        $messageType = 'danger';
    } else {
        if ($_POST['action'] === 'update_profile') {
            $hoten = trim($_POST['hoten'] ?? '');
            $dienthoai = trim($_POST['dienthoai'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $diachi = trim($_POST['diachi'] ?? '');
            
            $stmt = $conn->prepare("UPDATE users SET hoten = ?, dienthoai = ?, email = ?, diachi = ? WHERE username = ?");
            if ($stmt->execute([$hoten, $dienthoai, $email, $diachi, $userId])) {
                $message = 'Cập nhật thông tin thành công!';
                $messageType = 'success';
                // Refresh user data
                $stmt = $conn->prepare("SELECT id, username, password, hoten, email, sodienthoai, diachi, role, trangthai, setlock, avatar_url, auth_provider, created_at FROM users WHERE username = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = 'Có lỗi xảy ra khi cập nhật';
                $messageType = 'danger';
            }
        }
        
        if ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (!password_verify($currentPassword, $user['password'] ?? '')) {
                $message = 'Mật khẩu hiện tại không đúng';
                $messageType = 'danger';
            } elseif (strlen($newPassword) < 6) {
                $message = 'Mật khẩu mới phải có ít nhất 6 ký tự';
                $messageType = 'danger';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'Mật khẩu xác nhận không khớp';
                $messageType = 'danger';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                if ($stmt->execute([$hashedPassword, $userId])) {
                    $message = 'Đổi mật khẩu thành công!';
                    $messageType = 'success';
                } else {
                    $message = 'Có lỗi xảy ra khi đổi mật khẩu';
                    $messageType = 'danger';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f5; }
        .profile-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2980b9);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 32px; font-weight: 700;
        }
        
        .nav-pills .nav-link {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../components/navbar.php'; ?>
    
    <div class="profile-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../../index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active">Thông tin tài khoản</li>
            </ol>
        </nav>
        
        <!-- Profile Header -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['hoten'] ?? $userId, 0, 1)) ?>
                </div>
                <div>
                    <h3 class="mb-1"><?= htmlspecialchars($user['hoten'] ?? $userId) ?></h3>
                    <p class="text-muted mb-0">@<?= htmlspecialchars($userId) ?></p>
                </div>
            </div>
            
            <!-- Tabs -->
            <ul class="nav nav-pills mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="pill" href="#profile-tab">
                        <i class="fas fa-user me-1"></i>Thông tin cá nhân
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#password-tab">
                        <i class="fas fa-lock me-1"></i>Đổi mật khẩu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mgiohang/wishlist.php">
                        <i class="fas fa-heart me-1"></i>Yêu thích
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mgiohang/order_tracking.php">
                        <i class="fas fa-box me-1"></i>Đơn hàng
                    </a>
                </li>
            </ul>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile-tab">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <?= csrf_field() ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ tên</label>
                                <input type="text" name="hoten" class="form-control" 
                                       value="<?= htmlspecialchars($user['hoten'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" name="dienthoai" class="form-control" 
                                       value="<?= htmlspecialchars($user['dienthoai'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Địa chỉ</label>
                                <textarea name="diachi" class="form-control" rows="3"><?= htmlspecialchars($user['diachi'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Lưu thay đổi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Password Tab -->
                <div class="tab-pane fade" id="password-tab">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <?= csrf_field() ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-1"></i>Đổi mật khẩu
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../../components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>