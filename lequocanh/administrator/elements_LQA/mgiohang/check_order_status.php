<?php
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

SessionManager::start();

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
    exit;
}

$orderId = intval($_GET['order_id']);

$db = Database::getInstance();
$conn = $db->getConnection();

try {

    $sql = "SELECT trang_thai, trang_thai_thanh_toan, ma_nguoi_dung 
            FROM don_hang 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    if (isset($_SESSION['USER']) && $order['ma_nguoi_dung'] != $_SESSION['USER']) {

        if (!isset($_SESSION['ADMIN'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'order_status' => $order['trang_thai'],
        'payment_status' => $order['trang_thai_thanh_toan']
    ]);
    
} catch (Exception $e) {
    error_log("Error checking order status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
