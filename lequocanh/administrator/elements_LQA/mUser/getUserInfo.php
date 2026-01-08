<?php
session_start();
require '../../elements_LQA/mod/userCls.php';

if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$iduser = isset($_GET['iduser']) ? intval($_GET['iduser']) : 0;

if ($iduser <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID người dùng không hợp lệ']);
    exit;
}

$userObj = new user();
$userData = $userObj->UserGetbyId($iduser);

if ($userData) {

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'iduser' => $userData->iduser,
            'username' => $userData->username,
            'hoten' => $userData->hoten,
            'dienthoai' => $userData->dienthoai,
            'diachi' => $userData->diachi,
            'email' => isset($userData->email) ? $userData->email : ''
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin người dùng']);
}
exit;
