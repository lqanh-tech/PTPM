<?php
/**
 * API xử lý yêu cầu quên mật khẩu
 */

header('Content-Type: application/json; charset=utf-8');

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../mod/PasswordResetManager.php';

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ'
    ]);
    exit;
}

// Lấy identifier từ request
$identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';

if (empty($identifier)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập email hoặc tên đăng nhập'
    ]);
    exit;
}

try {
    $resetManager = new PasswordResetManager();
    
    // Tìm user
    $user = $resetManager->findUser($identifier);
    
    // Luôn trả về thông báo thành công để tránh lộ thông tin user tồn tại hay không
    // (Security best practice)
    $successMessage = 'Nếu tài khoản tồn tại, chúng tôi đã gửi email hướng dẫn đặt lại mật khẩu. Vui lòng kiểm tra hộp thư (bao gồm cả thư rác).';
    
    if (!$user) {
        // Log nhưng không tiết lộ cho user
        error_log("Password reset requested for non-existent user: " . $identifier);
        
        echo json_encode([
            'success' => true,
            'message' => $successMessage
        ]);
        exit;
    }
    
    // Kiểm tra user có email không
    if (empty($user->email)) {
        error_log("Password reset requested for user without email: " . $user->username);
        
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản này chưa đăng ký email. Vui lòng liên hệ quản trị viên để được hỗ trợ.'
        ]);
        exit;
    }
    
    // Kiểm tra rate limit
    if (!$resetManager->checkRateLimit($user->email, 3)) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau 1 giờ.'
        ]);
        exit;
    }
    
    // Tạo token
    $token = $resetManager->createResetToken($user->iduser, $user->email);
    
    // Gửi email
    $emailSent = $resetManager->sendResetEmail($user->email, $token, $user->username);
    
    if ($emailSent) {
        error_log("Password reset email sent to: " . $user->email . " for user: " . $user->username);
        
        echo json_encode([
            'success' => true,
            'message' => $successMessage
        ]);
    } else {
        error_log("Failed to send password reset email to: " . $user->email);
        
        echo json_encode([
            'success' => false,
            'message' => 'Không thể gửi email. Vui lòng thử lại sau hoặc liên hệ quản trị viên.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
    ]);
}
