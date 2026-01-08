<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
$isAdmin = isset($_SESSION['ADMIN']) || $phanQuyen->isAdmin($username);
$isNhanVien = $phanQuyen->isNhanVien($username);
?>
<div class="left-menu">
    <div>
        <a href="index.php" class="<?php echo !isset($_GET['req']) ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Menu
        </a>
    </div>
    <div class="">
        <a href="#"><i class="fas fa-cogs"></i> Quản lý</a>
    </div>
    <div class="">
        <ul>
            <?php
            $current_page = isset($_GET['req']) ? $_GET['req'] : '';
            $menu_items = [
                'userview' => ['icon' => 'fas fa-users', 'text' => 'Tài khoản', 'admin_only' => true, 'hide_from_employee' => true],
                'vaiTroView' => ['icon' => 'fas fa-user-shield', 'text' => 'Vai trò người dùng', 'admin_only' => true, 'hide_from_employee' => false],
                'nguoiDungVaiTroView' => ['icon' => 'fas fa-user-cog', 'text' => 'Gán vai trò', 'admin_only' => true, 'hide_from_employee' => true],
                'danhSachVaiTroView' => ['icon' => 'fas fa-users-cog', 'text' => 'Danh sách vai trò', 'admin_only' => false, 'hide_from_employee' => false],
                'khachhangview' => ['icon' => 'fas fa-user-friends', 'text' => 'Khách hàng', 'admin_only' => false, 'hide_from_employee' => false],
                'loaihangview' => ['icon' => 'fas fa-tags', 'text' => 'Loại hàng', 'admin_only' => false, 'hide_from_employee' => false],
                'hanghoaview' => ['icon' => 'fas fa-box', 'text' => 'Hàng hóa', 'admin_only' => false, 'hide_from_employee' => false],
                'thuoctinhhhview' => ['icon' => 'fas fa-list-ul', 'text' => 'Thuộc tính hàng hóa', 'admin_only' => false, 'hide_from_employee' => false],
                'thuoctinhview' => ['icon' => 'fas fa-clipboard-list', 'text' => 'Thuộc tính', 'admin_only' => false, 'hide_from_employee' => false],
                'dongiaview' => ['icon' => 'fas fa-dollar-sign', 'text' => 'Đơn giá', 'admin_only' => false, 'hide_from_employee' => false],
                'thuonghieuview' => ['icon' => 'fas fa-trademark', 'text' => 'Thương hiệu', 'admin_only' => false, 'hide_from_employee' => false],
                'donvitinhview' => ['icon' => 'fas fa-balance-scale', 'text' => 'Đơn vị tính', 'admin_only' => false, 'hide_from_employee' => false],
                'nhanvienview' => ['icon' => 'fas fa-user-tie', 'text' => 'Nhân viên', 'admin_only' => false, 'hide_from_employee' => true],
                'adminGiohangView' => ['icon' => 'fas fa-shopping-cart', 'text' => 'Giỏ hàng', 'admin_only' => false, 'hide_from_employee' => false],
                'don_hang' => ['icon' => 'fas fa-clipboard-check', 'text' => 'Đơn hàng', 'admin_only' => true, 'hide_from_employee' => true],
                'cau_hinh_thanh_toan' => ['icon' => 'fas fa-money-check-alt', 'text' => 'Cấu hình thanh toán', 'admin_only' => true, 'hide_from_employee' => true],
                'hinhanhview' => ['icon' => 'fas fa-images', 'text' => 'Hình ảnh', 'admin_only' => false, 'hide_from_employee' => false],
                'nhacungcapview' => ['icon' => 'fas fa-truck', 'text' => 'Nhà cung cấp', 'admin_only' => false, 'hide_from_employee' => false],
                'mphieunhap' => ['icon' => 'fas fa-file-invoice', 'text' => 'Phiếu nhập kho', 'admin_only' => false, 'hide_from_employee' => false],
                'mtonkho' => ['icon' => 'fas fa-warehouse', 'text' => 'Tồn kho', 'admin_only' => false, 'hide_from_employee' => false],
                'baocaoview' => ['icon' => 'fas fa-chart-line', 'text' => 'Báo cáo tổng hợp', 'admin_only' => false, 'hide_from_employee' => false],
                'doanhThuView' => ['icon' => 'fas fa-money-bill-wave', 'text' => 'Báo cáo doanh thu', 'admin_only' => false, 'hide_from_employee' => false],
                'sanPhamBanChayView' => ['icon' => 'fas fa-fire', 'text' => 'Sản phẩm bán chạy', 'admin_only' => false, 'hide_from_employee' => false],
                'loiNhuanView' => ['icon' => 'fas fa-chart-pie', 'text' => 'Báo cáo lợi nhuận', 'admin_only' => false, 'hide_from_employee' => false],
                'nhatKyHoatDongTichHop' => ['icon' => 'fas fa-chart-bar', 'text' => 'Thống kê hoạt động nhân viên', 'admin_only' => false, 'hide_from_employee' => true],
                'sanphamnoibat' => ['icon' => 'fas fa-star', 'text' => 'Sản phẩm nổi bật', 'admin_only' => false, 'hide_from_employee' => false],
                'marketing_content' => ['icon' => 'fas fa-bullhorn', 'text' => 'Nội dung Marketing', 'admin_only' => false, 'hide_from_employee' => false],
                'review_management' => ['icon' => 'fas fa-comments', 'text' => 'Quản lý bình luận', 'admin_only' => false, 'hide_from_employee' => false],
                'support_tickets' => ['icon' => 'fas fa-headset', 'text' => 'Hỗ trợ khách hàng', 'admin_only' => false, 'hide_from_employee' => false],
                'coupon' => ['icon' => 'fas fa-ticket-alt', 'text' => 'Mã giảm giá (Coupon)', 'admin_only' => true, 'hide_from_employee' => true],
                'shipping_config' => ['icon' => 'fas fa-shipping-fast', 'text' => 'Cấu hình vận chuyển', 'admin_only' => true, 'hide_from_employee' => true, 'dev' => true],
                'shipping_dashboard' => ['icon' => 'fas fa-truck-loading', 'text' => 'Dashboard vận chuyển', 'admin_only' => true, 'hide_from_employee' => true, 'dev' => true],
            ];

            foreach ($menu_items as $req => $item) {
                $shouldShow = false;

                if ($isAdmin) {
                    $shouldShow = true;
                }

                else if ($username === 'manager1') {

                    $manager1AllowedModules = [
                        'baocaoview',
                        'doanhThuView',
                        'sanPhamBanChayView',
                        'loiNhuanView',
                        'userprofile',
                        'userUpdateProfile',
                        'thongbao'
                    ];
                    $shouldShow = in_array($req, $manager1AllowedModules);
                } else if ($username === 'staff2') {

                    $staff2AllowedModules = [
                        'hanghoaview',
                        'dongiaview',
                        'userprofile',
                        'userUpdateProfile',
                        'thongbao'
                    ];
                    $shouldShow = in_array($req, $staff2AllowedModules);
                } else if ($username === 'lequocanh05') {

                    $lequocanhAllowedModules = [
                        'khachhangview',
                        'adminGiohangView',
                        'lichsumuahang',
                        'userprofile',
                        'userUpdateProfile',
                        'thongbao'
                    ];
                    $shouldShow = in_array($req, $lequocanhAllowedModules);
                }

                else if ($isNhanVien || strpos($username, 'manager') !== false) {

                    if ($item['hide_from_employee']) {
                        $shouldShow = false;
                    } else {

                        try {
                            $shouldShow = $phanQuyen->checkAccess($req, $username);
                        } catch (Exception $e) {
                            error_log("Menu access check error for $req: " . $e->getMessage());
                            $shouldShow = false;
                        }
                    }
                }

                else {

                    $basicUserModules = ['userprofile', 'userUpdateProfile', 'lichsumuahang'];
                    $shouldShow = in_array($req, $basicUserModules);
                }

                if ($shouldShow) {
                    $active_class = ($current_page === $req) ? 'active' : '';
                    $devBadge = isset($item['dev']) && $item['dev'] ? ' <span style="font-size:9px;background:#ff9800;color:#fff;padding:1px 4px;border-radius:3px;margin-left:3px;">DEV</span>' : '';
                    echo "<li><a href='index.php?req=$req' class='$active_class'><i class='{$item['icon']}'></i> {$item['text']}{$devBadge}</a></li>";
                }
            }
            ?>
            <?php

            $shouldShowShoppingPage = false;

            if ($isAdmin && $username === 'admin') {

                $shouldShowShoppingPage = true;
            } else if (!$isNhanVien) {

                $shouldShowShoppingPage = true;
            }

            if ($shouldShowShoppingPage) {
                echo '<li><a href="../index.php"><i class="fas fa-store"></i> Trang mua hàng</a></li>';
            }
            ?>
        </ul>
    </div>
</div>