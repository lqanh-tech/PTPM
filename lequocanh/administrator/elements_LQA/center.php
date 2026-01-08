<?php
require_once __DIR__ . '/mod/phanquyenCls.php';

$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
$isAdmin = isset($_SESSION['ADMIN']);
$isNhanVien = $phanQuyen->isNhanVien($username);

if (isset($_GET['req'])) {
    $request = $_GET['req'];

    error_log("Center.php - Yêu cầu truy cập module: $request, Username: $username, isAdmin: " . ($isAdmin ? 'true' : 'false') . ", isNhanVien: " . ($isNhanVien ? 'true' : 'false'));

    $hasAccess = false;
    
    if ($isAdmin) {

        $hasAccess = true;
    } else if ($isNhanVien) {

        $hasAccess = $phanQuyen->checkAccessForEmployee($request, $username);
    } else {

        $hasAccess = $phanQuyen->checkAccess($request, $username);
    }
    
    if (!$hasAccess) {
        echo '<div class="alert alert-danger">
                <strong>Lỗi:</strong> Bạn không có quyền truy cập vào chức năng này.
                <p>Module yêu cầu: ' . htmlspecialchars($request) . '</p>
                <p>Tài khoản: ' . htmlspecialchars($username) . '</p>
              </div>';
        error_log("Center.php - Từ chối quyền truy cập cho module: $request, Username: $username");
        exit;
    }

    error_log("Center.php - Cho phép truy cập module: $request, Username: $username");

    switch ($request) {
        case 'userview':
            require __DIR__ . '/mUser/userView.php';
            break;
        case 'updateuser':
            require __DIR__ . '/mUser/userUpdate.php';
            break;
        case 'userupdate':
            require __DIR__ . '/mUser/userUpdate.php';
            break;

        case 'userUpdateProfile':
            require __DIR__ . '/mUser/userUpdateProfile.php';
            break;
        case 'loaihangview':
            require __DIR__ . '/mLoaihang/loaihangView.php';
            break;
        case 'hanghoaview':
            require __DIR__ . '/mhanghoa/hanghoaView.php';
            break;
        case 'dongiaview':
            require __DIR__ . '/mdongia/dongiaView.php';
            break;
        case 'thuonghieuview':
            require __DIR__ . '/mthuonghieu/thuonghieuView.php';
            break;
        case 'donvitinhview':
            require __DIR__ . '/mdonvitinh/donvitinhView.php';
            break;
        case 'nhanvienview':
            require __DIR__ . '/mnhanvien/nhanvienView.php';
            break;
        case 'thuoctinhview':
            require __DIR__ . '/mthuoctinh/thuoctinhView.php';
            break;
        case 'thuoctinhhhview':
            require __DIR__ . '/mthuoctinhhh/thuoctinhhhView.php';
            break;
        case 'adminGiohangView':
            require __DIR__ . '/mgiohang/adminGiohangView.php';
            break;
        case 'hinhanhview':
            require __DIR__ . '/mhinhanh/hinhanhView.php';
            break;
        case 'nhacungcapview':
            require __DIR__ . '/mnhacungcap/nhacungcapView.php';
            break;
        case 'mphieunhap':
            require __DIR__ . '/mmphieunhap/mphieunhapView.php';
            break;
        case 'mphieunhapedit':
            require __DIR__ . '/mmphieunhap/mphieunhapEdit.php';
            break;
        case 'mchitietphieunhap':
            require __DIR__ . '/mmphieunhap/mchitietphieunhapView.php';
            break;
        case 'mchitietphieunhapedit':
            require __DIR__ . '/mmphieunhap/mchitietphieunhapEdit.php';
            break;
        case 'mtonkho':
            require __DIR__ . '/mmtonkho/mtonkhoView.php';
            break;
        case 'mtonkhoedit':
            require __DIR__ . '/mmtonkho/mtonkhoEdit.php';
            break;
        case 'mphieunhapfixtonkho':
            require __DIR__ . '/mmphieunhap/mphieunhapFixTonKho.php';
            break;
        case 'payment_config':
            require __DIR__ . '/madmin/payment_config.php';
            break;
        case 'cau_hinh_thanh_toan':
            require __DIR__ . '/madmin/payment_config.php';
            break;
        case 'marketing_content':
            require __DIR__ . '/madmin/marketing_content.php';
            break;
        case 'shipping_dashboard':
            require __DIR__ . '/madmin/shipping_dashboard.php';
            break;
        case 'shipping_report':
            require __DIR__ . '/madmin/shipping_report.php';
            break;
        case 'shipping_config':
            require __DIR__ . '/madmin/shipping_config.php';
            break;
        case 'orders':
            require __DIR__ . '/madmin/orders_v2.php';
            break;
        case 'don_hang':
            require __DIR__ . '/madmin/orders_v2.php';
            break;

        case 'khachhangview':
            require __DIR__ . '/mkhachhang/khachhangController.php';
            break;

        case 'lichsumuahang':
            require __DIR__ . '/mkhachhang/lichsumuahangController.php';
            break;

        case 'baocaoview':
            require __DIR__ . '/mbaocao/baocaoView.php';
            break;

        case 'doanhThuView':
            require __DIR__ . '/mbaocao/doanhThuView.php';
            break;

        case 'sanPhamBanChayView':
            require __DIR__ . '/mbaocao/sanPhamBanChayView.php';
            break;

        case 'loiNhuanView':
            require __DIR__ . '/mbaocao/loiNhuanView.php';
            break;

        case 'roleview':
            require __DIR__ . '/mrole/roleView.php';
            break;

        case 'vaiTroView':
            require __DIR__ . '/mphanquyen/vaiTroView.php';
            break;

        case 'nguoiDungVaiTroView':
            require __DIR__ . '/mphanquyen/nguoiDungVaiTroView.php';
            break;

        case 'danhSachVaiTroView':
            require __DIR__ . '/mphanquyen/danhSachVaiTroView.php';
            break;

        case 'nhatKyHoatDongTichHop':
            require __DIR__ . '/mnhatkyhoatdong/nhatKyHoatDongTichHop.php';
            break;

        case 'thongKeNhanVienCaiThien':
            require __DIR__ . '/mnhatkyhoatdong/thongKeNhanVienCaiThien.php';
            break;

        case 'quanLySanPhamDacBiet':
            require __DIR__ . '/../quan_ly_san_pham_dac_biet.php';
            break;
            
        case 'sanphamnoibat':
        case 'autoFeaturedDashboard':
        case 'manageFeatured':

            header('Location: index.php?req=quanLySanPhamDacBiet&tab=featured');
            exit;
            break;
            
        case 'addPromotion':
            require __DIR__ . '/msanphamnoibat/addPromotionView.php';
            break;
            
        case 'removePromotion':
            require __DIR__ . '/msanphamnoibat/removePromotionAct.php';
            break;
            
        case 'review_management':
            require __DIR__ . '/mreview_management/reviewManagementView.php';
            break;
            
        case 'support_tickets':
            require __DIR__ . '/msupport_tickets/supportTicketsView.php';
            break;
            
        case 'coupon':
            require __DIR__ . '/mcoupon/couponView.php';
            break;
    }
} else {
    require __DIR__ . '/default.php';
}
