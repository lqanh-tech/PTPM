<?php
/**
 * Quick Check: Kiểm tra nhanh cột email
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Kiểm tra cột email
    $stmt = $db->query('DESCRIBE user');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEmail = false;
    $emailColumn = null;
    
    foreach($columns as $col) {
        if ($col['Field'] === 'email') {
            $hasEmail = true;
            $emailColumn = $col;
            break;
        }
    }
    
    $response = [
        'success' => true,
        'hasEmailColumn' => $hasEmail,
        'emailColumn' => $emailColumn,
        'message' => $hasEmail ? 'Cột email đã tồn tại' : 'Cột email chưa tồn tại',
        'action' => $hasEmail ? 'Sẵn sàng sử dụng' : 'Cần chạy migration'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
