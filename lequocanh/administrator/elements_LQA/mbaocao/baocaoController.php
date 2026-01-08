<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('baocaoview', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

$view = isset($_GET['view']) ? $_GET['view'] : 'default';

switch ($view) {
    case 'doanhthu':
        require_once './elements_LQA/mbaocao/doanhThuView.php';
        break;

    case 'sanphambanchay':
        require_once './elements_LQA/mbaocao/sanPhamBanChayView.php';
        break;

    case 'loinhuan':
        require_once './elements_LQA/mbaocao/loiNhuanView.php';
        break;

    default:
        require_once './elements_LQA/mbaocao/baocaoView.php';
        break;
}
