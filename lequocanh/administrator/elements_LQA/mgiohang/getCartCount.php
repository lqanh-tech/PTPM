<?php

require_once __DIR__ . '/../mod/sessionManager.php';

SessionManager::start();

require_once __DIR__ . '/../mod/giohangCls.php';

header('Content-Type: application/json');

try {
    $giohang = new GioHang();
    
    if (!$giohang->canUseCart()) {
        echo json_encode([
            'success' => false,
            'count' => 0,
            'message' => 'Vui lòng đăng nhập để sử dụng giỏ hàng'
        ]);
        exit();
    }
    
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
