<?php
require_once __DIR__ . '/mod/phanquyenCls.php';

$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
$isAdmin = isset($_SESSION['ADMIN']);
$isNhanVien = $phanQuyen->isNhanVien($username);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị</title>
    <style>
        .welcome-container {
            padding: 30px;
            text-align: center;
        }
        .welcome-title {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .welcome-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .user-info {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            margin: 10px 0;
        }
        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 14px;
            margin: 5px;
        }
        .role-admin { background: #e74c3c; color: white; }
        .role-staff { background: #27ae60; color: white; }
        .role-user { background: #95a5a6; color: white; }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1 class="welcome-title">
            <i class="fas fa-tachometer-alt"></i> Chào mừng đến Trang Quản Trị
        </h1>
        
        <div class="welcome-info">
            <p><strong>Xin chào:</strong></p>
            <div class="user-info">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?>
            </div>
            
            <p style="margin-top: 15px;"><strong>Vai trò:</strong></p>
            <?php if ($isAdmin): ?>
                <span class="role-badge role-admin"><i class="fas fa-crown"></i> Quản trị viên</span>
            <?php elseif ($isNhanVien): ?>
                <span class="role-badge role-staff"><i class="fas fa-user-tie"></i> Nhân viên</span>
            <?php else: ?>
                <span class="role-badge role-user"><i class="fas fa-user"></i> Người dùng</span>
            <?php endif; ?>
        </div>
        
        <?php if ($isNhanVien && !$isAdmin): ?>
        <div class="alert-warning">
            <i class="fas fa-info-circle"></i> 
            <strong>Lưu ý:</strong> Bạn chỉ có thể truy cập các chức năng đã được Admin phân quyền.
            <br>Nếu bạn không thấy menu nào ở bên trái, vui lòng liên hệ Admin để được gán quyền.
        </div>
        <?php endif; ?>
        
        <p style="color: #7f8c8d; margin-top: 20px;">
            Chọn một chức năng từ menu bên trái để bắt đầu.
        </p>
    </div>
</body>
</html>