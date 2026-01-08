<?php

require_once __DIR__ . '/elements_LQA/mod/PasswordResetManager.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';
$validToken = false;
$resetRecord = null;

if (!empty($token)) {
    $resetManager = new PasswordResetManager();
    $resetRecord = $resetManager->validateToken($token);
    
    if ($resetRecord) {
        $validToken = true;
    } else {
        $error = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu link mới.';
    }
} else {
    $error = 'Không tìm thấy token. Vui lòng sử dụng link trong email.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($newPassword)) {
        $error = 'Vui lòng nhập mật khẩu mới';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {

        $result = $resetManager->resetPassword($token, $newPassword);
        
        if ($result) {
            $success = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập với mật khẩu mới.';
            $validToken = false;
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Đặt lại mật khẩu - LQA Shop</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 2.5rem;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .reset-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .reset-header .icon i {
            font-size: 2rem;
            color: white;
        }
        .reset-header .icon.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .reset-header .icon.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .reset-header h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .reset-header p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        .form-floating {
            margin-bottom: 1.5rem;
        }
        .form-floating input {
            border-radius: 10px;
            border: 2px solid #eee;
            padding: 1rem 0.75rem;
        }
        .form-floating input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: #28a745;
            border-radius: 10px;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .user-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .user-info .username {
            font-weight: 600;
            color: #667eea;
            font-size: 1.1rem;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        .form-floating {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <?php if (!empty($success)): ?>
            <!-- Thành công -->
            <div class="reset-header">
                <div class="icon success">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Thành công!</h2>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
            <a href="userLogin.php" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập ngay
            </a>
            
        <?php elseif (!$validToken): ?>
            <!-- Token không hợp lệ -->
            <div class="reset-header">
                <div class="icon error">
                    <i class="fas fa-times"></i>
                </div>
                <h2>Lỗi!</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <a href="forgot_password.php" class="btn-submit" style="text-decoration: none; display: block; text-align: center;">
                <i class="fas fa-redo me-2"></i>Yêu cầu link mới
            </a>
            <div class="back-link">
                <a href="userLogin.php">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
                </a>
            </div>
            
        <?php else: ?>
            <!-- Form đặt lại mật khẩu -->
            <div class="reset-header">
                <div class="icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h2>Đặt lại mật khẩu</h2>
                <p>Nhập mật khẩu mới cho tài khoản của bạn</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="user-info">
                <i class="fas fa-user me-2"></i>
                Tài khoản: <span class="username"><?php echo htmlspecialchars($resetRecord->username); ?></span>
            </div>
            
            <form method="post" id="resetForm">
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Mật khẩu mới" required minlength="6">
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>Mật khẩu mới
                    </label>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Xác nhận mật khẩu" required>
                    <label for="confirm_password">
                        <i class="fas fa-lock me-2"></i>Xác nhận mật khẩu
                    </label>
                    <i class="fas fa-eye password-toggle" id="toggleConfirm"></i>
                </div>
                
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-save me-2"></i>Đặt lại mật khẩu
                </button>
            </form>
            
            <div class="back-link">
                <a href="userLogin.php">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            $('.password-toggle').on('click', function() {
                const input = $(this).siblings('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });
            
            $('#password').on('input', function() {
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                const strengthBar = $('#passwordStrength');
                
                strengthBar.removeClass('strength-weak strength-medium strength-strong');
                
                if (password.length === 0) {
                    strengthBar.css('width', '0');
                } else if (strength < 2) {
                    strengthBar.addClass('strength-weak');
                } else if (strength < 4) {
                    strengthBar.addClass('strength-medium');
                } else {
                    strengthBar.addClass('strength-strong');
                }
            });
            
            function checkPasswordStrength(password) {
                let strength = 0;
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                return strength;
            }
            
            $('#resetForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirm = $('#confirm_password').val();
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Mật khẩu phải có ít nhất 6 ký tự');
                    return false;
                }
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Mật khẩu xác nhận không khớp');
                    return false;
                }
            });
            
            $('#password').focus();
        });
    </script>
</body>
</html>
