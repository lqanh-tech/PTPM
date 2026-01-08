<?php
session_start();

function displaySessionInfo() {
    echo "<h3>Thông tin Session</h3>";
    echo "<pre>";
    
    if (empty($_SESSION)) {
        echo "Không có thông tin session nào.";
    } else {
        foreach ($_SESSION as $key => $value) {
            echo htmlspecialchars($key) . ": " . htmlspecialchars(print_r($value, true)) . "\n";
        }
    }
    
    echo "</pre>";
}

function displayCookieInfo() {
    echo "<h3>Thông tin Cookie</h3>";
    echo "<pre>";
    
    if (empty($_COOKIE)) {
        echo "Không có cookie nào.";
    } else {
        foreach ($_COOKIE as $key => $value) {
            echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "\n";
        }
    }
    
    echo "</pre>";
}

function displayServerInfo() {
    echo "<h3>Thông tin Server</h3>";
    echo "<pre>";
    
    $serverVars = [
        'HTTP_HOST',
        'REQUEST_URI',
        'SCRIPT_NAME',
        'DOCUMENT_ROOT',
        'SERVER_NAME',
        'SERVER_PORT',
        'HTTPS',
        'REQUEST_METHOD',
        'HTTP_USER_AGENT',
        'REMOTE_ADDR'
    ];
    
    foreach ($serverVars as $var) {
        if (isset($_SERVER[$var])) {
            echo htmlspecialchars($var) . ": " . htmlspecialchars($_SERVER[$var]) . "\n";
        }
    }
    
    echo "</pre>";
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : 'user';
    
    if (!empty($username)) {
        if ($userType === 'admin') {
            $_SESSION['ADMIN'] = $username;
            echo "<div class='alert alert-success'>Đã thiết lập SESSION['ADMIN'] = '$username'</div>";
        } else {
            $_SESSION['USER'] = $username;
            echo "<div class='alert alert-success'>Đã thiết lập SESSION['USER'] = '$username'</div>";
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    echo "<div class='alert alert-warning'>Đã xóa tất cả session</div>";
}

if (isset($_POST['action']) && $_POST['action'] === 'set_cookie') {
    $cookieName = isset($_POST['cookie_name']) ? trim($_POST['cookie_name']) : '';
    $cookieValue = isset($_POST['cookie_value']) ? trim($_POST['cookie_value']) : '';
    
    if (!empty($cookieName) && !empty($cookieValue)) {
        setcookie($cookieName, $cookieValue, time() + 3600, '/');
        echo "<div class='alert alert-success'>Đã thiết lập cookie '$cookieName' = '$cookieValue'</div>";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_cookie' && isset($_GET['name'])) {
    $cookieName = $_GET['name'];
    setcookie($cookieName, '', time() - 3600, '/');
    echo "<div class='alert alert-warning'>Đã xóa cookie '$cookieName'</div>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra Session và Cookie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .container {
            max-width: 800px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .card {
            margin-bottom: 20px;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Kiểm tra Session và Cookie</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Thiết lập Session</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên người dùng</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Loại người dùng</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="user_type_user" value="user" checked>
                                    <label class="form-check-label" for="user_type_user">
                                        Người dùng thường (USER)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="user_type_admin" value="admin">
                                    <label class="form-check-label" for="user_type_admin">
                                        Quản trị viên (ADMIN)
                                    </label>
                                </div>
                            </div>
                            <input type="hidden" name="action" value="login">
                            <button type="submit" class="btn btn-primary">Thiết lập Session</button>
                        </form>
                        <div class="mt-3">
                            <a href="?action=logout" class="btn btn-warning">Xóa tất cả Session</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Thiết lập Cookie</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="cookie_name" class="form-label">Tên Cookie</label>
                                <input type="text" class="form-control" id="cookie_name" name="cookie_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="cookie_value" class="form-label">Giá trị Cookie</label>
                                <input type="text" class="form-control" id="cookie_value" name="cookie_value" required>
                            </div>
                            <input type="hidden" name="action" value="set_cookie">
                            <button type="submit" class="btn btn-primary">Thiết lập Cookie</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <?php displaySessionInfo(); ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <?php displayCookieInfo(); ?>
                
                <?php if (!empty($_COOKIE)): ?>
                <div class="mt-3">
                    <h5>Xóa Cookie</h5>
                    <div class="list-group">
                        <?php foreach ($_COOKIE as $key => $value): ?>
                        <a href="?action=delete_cookie&name=<?php echo urlencode($key); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($key); ?>
                            <span class="badge bg-danger rounded-pill">Xóa</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <?php displayServerInfo(); ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between">
                    <a href="../../userLogin.php" class="btn btn-secondary">Quay lại trang đăng nhập</a>
                    <a href="../../../index.php" class="btn btn-primary">Đi đến trang chủ</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
