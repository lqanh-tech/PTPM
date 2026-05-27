<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();

require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('baocaoview', $username)) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập!']);
    exit;
}

require_once '../mbaocao/baocaoCls.php';
$baoCao = new BaoCao();

$action = sanitizeInput($_GET['action'] ?? '', 'text');

switch ($action) {
    case 'getDoanhThuNgay':
        $date = sanitizeInput($_POST['date'] ?? date('Y-m-d'), 'text');
        $doanhThu = $baoCao->getDoanhThuNgay($date);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getDoanhThuThang':
        $month = sanitizeInput($_POST['month'] ?? date('m'), 'text');
        $year = sanitizeInput($_POST['year'] ?? date('Y'), 'text');
        $doanhThu = $baoCao->getDoanhThuThang($month, $year);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getDoanhThuNam':
        $year = sanitizeInput($_POST['year'] ?? date('Y'), 'text');
        $doanhThu = $baoCao->getDoanhThuNam($year);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getDoanhThuTheoKhoangThoiGian':
        $startDate = sanitizeInput($_POST['startDate'] ?? date('Y-m-d', strtotime('-30 days')), 'text');
        $endDate = sanitizeInput($_POST['endDate'] ?? date('Y-m-d'), 'text');
        $doanhThu = $baoCao->getDoanhThuTheoKhoangThoiGian($startDate, $endDate);
        echo json_encode(['success' => true, 'doanhThu' => $doanhThu]);
        break;
    
    case 'getSanPhamBanChay':
        $startDate = sanitizeInput($_POST['startDate'] ?? date('Y-m-d', strtotime('-30 days')), 'text');
        $endDate = sanitizeInput($_POST['endDate'] ?? date('Y-m-d'), 'text');
        $limit = sanitizeInput($_POST['limit'] ?? '10', 'int');
        $sanPhamBanChay = $baoCao->getSanPhamBanChay($startDate, $endDate, $limit);
        echo json_encode(['success' => true, 'sanPhamBanChay' => $sanPhamBanChay]);
        break;
    
    case 'getLoiNhuan':
        $startDate = sanitizeInput($_POST['startDate'] ?? date('Y-m-d', strtotime('-30 days')), 'text');
        $endDate = sanitizeInput($_POST['endDate'] ?? date('Y-m-d'), 'text');
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
