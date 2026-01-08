<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không được hỗ trợ');
    }

    if (!isset($_POST['auto_apply_images'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $autoApply = $_POST['auto_apply_images'] === '1';

    $_SESSION['auto_apply_images'] = $autoApply;

    echo json_encode([
        'success' => true,
        'message' => 'Tùy chọn đã được lưu',
        'value' => $autoApply
    ]);
} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}