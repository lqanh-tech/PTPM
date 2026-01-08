<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();

require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess($username, 'baocaoview')) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập!']);
    exit;
}

require_once '../mbaocao/baocaoCls.php';
$baoCao = new BaoCao();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getDoanhThuNgay':
        $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $doanhThu = $baoCao->getDoanhThuNgay($date);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getDoanhThuThang':
        $month = isset($_POST['month']) ? $_POST['month'] : date('m');
        $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
        $doanhThu = $baoCao->getDoanhThuThang($month, $year);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getDoanhThuNam':
        $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
        $doanhThu = $baoCao->getDoanhThuNam($year);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getDoanhThuTheoKhoangThoiGian':
        $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d');
        $doanhThu = $baoCao->getDoanhThuTheoKhoangThoiGian($startDate, $endDate);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getSanPhamBanChay':
        $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d');
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $sanPhamBanChay = $baoCao->getSanPhamBanChay($startDate, $endDate, $limit);
        echo json_encode(['success' => true, 'sanPhamBanChay' => $sanPhamBanChay]);
        break;
    
    case 'getLoiNhuan':
        $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d');
        $loiNhuan = $baoCao->getLoiNhuan($startDate, $endDate);
        echo json_encode(['success' => true, 'loiNhuan' => $loiNhuan]);
        break;
    
    case 'getLoiNhuanTheoSanPham':
        $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d');
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $loiNhuanTheoSanPham = $baoCao->getLoiNhuanTheoSanPham($startDate, $endDate, $limit);
        echo json_encode(['success' => true, 'loiNhuanTheoSanPham' => $loiNhuanTheoSanPham]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ!']);
        break;
}
?>
