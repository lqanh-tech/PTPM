<?php

require_once __DIR__ . '/../mod/sessionManager.php';

SessionManager::start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    $_SESSION['error_message'] = 'Vui lòng đăng nhập để thực hiện chức năng này!';
    header('Location: ../../userLogin.php');
    exit();
}

require_once '../mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Yêu cầu không hợp lệ!';
    header('Location: giohangView.php');
    exit();
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'ID đơn hàng không hợp lệ!';
    header('Location: giohangView.php');
    exit();
}

if (empty($reason)) {
    $_SESSION['error_message'] = 'Vui lòng nhập lý do đổi/trả hàng!';
    header('Location: orderDetailView.php?id=' . $orderId);
    exit();
}

try {

    if (isset($_SESSION['USER'])) {
        $checkSql = "SELECT id, ma_don_hang_text, trang_thai, trang_thai_doi_tra 
                     FROM don_hang 
                     WHERE id = ? AND ma_nguoi_dung = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$orderId, $_SESSION['USER']]);
    } else {

        $checkSql = "SELECT id, ma_don_hang_text, trang_thai, trang_thai_doi_tra 
                     FROM don_hang 
                     WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$orderId]);
    }
    
    $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error_message'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập!';
        header('Location: giohangView.php');
        exit();
    }
    
    if ($order['trang_thai'] !== 'approved') {
        $_SESSION['error_message'] = 'Chỉ có thể yêu cầu đổi/trả hàng cho đơn hàng đã được duyệt!';
        header('Location: orderDetailView.php?id=' . $orderId);
        exit();
    }
    
    $returnStatus = isset($order['trang_thai_doi_tra']) ? $order['trang_thai_doi_tra'] : 'none';
    if ($returnStatus !== 'none' && $returnStatus !== null) {
        $_SESSION['error_message'] = 'Đơn hàng này đã có yêu cầu đổi/trả trước đó!';
        header('Location: orderDetailView.php?id=' . $orderId);
        exit();
    }
    
    $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'trang_thai_doi_tra'";
    $checkColumnStmt = $conn->query($checkColumnSql);
    
    if ($checkColumnStmt->rowCount() == 0) {

        $alterSql = "ALTER TABLE don_hang 
                     ADD COLUMN trang_thai_doi_tra ENUM('none', 'requested', 'approved', 'rejected') DEFAULT 'none',
                     ADD COLUMN ly_do_doi_tra TEXT DEFAULT NULL,
                     ADD COLUMN ngay_yeu_cau_doi_tra DATETIME DEFAULT NULL";
        $conn->exec($alterSql);
    }
    
    $updateSql = "UPDATE don_hang 
                  SET trang_thai_doi_tra = 'requested', 
                      ly_do_doi_tra = ?, 
                      ngay_yeu_cau_doi_tra = NOW() 
                  WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if ($updateStmt->execute([$reason, $orderId])) {

        error_log("Return request created for order #$orderId by user: $username");
        
        $_SESSION['success_message'] = 'Đã gửi yêu cầu đổi/trả hàng thành công! Chúng tôi sẽ xem xét và phản hồi trong vòng 24-48 giờ.';
        header('Location: orderDetailView.php?id=' . $orderId);
        exit();
    } else {
        $_SESSION['error_message'] = 'Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại!';
        header('Location: orderDetailView.php?id=' . $orderId);
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Error processing return request: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: orderDetailView.php?id=' . $orderId);
    exit();
}
?>
