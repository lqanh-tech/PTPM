<?php
/**
 * Xử lý hủy đơn hàng (chỉ trong vòng 1 giờ)
 */

// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    $_SESSION['error_message'] = 'Vui lòng đăng nhập để thực hiện thao tác này!';
    header('Location: ../../userLogin.php');
    exit();
}

require_once '../mod/database.php';
require_once '../mod/mtonkhoCls.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$username = $_SESSION['USER'];

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'ID đơn hàng không hợp lệ!';
    header('Location: giohangView.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // 1. Lấy thông tin đơn hàng
    $sql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId, $username]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error_message'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn hàng này!';
        header('Location: giohangView.php');
        exit();
    }
    
    // 2. Kiểm tra trạng thái đơn hàng
    if ($order['trang_thai'] != 'pending') {
        $_SESSION['error_message'] = 'Chỉ có thể hủy đơn hàng đang chờ xác nhận!';
        header('Location: orderDetailView_v2.php?id=' . $orderId);
        exit();
    }
    
    // 3. Kiểm tra thời gian (phải trong vòng 1 giờ)
    $orderTime = strtotime($order['ngay_tao']);
    $currentTime = time();
    $hoursPassed = ($currentTime - $orderTime) / 3600;
    
    if ($hoursPassed > 1) {
        $_SESSION['error_message'] = 'Không thể hủy đơn hàng sau 1 giờ kể từ khi đặt hàng!';
        header('Location: orderDetailView_v2.php?id=' . $orderId);
        exit();
    }
    
    // 4. Bắt đầu transaction
    $conn->beginTransaction();
    
    // 5. Lấy danh sách sản phẩm trong đơn hàng
    $itemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Hoàn trả số lượng tồn kho
    $tonkho = new MTonKho();
    foreach ($items as $item) {
        $tonkho->updateSoLuong($item['ma_san_pham'], $item['so_luong'], true);
        error_log("Hoàn kho: Sản phẩm ID {$item['ma_san_pham']}, Số lượng: {$item['so_luong']}");
    }
    
    // 7. Cập nhật trạng thái đơn hàng
    $updateSql = "UPDATE don_hang SET trang_thai = 'cancelled', ngay_cap_nhat = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$orderId]);
    
    // 8. Commit transaction
    $conn->commit();
    
    // 9. Ghi log
    error_log("Đơn hàng #{$orderId} đã được hủy bởi user {$username}");
    
    $_SESSION['success_message'] = 'Đơn hàng đã được hủy thành công. Số lượng sản phẩm đã được hoàn trả vào kho.';
    header('Location: giohangView.php');
    exit();
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Lỗi khi hủy đơn hàng #{$orderId}: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại sau!';
    header('Location: orderDetailView_v2.php?id=' . $orderId);
    exit();
}
