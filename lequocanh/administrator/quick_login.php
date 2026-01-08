<?php
session_start();

if (isset($_POST['login'])) {
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['phanquyen'] = 1;
    $_SESSION['login'] = true;
    
    echo "<script>alert('Đăng nhập thành công!'); window.location.href = '?req=nhatKyHoatDongTichHop';</script>";
    exit;
}

if (isset($_SESSION['login']) && $_SESSION['login']) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px;'>";
    echo "<h3>✅ Đã đăng nhập với username: " . $_SESSION['username'] . "</h3>";
    echo "<p><a href='?req=nhatKyHoatDongTichHop' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Xem thống kê hoạt động nhân viên</a></p>";
    echo "<p><a href='?logout=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Đăng xuất</a></p>";
    echo "</div>";
}

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>alert('Đã đăng xuất!'); window.location.href = 'quick_login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập nhanh - Test</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .login-form { background: white; padding: 30px; border-radius: 10px; max-width: 400px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, button { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .title { text-align: center; color: #333; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="login-form">
        <h1 class="title">🔐 Đăng nhập nhanh để test</h1>
        
        <?php if (!isset($_SESSION['login']) || !$_SESSION['login']): ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Chọn tài khoản:</label>
                <select name="username" id="username" required>
                    <option value="">-- Chọn tài khoản --</option>
                    <option value="admin">admin (Quản trị viên)</option>
                    <option value="lequocanh">lequocanh (Nhân viên)</option>
                    <option value="manager1">manager1 (Quản lý)</option>
                    <option value="staff2">staff2 (Nhân viên)</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" name="login">Đăng nhập</button>
            </div>
        </form>
        
        <div style="background: #e2e3e5; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>📝 Hướng dẫn test:</h4>
            <ol>
                <li>Chọn một tài khoản và đăng nhập</li>
                <li>Truy cập trang thống kê hoạt động nhân viên</li>
                <li>Test các bộ lọc và xem dữ liệu</li>
                <li>Kiểm tra tab "Thống kê tổng quan" và "Nhật ký chi tiết"</li>
            </ol>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
