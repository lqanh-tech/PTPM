<?php

require_once __DIR__ . '/../includes/csrf_helper.php';
?>
<!doctype html>
<html lang="en">

<head>
    <title>Đăng nhập</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?= csrf_meta() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css_LQA/toast-notification.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            position: relative;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating input {
            border-radius: 10px;
            border: 2px solid #eee;
            padding: 1rem 0.75rem;
        }

        .form-floating input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 0.8rem;
            background: #0d6efd;
            border-radius: 10px;
            border: none;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .signup-link a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .signup-link a:hover {
            color: #0a58ca;
        }

        .signup-link a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: #0d6efd;
            left: 0;
            bottom: -2px;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .signup-link a:hover::after {
            transform: scaleX(1);
        }

        .form-floating input.is-invalid {
            animation: shake 0.5s;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: fadeIn 0.3s ease-in;
        }

        .error-message i {
            margin-right: 8px;
            font-size: 1rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Đăng Nhập</h2>
            <p>Vui lòng đăng nhập để tiếp tục</p>
        </div>

        <form name="login" method="post" action="./elements_LQA/mUser/userAct.php?reqact=checklogin" id="loginForm">
            <?= csrf_field() ?>
            <?php if (isset($_GET['register']) && $_GET['register'] == 'success'): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    Tên đăng nhập hoặc mật khẩu không chính xác
                </div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập" autocomplete="username">
                <label for="username"><i class="fas fa-user me-2"></i>Tên đăng nhập</label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" autocomplete="current-password">
                <label for="password"><i class="fas fa-lock me-2"></i>Mật khẩu</label>
            </div>

            <button type="submit" class="btn btn-login">Đăng nhập</button>
            
            <div class="forgot-password-link" style="text-align: right; margin-top: 10px;">
                <a href="forgot_password.php" style="color: #6c757d; font-size: 0.9rem; text-decoration: none;">
                    <i class="fas fa-key me-1"></i>Quên mật khẩu?
                </a>
            </div>
        </form>

        <div class="signup-link">
            Chưa có tài khoản? <a href="signUp.php"><i class="fas fa-user-plus me-1"></i>Đăng ký ngay</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js_LQA/toast-notification.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                let isValid = true;
                $('.form-control').removeClass('is-invalid');

                let username = $('#username').val().trim();
                $('#username').val(username);

                if (username === '') {
                    $('#username').addClass('is-invalid');
                    isValid = false;
                }

                if ($('#password').val() === '') {
                    $('#password').addClass('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    Toast.error('Vui lòng điền đầy đủ thông tin đăng nhập');
                } else {

                    console.log('Đang đăng nhập với username: ' + username);
                }
            });

            $('.form-control').on('input', function() {
                $(this).removeClass('is-invalid');
            });

            $('#username').focus();
        });
    </script>
    
    <!-- CSRF Protection Helper -->
    <script src="../public_files/js/csrf-helper.js"></script>
</body>

</html>