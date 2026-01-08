<?php
session_start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit();
}

include_once "../../config/config.php";
include_once "../../elements_LQA/mod/userCls.php";

$username = isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : $_SESSION['USER'];

$userObj = new user();

$allUsers = $userObj->UserGetAll();
$currentUser = null;

foreach ($allUsers as $user) {
    if ($user->username === $username) {
        $currentUser = $user;
        break;
    }
}

if (!$currentUser) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin người dùng']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        exit();
    }

    $result = $userObj->UserChangePassword($currentUser->iduser, $currentPassword, $newPassword);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
}
