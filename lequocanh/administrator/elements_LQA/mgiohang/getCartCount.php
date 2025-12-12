<?php
/**
 * Get Cart Count API
 * 
 * Trả về số lượng sản phẩm trong giỏ hàng
 */

// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';

// Start session safely
SessionManager::start();

require_once __DIR__ . '/../mod/giohangCls.php';

header('Content-Type: application/json');

try {
    $giohang = new GioHang();
    
    // Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
    if (!$giohang->canUseCart()) {
        echo json_encode([
            'success' => false,
            'count' => 0,
            'message' => 'Vui lòng đăng nhập để sử dụng giỏ hàng'
        ]);
        exit();
    }
    
    // Lấy số lượng sản phẩm trong giỏ hàng
    $count = $giohang->getCartItemCount();
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    error_log('Error getting cart count: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Có lỗi xảy ra'
    ]);
}
?>
