<?php
/**
 * Clear Cart After Payment
 * File này được gọi để xóa giỏ hàng sau khi thanh toán thành công
 */

// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();

// Kiểm tra xem có order_id không
if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit();
}

$orderId = intval($_GET['order_id']);

try {
    require_once '../mod/database.php';
    require_once '../mod/giohangCls.php';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Lấy thông tin đơn hàng
    $orderSql = "SELECT ma_nguoi_dung FROM don_hang WHERE id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $userId = $order['ma_nguoi_dung'];
    
    // Kiểm tra xem user hiện tại có phải là chủ đơn hàng không
    $currentUser = SessionManager::get('USER');
    if ($currentUser != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // LƯU Ý: Không xóa toàn bộ giỏ hàng
    // Các sản phẩm đã thanh toán đã được xóa trong payment_confirm.php
    // File này chỉ để đảm bảo tính nhất quán, không cần xóa gì thêm
    
    // Lấy danh sách sản phẩm đã thanh toán trong đơn hàng này
    $orderItemsSql = "SELECT ma_san_pham FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
    $orderItemsStmt = $conn->prepare($orderItemsSql);
    $orderItemsStmt->execute([$orderId]);
    $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($orderItems)) {
        // Chỉ xóa các sản phẩm đã thanh toán
        $giohang = new GioHang();
        $removedCount = 0;
        
        foreach ($orderItems as $productId) {
            if ($giohang->removeFromCart($productId)) {
                $removedCount++;
            }
        }
        
        error_log("Removed $removedCount purchased items from cart for user: $userId");
        echo json_encode([
            'success' => true, 
            'message' => "Removed $removedCount purchased items from cart",
            'removed_count' => $removedCount
        ]);
    } else {
        error_log("No items to remove from cart for order: $orderId");
        echo json_encode(['success' => true, 'message' => 'No items to remove']);
    }
    
} catch (Exception $e) {
    error_log("Error in clear_cart_after_payment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
