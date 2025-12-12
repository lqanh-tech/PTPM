<?php
/**
 * API kiểm tra trùng lặp username, email, phone
 * Dùng cho validation real-time khi đăng ký
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../mod/database.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
$value = isset($_GET['value']) ? trim($_GET['value']) : '';

if (empty($type) || empty($value)) {
    echo json_encode(['exists' => false, 'message' => '']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($type) {
        case 'username':
            $sql = "SELECT COUNT(*) FROM user WHERE username = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$value]);
            $exists = $stmt->fetchColumn() > 0;
            
            echo json_encode([
                'exists' => $exists,
                'message' => $exists ? 'Tên đăng nhập đã được sử dụng' : ''
            ]);
            break;
            
        case 'email':
            $sql = "SELECT COUNT(*) FROM user WHERE email = ? AND email != ''";
            $stmt = $db->prepare($sql);
            $stmt->execute([$value]);
            $exists = $stmt->fetchColumn() > 0;
            
            echo json_encode([
                'exists' => $exists,
                'message' => $exists ? 'Email đã được đăng ký bởi tài khoản khác' : ''
            ]);
            break;
            
        case 'phone':
            $sql = "SELECT COUNT(*) FROM user WHERE dienthoai = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$value]);
            $exists = $stmt->fetchColumn() > 0;
            
            echo json_encode([
                'exists' => $exists,
                'message' => $exists ? 'Số điện thoại đã được đăng ký bởi tài khoản khác' : ''
            ]);
            break;
            
        default:
            echo json_encode(['exists' => false, 'message' => 'Invalid type']);
    }
    
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
