<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('nhatKyHoatDongTichHop', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mod/nhatKyHoatDongCls.php';
require_once './elements_LQA/mod/userCls.php';
require_once './elements_LQA/mod/nhanvienCls.php';

$nhatKyObj = new NhatKyHoatDong();
$userObj = new user();
$nhanVienObj = new NhanVien();

$nhanVienList = $nhanVienObj->nhanvienGetAll();
$nhanVienUsernames = [];

foreach ($nhanVienList as $nhanVien) {
    if (isset($nhanVien->username_user) && !empty($nhanVien->username_user)) {
        $nhanVienUsernames[] = $nhanVien->username_user;
    }
}

$adminInfo = $userObj->UserGetbyUsername('admin');

$adminNhanVien = new stdClass();
$adminNhanVien->username_user = 'admin';
$adminNhanVien->tenNV = isset($adminInfo->hoten) ? $adminInfo->hoten : 'Quản trị viên';
$adminNhanVien->idNhanVien = 0;
$nhanVienList[] = $adminNhanVien;

$users = $userObj->UserGetAll();
$nhanVienUsers = [];
foreach ($users as $user) {
    if (in_array($user->username, $nhanVienUsernames)) {
        $nhanVienUsers[] = $user;
    }
}

$adminUser = new stdClass();
$adminUser->username = 'admin';
$adminUser->hoten = isset($adminInfo->hoten) ? $adminInfo->hoten : 'Quản trị viên';
$nhanVienUsers[] = $adminUser;

$filters = [];
$tuNgay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : date('Y-m-d', strtotime('-30 days'));
$denNgay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : date('Y-m-d');
$selectedUsername = isset($_GET['username']) ? $_GET['username'] : '';
$selectedHanhDong = isset($_GET['hanh_dong']) ? $_GET['hanh_dong'] : '';
$selectedDoiTuong = isset($_GET['doi_tuong']) ? $_GET['doi_tuong'] : '';

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'thongke';

$filters['tu_ngay'] = $tuNgay;
$filters['den_ngay'] = $denNgay;

if (!empty($selectedUsername)) {
    $filters['username'] = $selectedUsername;
}

if (!empty($selectedHanhDong)) {
    $filters['hanh_dong'] = $selectedHanhDong;
}

if (!empty($selectedDoiTuong)) {
    $filters['doi_tuong'] = $selectedDoiTuong;
}

$thongKeNhanVien = [];

foreach ($nhanVienList as $nhanVien) {
    if (isset($nhanVien->username_user) && !empty($nhanVien->username_user)) {
        $username = $nhanVien->username_user;

        $userFilters = [
            'username' => $username,
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay
        ];

        $tongHoatDong = $nhatKyObj->demTongSoNhatKy($userFilters);

        $loginFilters = $userFilters;
        $loginFilters['hanh_dong'] = 'đăng nhập';
        $soLanDangNhap = $nhatKyObj->demTongSoNhatKy($loginFilters);

        $addFilters = $userFilters;
        $addFilters['hanh_dong'] = 'thêm mới';
        $soLanThemMoi = $nhatKyObj->demTongSoNhatKy($addFilters);

        $updateFilters = $userFilters;
        $updateFilters['hanh_dong'] = 'cập nhật';
        $soLanCapNhat = $nhatKyObj->demTongSoNhatKy($updateFilters);

        $deleteFilters = $userFilters;
        $deleteFilters['hanh_dong'] = 'xóa';
        $soLanXoa = $nhatKyObj->demTongSoNhatKy($deleteFilters);

        $tenNhanVien = "Không xác định";
        if (isset($nhanVien->tenNV)) {
            $tenNhanVien = $nhanVien->tenNV;
        } elseif (isset($nhanVien->ten_user)) {
            $tenNhanVien = $nhanVien->ten_user;
        } elseif (isset($nhanVien->tenNhanVien)) {
            $tenNhanVien = $nhanVien->tenNhanVien;
        } elseif (isset($nhanVien->hoten)) {
            $tenNhanVien = $nhanVien->hoten;
        }

        $idNhanVien = 0;
        if (isset($nhanVien->idNhanVien)) {
            $idNhanVien = $nhanVien->idNhanVien;
        }

        $thongKeNhanVien[] = [
            'idNhanVien' => $idNhanVien,
            'tenNhanVien' => $tenNhanVien,
            'username' => $nhanVien->username_user,
            'tongHoatDong' => $tongHoatDong,
            'soLanDangNhap' => $soLanDangNhap,
            'soLanThemMoi' => $soLanThemMoi,
            'soLanCapNhat' => $soLanCapNhat,
            'soLanXoa' => $soLanXoa
        ];
    }
}

usort($thongKeNhanVien, function ($a, $b) {
    return $b['tongHoatDong'] - $a['tongHoatDong'];
});

$thongKeNgay = [];
$coDataThongKe = false;
$startDate = new DateTime($tuNgay);
$endDate = new DateTime($denNgay);
$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

foreach ($dateRange as $date) {
    $ngay = $date->format('Y-m-d');
    $dayFilters = [
        'tu_ngay' => $ngay,
        'den_ngay' => $ngay
    ];

    $tongHoatDong = $nhatKyObj->demTongSoNhatKy($dayFilters);

    $dayFilters['hanh_dong'] = 'đăng nhập';
    $soLanDangNhap = $nhatKyObj->demTongSoNhatKy($dayFilters);

    $dayFilters['hanh_dong'] = 'thêm mới';
    $soLanThemMoi = $nhatKyObj->demTongSoNhatKy($dayFilters);

    $dayFilters['hanh_dong'] = 'cập nhật';
    $soLanCapNhat = $nhatKyObj->demTongSoNhatKy($dayFilters);

    $dayFilters['hanh_dong'] = 'xóa';
    $soLanXoa = $nhatKyObj->demTongSoNhatKy($dayFilters);

    if ($tongHoatDong > 0 || $soLanDangNhap > 0 || $soLanThemMoi > 0 || $soLanCapNhat > 0 || $soLanXoa > 0) {
        $coDataThongKe = true;
    }

    $thongKeNgay[] = [
        'ngay' => $ngay,
        'ngayHienThi' => date('d/m/Y', strtotime($ngay)),
        'tongHoatDong' => $tongHoatDong,
        'soLanDangNhap' => $soLanDangNhap,
        'soLanThemMoi' => $soLanThemMoi,
        'soLanCapNhat' => $soLanCapNhat,
        'soLanXoa' => $soLanXoa
    ];
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

if (empty($filters['username'])) {

}

$nhatKyList = $nhatKyObj->layDanhSachNhatKy($filters, $limit, $offset);
$totalRecords = $nhatKyObj->demTongSoNhatKy($filters);
$totalPages = ceil($totalRecords / $limit);

$danhSachHanhDong = [
    'đăng nhập' => 'Đăng nhập',
    'đăng xuất' => 'Đăng xuất',
    'thêm mới' => 'Thêm mới',
    'cập nhật' => 'Cập nhật',
    'xóa' => 'Xóa',
    'xem' => 'Xem',
    'duyệt' => 'Duyệt',
    'hủy' => 'Hủy',
    'khác' => 'Khác'
];

$danhSachDoiTuong = [
    'người dùng' => 'Người dùng',
    'nhân viên' => 'Nhân viên',
    'hàng hóa' => 'Hàng hóa',
    'loại hàng' => 'Loại hàng',
    'đơn hàng' => 'Đơn hàng',
    'phiếu nhập' => 'Phiếu nhập',
    'tồn kho' => 'Tồn kho',
    'khác' => 'Khác'
];
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-chart-line"></i> Quản lý hoạt động nhân viên</h2>
        <p class="text-muted">Thống kê và nhật ký hoạt động của nhân viên trong hệ thống</p>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentTab == 'thongke' ? 'active' : ''; ?>"
                    href="?req=nhatKyHoatDongTichHop&tab=thongke&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Thống kê tổng quan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentTab == 'chitiet' ? 'active' : ''; ?>"
                    href="?req=nhatKyHoatDongTichHop&tab=chitiet&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                    <i class="fas fa-list"></i> Nhật ký chi tiết
                </a>
            </li>
        </ul>
    </div>

    <!-- Form lọc dữ liệu chung -->
    <div class="filter-section">
        <form method="get" action="" class="filter-form">
            <input type="hidden" name="req" value="nhatKyHoatDongTichHop">
            <input type="hidden" name="tab" value="<?php echo $currentTab; ?>">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="username">Người dùng:</label>
                        <select name="username" id="username" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($nhanVienUsers as $user): ?>
                                <option value="<?php echo $user->username; ?>" <?php echo ($selectedUsername == $user->username) ? 'selected' : ''; ?>>
                                    <?php echo $user->username . ' (' . $user->hoten . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="hanh_dong">Hành động:</label>
                        <select name="hanh_dong" id="hanh_dong" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($danhSachHanhDong as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($selectedHanhDong == $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="tu_ngay">Từ ngày:</label>
                        <input type="date" name="tu_ngay" id="tu_ngay" class="form-control" value="<?php echo $tuNgay; ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="den_ngay">Đến ngày:</label>
                        <input type="date" name="den_ngay" id="den_ngay" class="form-control" value="<?php echo $denNgay; ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Lọc</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <?php if ($currentTab == 'thongke'): ?>
            <!-- TAB THỐNG KÊ -->
            <div class="tab-pane active">
                <!-- Thống kê tổng quan -->
                <div class="dashboard-cards">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="dashboard-card primary">
                                <div class="card-content">
                                    <div class="card-info">
                                        <h4>Tổng số hoạt động</h4>
                                        <h2><?php echo array_sum(array_column($thongKeNhanVien, 'tongHoatDong')); ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="dashboard-card success">
                                <div class="card-content">
                                    <div class="card-info">
                                        <h4>Tổng số đăng nhập</h4>
                                        <h2><?php echo array_sum(array_column($thongKeNhanVien, 'soLanDangNhap')); ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="dashboard-card info">
                                <div class="card-content">
                                    <div class="card-info">
                                        <h4>Tổng số thêm mới</h4>
                                        <h2><?php echo array_sum(array_column($thongKeNhanVien, 'soLanThemMoi')); ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="dashboard-card warning">
                                <div class="card-content">
                                    <div class="card-info">
                                        <h4>Tổng số cập nhật</h4>
                                        <h2><?php echo array_sum(array_column($thongKeNhanVien, 'soLanCapNhat')); ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ hoạt động theo ngày -->
                <?php if ($coDataThongKe): ?>
                    <div class="chart-container">
                        <h3>Biểu đồ hoạt động theo ngày</h3>
                        <canvas id="activityChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="no-data-container">
                        <div class="no-data-content">
                            <i class="fas fa-chart-line fa-3x"></i>
                            <h3>Không có dữ liệu hoạt động</h3>
                            <p>Chưa có hoạt động nào được ghi nhận trong khoảng thời gian từ <strong><?php echo date('d/m/Y', strtotime($tuNgay)); ?></strong> đến <strong><?php echo date('d/m/Y', strtotime($denNgay)); ?></strong></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bảng thống kê theo nhân viên -->
                <div class="table-responsive">
                    <h3>Thống kê hoạt động theo nhân viên</h3>
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên nhân viên</th>
                                <th>Username</th>
                                <th>Tổng hoạt động</th>
                                <th>Đăng nhập</th>
                                <th>Thêm mới</th>
                                <th>Cập nhật</th>
                                <th>Xóa</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($thongKeNhanVien) > 0): ?>
                                <?php foreach ($thongKeNhanVien as $index => $thongKe): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $thongKe['tenNhanVien']; ?></td>
                                        <td><?php echo $thongKe['username']; ?></td>
                                        <td><?php echo $thongKe['tongHoatDong']; ?></td>
                                        <td><?php echo $thongKe['soLanDangNhap']; ?></td>
                                        <td><?php echo $thongKe['soLanThemMoi']; ?></td>
                                        <td><?php echo $thongKe['soLanCapNhat']; ?></td>
                                        <td><?php echo $thongKe['soLanXoa']; ?></td>
                                        <td>
                                            <a href="?req=nhatKyHoatDongTichHop&tab=chitiet&username=<?php echo $thongKe['username']; ?>&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center no-data-row">
                                        <div class="no-data-message">
                                            <i class="fas fa-users fa-2x"></i>
                                            <h4>Không có dữ liệu hoạt động nhân viên</h4>
                                            <p>Chưa có nhân viên nào thực hiện hoạt động trong khoảng thời gian đã chọn</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- TAB NHẬT KÝ CHI TIẾT -->
            <div class="tab-pane active">
                <!-- Hiển thị kết quả -->
                <div class="table-responsive">
                    <h3>Nhật ký hoạt động chi tiết</h3>
                    <p class="text-muted">Hiển thị <?php echo count($nhatKyList); ?> trong tổng số <?php echo $totalRecords; ?> bản ghi</p>

                    <table class="content-table">
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th width="120">Người dùng</th>
                                <th width="100">Hành động</th>
                                <th width="100">Đối tượng</th>
                                <th>Chi tiết</th>
                                <th width="120">IP</th>
                                <th width="140">Thời gian</th>
                                <th width="80">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($nhatKyList) > 0): ?>
                                <?php foreach ($nhatKyList as $nhatKy): ?>
                                    <tr>
                                        <td><?php echo $nhatKy['id']; ?></td>
                                        <td>
                                            <?php
                                            $displayName = $nhatKy['username'];

                                            if (isset($nhatKy['ten_nhan_vien']) && !empty($nhatKy['ten_nhan_vien'])) {
                                                $displayName .= ' (' . $nhatKy['ten_nhan_vien'] . ')';
                                            } elseif (isset($nhatKy['tenNhanVien']) && !empty($nhatKy['tenNhanVien'])) {
                                                $displayName .= ' (' . $nhatKy['tenNhanVien'] . ')';
                                            } elseif (isset($nhatKy['hoten']) && !empty($nhatKy['hoten'])) {
                                                $displayName .= ' (' . $nhatKy['hoten'] . ')';
                                            }
                                            echo $displayName;
                                            ?>
                                        </td>
                                        <td><span class="badge badge-action"><?php echo htmlspecialchars($nhatKy['hanh_dong']); ?></span></td>
                                        <td><span class="badge badge-object"><?php echo htmlspecialchars($nhatKy['doi_tuong']); ?></span></td>
                                        <td class="detail-cell"><?php

                                                                $chiTiet = '';
                                                                if (!empty($nhatKy['chi_tiet'])) {
                                                                    $chiTiet = $nhatKy['chi_tiet'];
                                                                } elseif (!empty($nhatKy['noi_dung'])) {
                                                                    $chiTiet = $nhatKy['noi_dung'];
                                                                }

                                                                if (!empty($chiTiet)) {
                                                                    $shortDetail = mb_strlen($chiTiet) > 60 ? mb_substr($chiTiet, 0, 60) . '...' : $chiTiet;
                                                                    echo '<span title="' . htmlspecialchars($chiTiet) . '">' . htmlspecialchars($shortDetail) . '</span>';
                                                                } else {
                                                                    echo '<em class="text-muted">Không có chi tiết</em>';
                                                                }
                                                                ?></td>
                                        <td><code class="ip-code"><?php echo htmlspecialchars($nhatKy['ip_address']); ?></code></td>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($nhatKy['thoi_gian'])); ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info" onclick="viewActivityDetail(<?php echo $nhatKy['id']; ?>)" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="no-data-message">
                                            <i class="fas fa-history fa-2x"></i>
                                            <h4>Không có dữ liệu nhật ký hoạt động</h4>
                                            <p>Hệ thống sẽ tự động ghi nhật ký khi người dùng thực hiện các hoạt động như đăng nhập, đăng xuất, thêm, sửa, xóa...</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?req=nhatKyHoatDongTichHop&tab=chitiet&page=1&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?req=nhatKyHoatDongTichHop&tab=chitiet&page=<?php echo $page - 1; ?>&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?req=nhatKyHoatDongTichHop&tab=chitiet&page=<?php echo $i; ?>&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?req=nhatKyHoatDongTichHop&tab=chitiet&page=<?php echo $page + 1; ?>&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?req=nhatKyHoatDongTichHop&tab=chitiet&page=<?php echo $totalPages; ?>&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?><?php echo !empty($selectedUsername) ? '&username=' . $selectedUsername : ''; ?><?php echo !empty($selectedHanhDong) ? '&hanh_dong=' . $selectedHanhDong : ''; ?><?php echo !empty($selectedDoiTuong) ? '&doi_tuong=' . $selectedDoiTuong : ''; ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal xem chi tiết nhật ký -->
<div id="activityDetailModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Chi tiết nhật ký hoạt động</h3>
            <span class="close" onclick="closeActivityDetailModal()">&times;</span>
        </div>
        <div class="modal-body" id="activityDetailContent">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Đang tải thông tin...
            </div>
        </div>
    </div>
</div>

<style>

    .tab-navigation {
        margin-bottom: 20px;
    }

    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        margin-bottom: -2px;
    }

    .nav-link {
        display: block;
        padding: 12px 20px;
        text-decoration: none;
        color: #495057;
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        border-bottom: none;
        border-radius: 5px 5px 0 0;
        margin-right: 5px;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        background-color: #e9ecef;
        color: #007bff;
    }

    .nav-link.active {
        background-color: white;
        color: #007bff;
        border-color: #007bff;
        border-bottom: 2px solid white;
        font-weight: bold;
    }

    .nav-link i {
        margin-right: 8px;
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }

    .dashboard-cards {
        margin-bottom: 30px;
    }

    .dashboard-card {
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-2px);
    }

    .dashboard-card.primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
    }

    .dashboard-card.success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        color: white;
    }

    .dashboard-card.info {
        background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
        color: white;
    }

    .dashboard-card.warning {
        background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        color: white;
    }

    .card-content {
        display: flex;
        padding: 25px;
        justify-content: space-between;
        align-items: center;
    }

    .card-info h4 {
        margin: 0;
        font-size: 14px;
        opacity: 0.9;
        font-weight: 500;
    }

    .card-info h2 {
        margin: 10px 0 0;
        font-size: 28px;
        font-weight: bold;
    }

    .card-icon {
        font-size: 35px;
        opacity: 0.8;
    }

    .chart-container {
        background-color: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        border: 1px solid #dee2e6;
    }

    .chart-container h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }

    canvas {
        width: 100% !important;
        height: 350px !important;
    }

    .no-data-container {
        background-color: white;
        padding: 50px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        text-align: center;
        border: 1px solid #dee2e6;
    }

    .no-data-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .no-data-content i {
        color: #6c757d;
        margin-bottom: 20px;
    }

    .no-data-content h3 {
        color: #495057;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .no-data-content p {
        color: #6c757d;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .table-responsive {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 30px;
        border: 1px solid #dee2e6;
    }

    .table-responsive h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
        font-weight: 600;
    }

    .content-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .content-table th {
        background-color: #f8f9fa;
        padding: 12px 8px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }

    .content-table td {
        padding: 12px 8px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .content-table tr:hover {
        background-color: #f8f9fa;
    }

    .no-data-row {
        padding: 40px 20px !important;
    }

    .no-data-message {
        text-align: center;
        color: #6c757d;
    }

    .no-data-message i {
        margin-bottom: 15px;
        color: #adb5bd;
    }

    .no-data-message h4 {
        color: #495057;
        margin-bottom: 10px;
        font-size: 18px;
        font-weight: 600;
    }

    .no-data-message p {
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 25px;
    }

    .pagination {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .page-item {
        margin: 0 3px;
    }

    .page-link {
        display: block;
        padding: 8px 12px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #007bff;
        text-decoration: none;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .page-link:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
    }

    .btn {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }

    .btn-info {
        background-color: #17a2b8;
        color: white;
        border: 1px solid #17a2b8;
    }

    .btn-info:hover {
        background-color: #138496;
        border-color: #117a8b;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
        border: 1px solid #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #495057;
    }

    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease;
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 8px;
        width: 80%;
        max-width: 800px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        animation: slideIn 0.3s ease;
    }

    .modal-header {
        background-color: #007bff;
        color: white;
        padding: 15px 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .close {
        color: white;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .close:hover {
        color: #ccc;
    }

    .modal-body {
        padding: 20px;
        max-height: 500px;
        overflow-y: auto;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }

    .detail-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .detail-table th,
    .detail-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    .detail-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
        width: 30%;
    }

    .detail-table td {
        color: #333;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .nav-tabs {
            flex-direction: column;
        }

        .nav-link {
            margin-right: 0;
            margin-bottom: 5px;
        }

        .dashboard-cards .col-md-3 {
            margin-bottom: 15px;
        }

        .card-content {
            padding: 20px;
        }

        .card-info h2 {
            font-size: 24px;
        }

        .card-icon {
            font-size: 30px;
        }

        .modal-content {
            width: 95%;
            margin: 10% auto;
        }

        .detail-table th {
            width: 40%;
        }
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        white-space: nowrap;
    }

    .badge-action {
        background-color: #007bff;
        color: white;
    }

    .badge-object {
        background-color: #28a745;
        color: white;
    }

    .detail-cell {
        max-width: 300px;
        word-wrap: break-word;
        line-height: 1.4;
    }

    .detail-cell span {
        cursor: help;
    }

    .ip-code {
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
        color: #e83e8c;
        font-size: 11px;
        border: 1px solid #dee2e6;
    }

    .text-muted {
        color: #6c757d !important;
        font-style: italic;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        <?php if ($coDataThongKe && $currentTab == 'thongke'): ?>

            var thongKeNgay = <?php echo json_encode($thongKeNgay); ?>;

            var labels = thongKeNgay.map(function(item) {
                return item.ngayHienThi;
            });

            var tongHoatDong = thongKeNgay.map(function(item) {
                return item.tongHoatDong;
            });

            var soLanDangNhap = thongKeNgay.map(function(item) {
                return item.soLanDangNhap;
            });

            var soLanThemMoi = thongKeNgay.map(function(item) {
                return item.soLanThemMoi;
            });

            var soLanCapNhat = thongKeNgay.map(function(item) {
                return item.soLanCapNhat;
            });

            var soLanXoa = thongKeNgay.map(function(item) {
                return item.soLanXoa;
            });

            var ctx = document.getElementById('activityChart').getContext('2d');
            var activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Tổng hoạt động',
                            data: tongHoatDong,
                            backgroundColor: 'rgba(78, 115, 223, 0.2)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Đăng nhập',
                            data: soLanDangNhap,
                            backgroundColor: 'rgba(28, 200, 138, 0.2)',
                            borderColor: 'rgba(28, 200, 138, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: 'Thêm mới',
                            data: soLanThemMoi,
                            backgroundColor: 'rgba(54, 185, 204, 0.2)',
                            borderColor: 'rgba(54, 185, 204, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: 'Cập nhật',
                            data: soLanCapNhat,
                            backgroundColor: 'rgba(246, 194, 62, 0.2)',
                            borderColor: 'rgba(246, 194, 62, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: 'Xóa',
                            data: soLanXoa,
                            backgroundColor: 'rgba(231, 74, 59, 0.2)',
                            borderColor: 'rgba(231, 74, 59, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Ngày'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Số lượng'
                            },
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        <?php else: ?>
            console.log('Không có dữ liệu để hiển thị biểu đồ hoặc không ở tab thống kê');
        <?php endif; ?>
    });

    function viewActivityDetail(activityId) {
        const modal = document.getElementById('activityDetailModal');
        const content = document.getElementById('activityDetailContent');

        modal.style.display = 'block';

        content.innerHTML = `
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Đang tải thông tin chi tiết...
            </div>
        `;

        fetch('elements_LQA/mnhatkyhoatdong/getActivityDetail.php?id=' + activityId)
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Lỗi khi tải thông tin: ${error.message}
                    </div>
                `;
            });
    }

    function closeActivityDetailModal() {
        const modal = document.getElementById('activityDetailModal');
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('activityDetailModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>