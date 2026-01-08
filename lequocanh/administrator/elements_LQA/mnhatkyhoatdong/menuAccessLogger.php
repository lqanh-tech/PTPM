<?php

$helperPaths = [
    __DIR__ . '/nhatKyHoatDongHelper.php',
    __DIR__ . '/../mnhatkyhoatdong/nhatKyHoatDongHelper.php',
    './elements_LQA/mnhatkyhoatdong/nhatKyHoatDongHelper.php'
];

$foundHelper = false;
foreach ($helperPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundHelper = true;
        break;
    }
}

if (!$foundHelper) {
    error_log("MenuAccessLogger: Không thể tìm thấy nhatKyHoatDongHelper.php");
    return;
}

function ghiNhatKyTruyCapMenu($username, $menuReq, $menuName) {
    if (empty($username) || empty($menuReq)) {
        return false;
    }
    
    $menuMapping = [
        'hanghoaview' => ['Sản phẩm', 'Xem danh sách sản phẩm'],
        'loaihangview' => ['Loại hàng', 'Xem danh sách loại hàng'],
        'userview' => ['Khách hàng', 'Xem danh sách khách hàng'],
        'nhanvienview' => ['Nhân viên', 'Xem danh sách nhân viên'],
        'donhangview' => ['Đơn hàng', 'Xem danh sách đơn hàng'],
        'nhacungcapview' => ['Nhà cung cấp', 'Xem danh sách nhà cung cấp'],
        'mphieunhap' => ['Phiếu nhập', 'Xem phiếu nhập kho'],
        'mtonkho' => ['Tồn kho', 'Xem báo cáo tồn kho'],
        'baocaoview' => ['Báo cáo', 'Xem báo cáo thống kê'],
        'nhatKyHoatDongTichHop' => ['Thống kê', 'Xem thống kê hoạt động nhân viên'],
        'lichsumuahang' => ['Lịch sử', 'Xem lịch sử mua hàng'],
        'userprofile' => ['Hồ sơ', 'Xem hồ sơ cá nhân'],
        'userUpdateProfile' => ['Hồ sơ', 'Cập nhật hồ sơ cá nhân']
    ];
    
    if (isset($menuMapping[$menuReq])) {
        $doiTuong = $menuMapping[$menuReq][0];
        $chiTiet = $menuMapping[$menuReq][1];
    } else {
        $doiTuong = 'Menu';
        $chiTiet = "Truy cập menu: " . ($menuName ?: $menuReq);
    }
    
    return ghiNhatKyHoatDong($username, 'Truy cập', $doiTuong, null, $chiTiet);
}

function ghiNhatKyThoiGianTrang($username, $menuReq, $thoiGianGiay) {
    if (empty($username) || empty($menuReq) || $thoiGianGiay < 5) {
        return false;
    }
    
    $phut = floor($thoiGianGiay / 60);
    $giay = $thoiGianGiay % 60;
    $thoiGianText = $phut > 0 ? "{$phut} phút {$giay} giây" : "{$giay} giây";
    
    $menuMapping = [
        'hanghoaview' => 'Sản phẩm',
        'loaihangview' => 'Loại hàng',
        'userview' => 'Khách hàng',
        'nhanvienview' => 'Nhân viên',
        'donhangview' => 'Đơn hàng',
        'nhacungcapview' => 'Nhà cung cấp',
        'mphieunhap' => 'Phiếu nhập',
        'mtonkho' => 'Tồn kho',
        'baocaoview' => 'Báo cáo',
        'nhatKyHoatDongTichHop' => 'Thống kê',
        'lichsumuahang' => 'Lịch sử',
        'userprofile' => 'Hồ sơ',
        'userUpdateProfile' => 'Hồ sơ'
    ];
    
    $doiTuong = isset($menuMapping[$menuReq]) ? $menuMapping[$menuReq] : 'Menu';
    $chiTiet = "Thời gian làm việc: {$thoiGianText}";
    
    return ghiNhatKyHoatDong($username, 'Làm việc', $doiTuong, null, $chiTiet);
}

function ghiNhatKyThaoTacChiTiet($username, $thaoTac, $doiTuong, $doiTuongId = null, $chiTiet = '') {
    if (empty($username) || empty($thaoTac) || empty($doiTuong)) {
        return false;
    }
    
    return ghiNhatKyHoatDong($username, $thaoTac, $doiTuong, $doiTuongId, $chiTiet);
}

if (isset($_SESSION['USER']) || isset($_SESSION['ADMIN'])) {
    $currentUser = isset($_SESSION['USER']) ? $_SESSION['USER'] : $_SESSION['ADMIN'];
    $currentReq = isset($_GET['req']) ? $_GET['req'] : 'index';
    
    if ($currentReq !== 'index' && !empty($currentReq)) {
        ghiNhatKyTruyCapMenu($currentUser, $currentReq, '');
    }
}
?>
