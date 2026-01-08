<?php

require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

if (!isset($_SESSION['USER'])) {
    $_SESSION['error_message'] = 'Vui lòng đăng nhập để thực hiện thao tác này!';
    header('Location: ../../userLogin.php');
    exit();
}

require_once '../mod/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Phương thức không hợp lệ!';
    header('Location: giohangView.php');
    exit();
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$username = $_SESSION['USER'];

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'ID đơn hàng không hợp lệ!';
    header('Location: giohangView.php');
    exit();
}

if (empty($reason)) {
    $_SESSION['error_message'] = 'Vui lòng nhập lý do đổi/trả hàng!';
    header('Location: orderDetailView_v2.php?id=' . $orderId);
    exit();
}

if (strlen($reason) < 20) {
    $_SESSION['error_message'] = 'Lý do đổi/trả phải có ít nhất 20 ký tự!';
    header('Location: orderDetailView_v2.php?id=' . $orderId);
    exit();
}

if (strlen($reason) > 1000) {
    $_SESSION['error_message'] = 'Lý do đổi/trả không được vượt quá 1000 ký tự!';
    header('Location: orderDetailView_v2.php?id=' . $orderId);
    exit();
}

$reason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');

$db = Database::getInstance();
$conn = $db->getConnection();

try {

    $sql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId, $username]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error_message'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền thực hiện thao tác này!';
        header('Location: giohangView.php');
        exit();
    }
    
    if ($order['trang_thai'] != 'approved') {
        $_SESSION['error_message'] = 'Chỉ có thể yêu cầu đổi/trả đơn hàng đã được duyệt!';
        header('Location: orderDetailView_v2.php?id=' . $orderId);
        exit();
    }
    
    $returnStatus = isset($order['trang_thai_doi_tra']) ? $order['trang_thai_doi_tra'] : 'none';
    if ($returnStatus != 'none') {
        $_SESSION['error_message'] = 'Đơn hàng này đã có yêu cầu đổi/trả trước đó!';
        header('Location: orderDetailView_v2.php?id=' . $orderId);
        exit();
    }
    
    $updateSql = "UPDATE don_hang 
                  SET trang_thai_doi_tra = 'requested', 
                      ly_do_doi_tra = ?, 
                      ngay_yeu_cau_doi_tra = NOW(),
                      ngay_cap_nhat = NOW()
                  WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$reason, $orderId]);
    
    error_log("Yêu cầu đổi/trả đơn hàng #{$orderId} bởi user {$username}. Lý do: " . substr($reason, 0, 100));
    
    $_SESSION['success_message'] = 'Yêu cầu đổi/trả hàng đã được gửi thành công! Chúng tôi sẽ xem xét và liên hệ với bạn trong vòng 24-48 giờ.';
    header('Location: orderDetailView_v2.php?id=' . $orderId);
    exit();
    
} catch (Exception $e) {
    error_log("Lỗi khi tạo yêu cầu đổi/trả đơn hàng #{$orderId}: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau!';
    header('Location: orderDetailView_v2.php?id=' . $orderId);
    exit();
}
