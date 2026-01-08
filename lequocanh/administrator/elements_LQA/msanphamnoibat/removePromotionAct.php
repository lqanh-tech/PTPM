<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../mod/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idhanghoa'])) {
    try {
        $db = Database::getInstance()->getConnection();
        $idhanghoa = intval($_POST['idhanghoa']);
        
        $stmt = $db->prepare("UPDATE hanghoa SET giakhuyenmai = NULL WHERE idhanghoa = ?");
        $stmt->execute([$idhanghoa]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa khuyến mãi. Giá gốc được giữ nguyên.'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
