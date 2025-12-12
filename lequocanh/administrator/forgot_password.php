<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quên mật khẩu - LQA Shop</title>
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
        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 2.5rem;
        }
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .forgot-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .forgot-header .icon i {
            font-size: 2rem;
            color: white;
        }
        .forgot-header h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .forgot-header p {
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
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
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
        .spinner-border {
            width: 1.2rem;
            height: 1.2rem;
            margin-right: 0.5rem;
        }
        .info-box {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #004085;
        }
        .info-box i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="icon">
                <i class="fas fa-key"></i>
            </div>
            <h2>Quên mật khẩu?</h2>
            <p>Nhập email hoặc tên đăng nhập để nhận link đặt lại mật khẩu</p>
        </div>

        <div id="alertContainer"></div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Chúng tôi sẽ gửi một email chứa link đặt lại mật khẩu. Link này có hiệu lực trong 60 phút.
        </div>

        <form id="forgotForm" method="post">
            <div class="form-floating">
                <input type="text" class="form-control" id="identifier" name="identifier" 
                       placeholder="Email hoặc tên đăng nhập" required autocomplete="email">
                <label for="identifier">
                    <i class="fas fa-envelope me-2"></i>Email hoặc tên đăng nhập
                </label>
            </div>

            <button type="submit" class="btn btn-submit" id="submitBtn">
                <i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu
            </button>
        </form>

        <div class="back-link">
            <a href="userLogin.php">
                <i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#forgotForm').on('submit', function(e) {
                e.preventDefault();
                
                const identifier = $('#identifier').val().trim();
                const submitBtn = $('#submitBtn');
                const alertContainer = $('#alertContainer');
                
                if (!identifier) {
                    showAlert('danger', 'Vui lòng nhập email hoặc tên đăng nhập');
                    return;
                }
                
                // Disable button và hiển thị loading
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status"></span>Đang xử lý...'
                );
                
                // Gửi request
                $.ajax({
                    url: './elements_LQA/mUser/forgotPasswordAct.php',
                    type: 'POST',
                    data: { identifier: identifier },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            $('#forgotForm')[0].reset();
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        showAlert('danger', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(
                            '<i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu'
                        );
                    }
                });
            });
            
            function showAlert(type, message) {
                const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
                $('#alertContainer').html(`
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${icon} me-2"></i>${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
            }
            
            // Auto focus
            $('#identifier').focus();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
