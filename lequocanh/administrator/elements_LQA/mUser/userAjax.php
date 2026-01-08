<?php
session_start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$paths = [
    __DIR__ . '/../../elements_LQA/mod/database.php',
    __DIR__ . '/../mod/database.php',
    __DIR__ . '/../../mod/database.php',
    './elements_LQA/mod/database.php'
];

$found = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $found = true;
        break;
    }
}

if (!$found) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$userClsPaths = [
    __DIR__ . '/../../elements_LQA/mod/userCls.php',
    __DIR__ . '/../mod/userCls.php',
    __DIR__ . '/../../mod/userCls.php',
    './elements_LQA/mod/userCls.php'
];

$foundUserCls = false;
foreach ($userClsPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundUserCls = true;
        break;
    }
}

if (!$foundUserCls) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User class not found']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getUsers':

        $userObj = new user();
        $list_user = $userObj->UserGetAll();
        
        $html = '';
        if (count($list_user) > 0) {
            foreach ($list_user as $u) {
                $isAdmin = ($u->username === 'admin');
                $html .= '<tr>';
                $html .= '<td>' . $u->iduser . '</td>';
                $html .= '<td>' . htmlspecialchars($u->username) . '</td>';
                $html .= '<td>' . str_repeat('*', 8) . '</td>';
                $html .= '<td>' . htmlspecialchars($u->hoten) . '</td>';
                $html .= '<td>' . ($u->gioitinh == 1 ? 'Nam' : 'Nữ') . '</td>';
                $html .= '<td>' . htmlspecialchars($u->ngaysinh) . '</td>';
                $html .= '<td>' . htmlspecialchars($u->diachi) . '</td>';
                $html .= '<td>' . htmlspecialchars($u->dienthoai) . '</td>';
                $html .= '<td>' . ($u->trangthai == 1 ? 'Hoạt động' : 'Bị khóa') . '</td>';
                $html .= '<td>';
                
                if (!$isAdmin || isset($_SESSION['ADMIN'])) {
                    $html .= '<a href="index.php?req=userupdate&iduser=' . $u->iduser . '" class="btn-action btn-edit" title="Sửa"><i class="fas fa-edit"></i></a>';
                    
                    if (!$isAdmin) {
                        $lockIcon = $u->trangthai == 1 ? 'fa-lock' : 'fa-unlock';
                        $lockTitle = $u->trangthai == 1 ? 'Khóa tài khoản' : 'Mở khóa tài khoản';
                        $html .= '<a href="./elements_LQA/mUser/userAct.php?reqact=setlock&iduser=' . $u->iduser . '&setlock=' . $u->trangthai . '" class="btn-action btn-lock" title="' . $lockTitle . '"><i class="fas ' . $lockIcon . '"></i></a>';
                        
                        $html .= '<a href="./elements_LQA/mUser/userAct.php?reqact=deleteuser&iduser=' . $u->iduser . '" class="btn-action btn-delete" title="Xóa" onclick="return confirm(\'Bạn có chắc chắn muốn xóa người dùng này?\');"><i class="fas fa-trash-alt"></i></a>';
                    }
                }
                
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="10" class="text-center">Không có người dùng nào</td></tr>';
        }
        
        echo $html;
        break;
        
    case 'getUserCount':

        $userObj = new user();
        $list_user = $userObj->UserGetAll();
        echo count($list_user);
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
