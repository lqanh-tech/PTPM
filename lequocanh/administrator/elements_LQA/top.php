<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

echo '<div class="admin-header">';
echo '<div class="header-content">';

echo '<div class="system-info">';
echo '<div class="system-logo">';
echo '<i class="fas fa-cogs"></i>';
echo '</div>';
echo '<div class="system-title">';
echo '<h1>Hệ Thống Quản Lý</h1>';
echo '<p class="system-subtitle">Bảng điều khiển quản trị</p>';
echo '</div>';
echo '</div>';

echo '<div class="header-info">';
echo '<div class="datetime-display">';
$current_time = date('H:i:s - d/m/Y');
echo '<div class="time-section">';
echo '<i class="fas fa-clock"></i>';
echo '<span class="time-label">Thời gian:</span>';
echo '<span id="time-display" class="time-value">' . $current_time . '</span>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

echo '<div class="user-info-section">';

if (isset($_SESSION['ADMIN']) || isset($_SESSION['USER'])) {

    $namelogin = isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : $_SESSION['USER'];
    $userRole = isset($_SESSION['ADMIN']) ? 'Quản trị viên' : 'Người dùng';
    $roleIcon = isset($_SESSION['ADMIN']) ? 'fas fa-user-shield' : 'fas fa-user';
    $roleClass = isset($_SESSION['ADMIN']) ? 'admin-role' : 'user-role';

    echo '<div class="user-card">';
    echo '<div class="user-avatar">';
    echo '<i class="' . $roleIcon . '"></i>';
    echo '</div>';
    echo '<div class="user-details">';
    echo '<div class="user-greeting">';
    echo '<span class="greeting-text">Xin chào,</span>';
    echo '<span class="user-name">' . htmlspecialchars($namelogin) . '</span>';
    echo '</div>';
    echo '<div class="user-role ' . $roleClass . '">';
    echo '<i class="fas fa-circle"></i>';
    echo '<span>' . $userRole . '</span>';
    echo '</div>';

    if (isset($_COOKIE[$namelogin])) {
        echo '<div class="last-login">';
        echo '<i class="fas fa-history"></i>';
        echo '<span>Đăng nhập gần nhất: ' . htmlspecialchars($_COOKIE[$namelogin]) . '</span>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="user-card not-logged-in">';
    echo '<div class="user-avatar">';
    echo '<i class="fas fa-user-times"></i>';
    echo '</div>';
    echo '<div class="user-details">';
    echo '<div class="user-greeting">';
    echo '<span class="greeting-text">Chưa đăng nhập</span>';
    echo '</div>';
    echo '<div class="login-prompt">';
    echo '<a href="./userLogin.php" class="login-link">';
    echo '<i class="fas fa-sign-in-alt"></i>';
    echo '<span>Đăng nhập ngay</span>';
    echo '</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '</div>';

echo '<div class="status-section">';

if (isset($_GET['result'])) {
    if ($_GET['result'] == 'ok') {
        echo '<div class="status-alert success">';
        echo '<div class="status-icon">';
        echo '<i class="fas fa-check-circle"></i>';
        echo '</div>';
        echo '<div class="status-content">';
        echo '<h4>Thành công!</h4>';
        echo '<p>Thao tác đã được thực hiện thành công</p>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="status-alert error">';
        echo '<div class="status-icon">';
        echo '<i class="fas fa-exclamation-circle"></i>';
        echo '</div>';
        echo '<div class="status-content">';
        echo '<h4>Thất bại!</h4>';
        echo '<p>Có lỗi xảy ra trong quá trình thực hiện</p>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<div class="status-alert ready">';
    echo '<div class="status-icon">';
    echo '<i class="fas fa-cog fa-spin"></i>';
    echo '</div>';
    echo '<div class="status-content">';
    echo '<h4>Hệ thống sẵn sàng</h4>';
    echo '<p>Chọn chức năng để bắt đầu thao tác</p>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
?>

<style>

    .admin-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
        position: relative;
    }

    .admin-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
    }

    .header-content {
        padding: 25px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .system-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .system-logo {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .system-logo i {
        font-size: 24px;
        color: #ffffff;
    }

    .system-title h1 {
        margin: 0;
        color: #ffffff;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .system-subtitle {
        margin: 5px 0 0 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        font-weight: 400;
    }

    .header-info {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .datetime-display {
        background: rgba(255, 255, 255, 0.15);
        padding: 15px 20px;
        border-radius: 10px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .time-section {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #ffffff;
    }

    .time-section i {
        font-size: 16px;
        color: #4ecdc4;
    }

    .time-label {
        font-size: 14px;
        font-weight: 500;
        opacity: 0.9;
    }

    .time-value {
        font-size: 16px;
        font-weight: 700;
        font-family: 'Courier New', monospace;
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 8px;
        border-radius: 6px;
    }

    .user-info-section {
        margin: 20px 0;
    }

    .user-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #e8ecef;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
    }

    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #ffffff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .user-details {
        flex: 1;
    }

    .user-greeting {
        margin-bottom: 8px;
    }

    .greeting-text {
        color: #6c757d;
        font-size: 14px;
        margin-right: 8px;
    }

    .user-name {
        color: #2c3e50;
        font-size: 18px;
        font-weight: 700;
    }

    .user-role {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .user-role i {
        font-size: 8px;
    }

    .admin-role {
        color: #e74c3c;
        font-weight: 600;
    }

    .user-role:not(.admin-role) {
        color: #3498db;
        font-weight: 500;
    }

    .last-login {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        font-size: 13px;
    }

    .last-login i {
        font-size: 12px;
        color: #95a5a6;
    }

    .not-logged-in .user-avatar {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    }

    .login-prompt {
        margin-top: 10px;
    }

    .login-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #3498db;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .login-link:hover {
        color: #2980b9;
    }

    .status-section {
        margin: 20px 0;
    }

    .status-alert {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 4px solid;
    }

    .status-alert.success {
        border-left-color: #27ae60;
        background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
    }

    .status-alert.error {
        border-left-color: #e74c3c;
        background: linear-gradient(135deg, #ffffff 0%, #fff8f8 100%);
    }

    .status-alert.ready {
        border-left-color: #f39c12;
        background: linear-gradient(135deg, #ffffff 0%, #fffcf8 100%);
    }

    .status-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .success .status-icon {
        background: rgba(39, 174, 96, 0.1);
        color: #27ae60;
    }

    .error .status-icon {
        background: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
    }

    .ready .status-icon {
        background: rgba(243, 156, 18, 0.1);
        color: #f39c12;
    }

    .status-content h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 700;
        color: #2c3e50;
    }

    .status-content p {
        margin: 0;
        color: #6c757d;
        font-size: 14px;
    }

    #signoutbutton {
        top: 80px !important;
        z-index: 1000 !important;
        transition: all 0.3s ease;
    }

    #signoutbutton:hover {
        transform: scale(1.05);
    }

    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }

        .system-title h1 {
            font-size: 24px;
        }

        .datetime-display {
            padding: 12px 16px;
        }

        .time-value {
            font-size: 14px;
        }

        .user-card {
            flex-direction: column;
            text-align: center;
            padding: 15px;
        }

        .status-alert {
            flex-direction: column;
            text-align: center;
            padding: 15px;
        }

        #signoutbutton {
            top: 100px !important;
            right: 5px !important;
        }
    }

    @media (max-width: 480px) {
        .admin-header {
            margin: 10px;
            border-radius: 8px;
        }

        .header-content {
            padding: 15px;
        }

        .system-title h1 {
            font-size: 20px;
        }

        .system-logo {
            width: 50px;
            height: 50px;
        }

        .system-logo i {
            font-size: 20px;
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .fa-spin {
        animation: spin 2s linear infinite;
    }
</style>

<script>

    function updateCurrentTime() {
        const now = new Date();

        const options = {
            timeZone: 'Asia/Ho_Chi_Minh',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour12: false
        };

        const formatter = new Intl.DateTimeFormat('vi-VN', options);
        const parts = formatter.formatToParts(now);

        const timeString = `${parts.find(p => p.type === 'hour').value}:${parts.find(p => p.type === 'minute').value}:${parts.find(p => p.type === 'second').value} - ${parts.find(p => p.type === 'day').value}/${parts.find(p => p.type === 'month').value}/${parts.find(p => p.type === 'year').value}`;

        const timeDisplay = document.getElementById('time-display');
        if (timeDisplay) {
            timeDisplay.textContent = timeString;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCurrentTime();

        setInterval(updateCurrentTime, 1000);
    });
</script>