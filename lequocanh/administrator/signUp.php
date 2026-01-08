<?php
require_once './elements_LQA/mod/userCls.php';
require_once './elements_LQA/mod/userRoleCls.php';
require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/EmailService.php';

require_once __DIR__ . '/../includes/csrf_helper.php';

$errors = [];
$success = false;
$formData = [
    'username' => '',
    'fullname' => '',
    'gender' => '',
    'birthdate' => '',
    'address' => '',
    'province' => 0,
    'district' => 0,
    'ward' => 0,
    'phone' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new user();
    $userRole = new UserRole();
    $db = Database::getInstance()->getConnection();

    $formData['username'] = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $formData['fullname'] = trim($_POST['fullname'] ?? '');
    $formData['gender'] = $_POST['gender'] ?? '';
    $formData['birthdate'] = $_POST['birthdate'] ?? '';
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['province'] = intval($_POST['province'] ?? 0);
    $formData['district'] = intval($_POST['district'] ?? 0);
    $formData['ward'] = intval($_POST['ward'] ?? 0);
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');

    if (empty($formData['username'])) {
        $errors['username'] = 'Vui lòng nhập tên đăng nhập';
    } elseif (strlen($formData['username']) < 4) {
        $errors['username'] = 'Tên đăng nhập phải có ít nhất 4 ký tự';
    } elseif (strlen($formData['username']) > 30) {
        $errors['username'] = 'Tên đăng nhập không được quá 30 ký tự';
    } elseif (strpos($formData['username'], ' ') !== false) {
        $errors['username'] = 'Tên đăng nhập không được chứa dấu cách';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
        $errors['username'] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
    } elseif ($user->UserCheckUsername($formData['username'])) {
        $errors['username'] = 'Tên đăng nhập đã được sử dụng';
    }

    if (empty($password)) {
        $errors['password'] = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif (strlen($password) > 50) {
        $errors['password'] = 'Mật khẩu không được quá 50 ký tự';
    }
    
    if (empty($confirmPassword)) {
        $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
    }
    
    if (empty($formData['fullname'])) {
        $errors['fullname'] = 'Vui lòng nhập họ tên';
    } elseif (strlen($formData['fullname']) < 2) {
        $errors['fullname'] = 'Họ tên phải có ít nhất 2 ký tự';
    } elseif (strlen($formData['fullname']) > 100) {
        $errors['fullname'] = 'Họ tên không được quá 100 ký tự';
    }
    
    $genderValue = '1';
    if (!empty($formData['gender'])) {
        if (!in_array($formData['gender'], ['male', 'female'])) {
            $errors['gender'] = 'Giới tính không hợp lệ';
        } else {
            $genderValue = $formData['gender'] === 'male' ? '1' : '0';
        }
    }
    
    if (!empty($formData['birthdate'])) {
        $birthDate = strtotime($formData['birthdate']);
        $minAge = strtotime('-100 years');
        $maxAge = strtotime('-10 years');
        
        if ($birthDate === false) {
            $errors['birthdate'] = 'Ngày sinh không hợp lệ';
        } elseif ($birthDate < $minAge) {
            $errors['birthdate'] = 'Ngày sinh không hợp lệ';
        } elseif ($birthDate > $maxAge) {
            $errors['birthdate'] = 'Bạn phải từ 10 tuổi trở lên để đăng ký';
        }
    }

    if (!empty($formData['address']) && strlen($formData['address']) > 255) {
        $errors['address'] = 'Địa chỉ không được quá 255 ký tự';
    }
    
    if (empty($formData['phone'])) {
        $errors['phone'] = 'Vui lòng nhập số điện thoại';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $formData['phone'])) {
        $errors['phone'] = 'Số điện thoại phải có 10-11 chữ số';
    } elseif (!preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $formData['phone'])) {
        $errors['phone'] = 'Số điện thoại không đúng định dạng Việt Nam (VD: 0912345678)';
    } else {

        $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE dienthoai = ?");
        $stmt->execute([$formData['phone']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['phone'] = 'Số điện thoại đã được đăng ký bởi tài khoản khác';
        }
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Vui lòng nhập địa chỉ email';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không đúng định dạng';
    } elseif (strlen($formData['email']) > 100) {
        $errors['email'] = 'Email không được quá 100 ký tự';
    } else {

        $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE email = ? AND email != ''");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email đã được đăng ký bởi tài khoản khác';
        }
    }
    
    if (empty($errors)) {
        try {
            $result = $user->UserAdd(
                $formData['username'],
                $password,
                $formData['fullname'],
                $genderValue,
                $formData['birthdate'] ?: null,
                $formData['address'] ?: null,
                $formData['phone'],
                $formData['email'] ?: null,
                $formData['province'] ?: null,
                $formData['district'] ?: null,
                $formData['ward'] ?: null
            );

            if ($result) {
                $newUserId = $db->lastInsertId();
                $userRole->assignDefaultRole($newUserId);
                
                try {
                    $emailService = new EmailService();
                    $emailService->sendWelcomeEmail(
                        $formData['email'],
                        $formData['fullname'],
                        $formData['username']
                    );
                } catch (Exception $e) {

                    error_log("Failed to send welcome email: " . $e->getMessage());
                }
                
                header("Location: userLogin.php?register=success");
                exit();
            } else {
                $errors['general'] = 'Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Đăng ký tài khoản - LQA Shop</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrf_meta() ?>
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
        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }
        .signup-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .signup-header h2 {
            color: #2c3e50;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.3rem;
        }
        .form-label .required {
            color: #dc3545;
            margin-left: 2px;
        }
        .form-label .optional {
            color: #6c757d;
            font-weight: normal;
            font-size: 0.85rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.7rem 1rem;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        .form-control.is-valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .form-control.is-invalid + .invalid-feedback,
        .form-select.is-invalid + .invalid-feedback {
            display: block;
        }
        .valid-feedback {
            display: none;
            color: #28a745;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .form-control.is-valid + .valid-feedback {
            display: block;
        }
        .btn-signup {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-signup:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-signup:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
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
        .input-group-text {
            cursor: pointer;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .form-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .checking-indicator {
            display: none;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .checking-indicator.show {
            display: inline;
        }
    </style>
</head>
<body>
<div class="signup-container">
    <div class="signup-header">
        <h2><i class="fas fa-user-plus me-2"></i>Đăng Ký Tài Khoản</h2>
        <p class="text-muted">Điền thông tin để tạo tài khoản mới</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $errors['general']; ?></div>
    <?php endif; ?>

    <form id="signupForm" method="POST" novalidate>
        <?= csrf_field() ?>
        <!-- Username -->
        <div class="form-group">
            <label class="form-label">Tên đăng nhập <span class="required">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                   id="username" name="username" value="<?php echo htmlspecialchars($formData['username']); ?>"
                   placeholder="Nhập tên đăng nhập (4-30 ký tự)" maxlength="30">
            <div class="invalid-feedback"><?php echo $errors['username'] ?? 'Vui lòng nhập tên đăng nhập hợp lệ'; ?></div>
            <div class="valid-feedback"><i class="fas fa-check"></i> Tên đăng nhập hợp lệ</div>
            <div class="form-hint"><i class="fas fa-info-circle"></i> Chỉ chữ cái, số và dấu gạch dưới, không dấu cách</div>
            <span class="checking-indicator" id="username-checking"><i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...</span>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label class="form-label">Mật khẩu <span class="required">*</span></label>
            <div class="input-group">
                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                       id="password" name="password" placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" maxlength="50">
                <span class="input-group-text" id="togglePassword"><i class="fas fa-eye"></i></span>
            </div>
            <div class="password-strength" id="passwordStrength"></div>
            <div class="invalid-feedback"><?php echo $errors['password'] ?? 'Mật khẩu phải có ít nhất 6 ký tự'; ?></div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label class="form-label">Xác nhận mật khẩu <span class="required">*</span></label>
            <div class="input-group">
                <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                       id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu">
                <span class="input-group-text" id="toggleConfirm"><i class="fas fa-eye"></i></span>
            </div>
            <div class="invalid-feedback"><?php echo $errors['confirm_password'] ?? 'Mật khẩu xác nhận không khớp'; ?></div>
        </div>

        <!-- Fullname -->
        <div class="form-group">
            <label class="form-label">Họ và tên <span class="required">*</span></label>
            <input type="text" class="form-control <?php echo isset($errors['fullname']) ? 'is-invalid' : ''; ?>" 
                   id="fullname" name="fullname" value="<?php echo htmlspecialchars($formData['fullname']); ?>"
                   placeholder="Nhập họ và tên đầy đủ" maxlength="100">
            <div class="invalid-feedback"><?php echo $errors['fullname'] ?? 'Vui lòng nhập họ tên'; ?></div>
        </div>

        <!-- Phone -->
        <div class="form-group">
            <label class="form-label">Số điện thoại <span class="required">*</span></label>
            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                   id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>"
                   placeholder="VD: 0912345678" maxlength="11" inputmode="numeric">
            <div class="invalid-feedback"><?php echo $errors['phone'] ?? 'Số điện thoại không hợp lệ'; ?></div>
            <div class="valid-feedback"><i class="fas fa-check"></i> Số điện thoại hợp lệ</div>
            <span class="checking-indicator" id="phone-checking"><i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...</span>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                   id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>"
                   placeholder="VD: example@gmail.com" maxlength="100" required>
            <div class="invalid-feedback"><?php echo $errors['email'] ?? 'Vui lòng nhập email hợp lệ'; ?></div>
            <div class="valid-feedback"><i class="fas fa-check"></i> Email hợp lệ</div>
            <div class="form-hint"><i class="fas fa-info-circle"></i> Dùng để nhận email xác nhận đăng ký, khôi phục mật khẩu và thông báo đơn hàng</div>
            <span class="checking-indicator" id="email-checking"><i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...</span>
        </div>

        <!-- Gender -->
        <div class="form-group">
            <label class="form-label">Giới tính <span class="optional">(không bắt buộc)</span></label>
            <select class="form-select <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" id="gender" name="gender">
                <option value="">-- Chọn giới tính --</option>
                <option value="male" <?php echo $formData['gender'] === 'male' ? 'selected' : ''; ?>>Nam</option>
                <option value="female" <?php echo $formData['gender'] === 'female' ? 'selected' : ''; ?>>Nữ</option>
            </select>
            <div class="invalid-feedback"><?php echo $errors['gender'] ?? ''; ?></div>
        </div>

        <!-- Birthdate -->
        <div class="form-group">
            <label class="form-label">Ngày sinh <span class="optional">(không bắt buộc)</span></label>
            <input type="date" class="form-control <?php echo isset($errors['birthdate']) ? 'is-invalid' : ''; ?>" 
                   id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($formData['birthdate']); ?>"
                   max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>">
            <div class="invalid-feedback"><?php echo $errors['birthdate'] ?? 'Ngày sinh không hợp lệ'; ?></div>
        </div>

        <!-- Address - Cascade Dropdowns -->
        <div class="form-group">
            <label class="form-label">Tỉnh/Thành phố <span class="optional">(không bắt buộc)</span></label>
            <select class="form-select" id="province" name="province">
                <option value="">-- Chọn Tỉnh/Thành phố --</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Quận/Huyện <span class="optional">(không bắt buộc)</span></label>
            <select class="form-select" id="district" name="district" disabled>
                <option value="">-- Chọn Quận/Huyện --</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Phường/Xã <span class="optional">(không bắt buộc)</span></label>
            <select class="form-select" id="ward" name="ward" disabled>
                <option value="">-- Chọn Phường/Xã --</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Địa chỉ chi tiết <span class="optional">(không bắt buộc)</span></label>
            <input type="text" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" 
                   id="address" name="address" value="<?php echo htmlspecialchars($formData['address']); ?>"
                   placeholder="Số nhà, tên đường..." maxlength="255">
            <div class="invalid-feedback"><?php echo $errors['address'] ?? ''; ?></div>
            <div class="form-hint"><i class="fas fa-info-circle"></i> VD: Số 123, Đường Nguyễn Văn A</div>
        </div>

        <button type="submit" class="btn btn-signup mt-3" id="submitBtn">
            <i class="fas fa-user-plus me-2"></i>Đăng Ký
        </button>
    </form>

    <div class="login-link">
        Đã có tài khoản? <a href="userLogin.php"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập ngay</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let checkTimeout = {};
    
    // Load provinces on page load
    loadProvinces();
    
    // Cascade dropdown handlers
    $('#province').on('change', function() {
        const provinceId = $(this).val();
        $('#district').prop('disabled', true).html('<option value="">-- Chọn Quận/Huyện --</option>');
        $('#ward').prop('disabled', true).html('<option value="">-- Chọn Phường/Xã --</option>');
        
        if (provinceId) {
            loadDistricts(provinceId);
        }
    });
    
    $('#district').on('change', function() {
        const districtId = $(this).val();
        $('#ward').prop('disabled', true).html('<option value="">-- Chọn Phường/Xã --</option>');
        
        if (districtId) {
            loadWards(districtId);
        }
    });
    
    function loadProvinces() {
        $.ajax({
            url: '../api/get_address_data.php',
            type: 'GET',
            data: { action: 'get_all_provinces' },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.provinces) {
                    let options = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
                    response.provinces.forEach(function(province) {
                        options += `<option value="${province.id}">${province.name}</option>`;
                    });
                    $('#province').html(options);
                }
            },
            error: function() {
                console.error('Failed to load provinces');
            }
        });
    }
    
    function loadDistricts(provinceId) {
        $.ajax({
            url: '../api/get_address_data.php',
            type: 'GET',
            data: { 
                action: 'get_districts',
                province_id: provinceId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.districts) {
                    let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                    response.districts.forEach(function(district) {
                        options += `<option value="${district.id}">${district.name}</option>`;
                    });
                    $('#district').html(options).prop('disabled', false);
                }
            },
            error: function() {
                console.error('Failed to load districts');
            }
        });
    }
    
    function loadWards(districtId) {
        $.ajax({
            url: '../api/get_address_data.php',
            type: 'GET',
            data: { 
                action: 'get_wards',
                district_id: districtId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.wards) {
                    let options = '<option value="">-- Chọn Phường/Xã --</option>';
                    response.wards.forEach(function(ward) {
                        options += `<option value="${ward.id}">${ward.name}</option>`;
                    });
                    $('#ward').html(options).prop('disabled', false);
                }
            },
            error: function() {
                console.error('Failed to load wards');
            }
        });
    }
    
    $('#togglePassword, #toggleConfirm').on('click', function() {
        const input = $(this).siblings('input');
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
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
        
        validateField('password');
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
    
    $('#username').on('input', function() {
        const value = $(this).val().trim();
        clearTimeout(checkTimeout['username']);
        
        if (value.length < 4) {
            setFieldError('username', 'Tên đăng nhập phải có ít nhất 4 ký tự');
            return;
        }
        if (value.includes(' ')) {
            setFieldError('username', 'Tên đăng nhập không được chứa dấu cách');
            return;
        }
        if (!/^[a-zA-Z0-9_]+$/.test(value)) {
            setFieldError('username', 'Chỉ được chứa chữ cái, số và dấu gạch dưới');
            return;
        }
        
        $('#username-checking').addClass('show');
        checkTimeout['username'] = setTimeout(function() {
            checkDuplicate('username', value);
        }, 500);
    });
    
    $('#phone').on('input', function() {

        this.value = this.value.replace(/[^0-9]/g, '');
        
        const value = $(this).val();
        clearTimeout(checkTimeout['phone']);
        
        if (value.length === 0) {
            setFieldError('phone', 'Vui lòng nhập số điện thoại');
            return;
        }
        if (!/^[0-9]{10,11}$/.test(value)) {
            setFieldError('phone', 'Số điện thoại phải có 10-11 chữ số');
            return;
        }
        if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(value)) {
            setFieldError('phone', 'Số điện thoại không đúng định dạng VN');
            return;
        }
        
        $('#phone-checking').addClass('show');
        checkTimeout['phone'] = setTimeout(function() {
            checkDuplicate('phone', value);
        }, 500);
    });
    
    $('#email').on('input', function() {
        const value = $(this).val().trim();
        clearTimeout(checkTimeout['email']);
        
        if (value === '') {
            setFieldError('email', 'Vui lòng nhập địa chỉ email');
            return;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setFieldError('email', 'Email không đúng định dạng');
            return;
        }
        
        $('#email-checking').addClass('show');
        checkTimeout['email'] = setTimeout(function() {
            checkDuplicate('email', value);
        }, 500);
    });

    function checkDuplicate(type, value) {
        $.ajax({
            url: './elements_LQA/mUser/checkDuplicateAct.php',
            type: 'GET',
            data: { type: type, value: value },
            dataType: 'json',
            success: function(response) {
                $('#' + type + '-checking').removeClass('show');
                
                if (response.exists) {
                    setFieldError(type, response.message);
                } else {
                    setFieldValid(type);
                }
            },
            error: function() {
                $('#' + type + '-checking').removeClass('show');
            }
        });
    }
    
    function setFieldError(field, message) {
        const input = $('#' + field);
        input.removeClass('is-valid').addClass('is-invalid');
        input.siblings('.invalid-feedback').text(message);
    }
    
    function setFieldValid(field) {
        const input = $('#' + field);
        input.removeClass('is-invalid').addClass('is-valid');
    }
    
    $('#confirm_password').on('input', function() {
        const password = $('#password').val();
        const confirm = $(this).val();
        
        if (confirm === '') {
            $(this).removeClass('is-valid is-invalid');
        } else if (password !== confirm) {
            setFieldError('confirm_password', 'Mật khẩu xác nhận không khớp');
        } else {
            setFieldValid('confirm_password');
        }
    });
    
    $('#fullname').on('input', function() {
        const value = $(this).val().trim();
        if (value.length < 2) {
            setFieldError('fullname', 'Họ tên phải có ít nhất 2 ký tự');
        } else {
            setFieldValid('fullname');
        }
    });
    
    function validateField(field) {
        const input = $('#' + field);
        const value = input.val();
        
        switch(field) {
            case 'password':
                if (value.length < 6) {
                    setFieldError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
                } else {
                    setFieldValid('password');
                }

                if ($('#confirm_password').val() !== '') {
                    $('#confirm_password').trigger('input');
                }
                break;
        }
    }
    
    $('#signupForm').on('submit', function(e) {
        let isValid = true;
        const errors = [];
        
        const username = $('#username').val().trim();
        if (username === '' || username.length < 4 || username.includes(' ') || !/^[a-zA-Z0-9_]+$/.test(username)) {
            isValid = false;
            if (!$('#username').hasClass('is-invalid')) {
                setFieldError('username', 'Tên đăng nhập không hợp lệ');
            }
            errors.push('Tên đăng nhập');
        }
        
        if ($('#password').val().length < 6) {
            isValid = false;
            setFieldError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
            errors.push('Mật khẩu');
        }
        
        if ($('#password').val() !== $('#confirm_password').val()) {
            isValid = false;
            setFieldError('confirm_password', 'Mật khẩu xác nhận không khớp');
            errors.push('Xác nhận mật khẩu');
        }
        
        if ($('#fullname').val().trim().length < 2) {
            isValid = false;
            setFieldError('fullname', 'Vui lòng nhập họ tên');
            errors.push('Họ tên');
        }
        
        const phone = $('#phone').val();
        if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(phone)) {
            isValid = false;
            setFieldError('phone', 'Số điện thoại không hợp lệ');
            errors.push('Số điện thoại');
        }
        
        const email = $('#email').val().trim();
        if (email === '') {
            isValid = false;
            setFieldError('email', 'Vui lòng nhập địa chỉ email');
            errors.push('Email');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            isValid = false;
            setFieldError('email', 'Email không đúng định dạng');
            errors.push('Email');
        }
        
        if ($('.form-control.is-invalid, .form-select.is-invalid').length > 0) {
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            
            const firstError = $('.is-invalid').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 300);
                firstError.focus();
            }
            
            if (errors.length > 0) {
                alert('Vui lòng kiểm tra lại các trường: ' + errors.join(', '));
            }
        }
    });
});
</script>

<!-- CSRF Protection Helper -->
<script src="../public_files/js/csrf-helper.js"></script>
</body>
</html>
