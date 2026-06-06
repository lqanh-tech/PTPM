<?php
declare(strict_types=1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['USER'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

require_once __DIR__ . '/../../administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_user_address') {
        $username = $_SESSION['USER'];
        $provinceId = intval($_POST['province_id'] ?? 0);
        $districtId = intval($_POST['district_id'] ?? 0);
        $wardId = $_POST['ward_id'] ?? '';
        $addressDetail = trim($_POST['address_detail'] ?? '');
        $receiverName = trim($_POST['receiver_name'] ?? '');
        $receiverPhone = trim($_POST['receiver_phone'] ?? '');
        
        if ($provinceId <= 0 || $districtId <= 0 || empty($addressDetail)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            exit;
        }
        
        $stmt = $db->prepare("
            UPDATE user 
            SET province_id = ?, district_id = ?, ward_id = ?, address_detail = ?, 
                diachi = ?, hoten = COALESCE(NULLIF(?, ''), hoten), dienthoai = COALESCE(NULLIF(?, ''), dienthoai)
            WHERE username = ?
        ");
        $stmt->execute([
            $provinceId, $districtId, $wardId ?: null, $addressDetail,
            $addressDetail, $receiverName, $receiverPhone, $username
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã lưu địa chỉ thành công'
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
