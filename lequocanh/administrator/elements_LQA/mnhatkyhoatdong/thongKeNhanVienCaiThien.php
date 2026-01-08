<?php

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/nhatKyHoatDongCls.php';
require_once './elements_LQA/mod/phanHeQuanLyCls.php';
require_once './elements_LQA/mod/nhanvienCls.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$nhatKyObj = new NhatKyHoatDong();
$phanHeObj = new PhanHeQuanLy();
$nhanVienObj = new NhanVien();

$tuNgay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : date('Y-m-d', strtotime('-7 days'));
$denNgay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : date('Y-m-d');
$selectedNhanVien = isset($_GET['nhan_vien']) ? $_GET['nhan_vien'] : '';
$selectedPhanHe = isset($_GET['phan_he']) ? $_GET['phan_he'] : '';

$stmt = $conn->query("
    SELECT nv.idNhanVien, nv.tenNV, u.username 
    FROM nhanvien nv 
    JOIN user u ON nv.iduser = u.iduser 
    WHERE u.username IS NOT NULL
    ORDER BY nv.tenNV
");
$danhSachNhanVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

$danhSachPhanHe = $phanHeObj->getAllPhanHe();

$filters = [
    'tu_ngay' => $tuNgay,
    'den_ngay' => $denNgay
];

if (!empty($selectedNhanVien)) {
    $filters['username'] = $selectedNhanVien;
}

$thongKeNhanVien = [];
foreach ($danhSachNhanVien as $nv) {
    $username = $nv['username'];
    
    $userFilters = [
        'username' => $username,
        'tu_ngay' => $tuNgay,
        'den_ngay' => $denNgay
    ];
    
    $tongHoatDong = $nhatKyObj->demSoLuongNhatKy($userFilters);
    
    $userFilters['hanh_dong'] = 'Đăng nhập';
    $soLanDangNhap = $nhatKyObj->demSoLuongNhatKy($userFilters);
    
    $userFilters['hanh_dong'] = 'Thêm mới';
    $soLanThemMoi = $nhatKyObj->demSoLuongNhatKy($userFilters);
    
    $userFilters['hanh_dong'] = 'Cập nhật';
    $soLanCapNhat = $nhatKyObj->demSoLuongNhatKy($userFilters);
    
    $userFilters['hanh_dong'] = 'Xóa';
    $soLanXoa = $nhatKyObj->demSoLuongNhatKy($userFilters);
    
    $phanHeGan = $phanHeObj->getPhanHeByNhanVienId($nv['idNhanVien']);
    
    $thongKeNhanVien[] = [
        'idNhanVien' => $nv['idNhanVien'],
        'tenNhanVien' => $nv['tenNV'],
        'username' => $username,
        'tongHoatDong' => $tongHoatDong,
        'soLanDangNhap' => $soLanDangNhap,
        'soLanThemMoi' => $soLanThemMoi,
        'soLanCapNhat' => $soLanCapNhat,
        'soLanXoa' => $soLanXoa,
        'phanHeGan' => $phanHeGan
    ];
}

usort($thongKeNhanVien, function($a, $b) {
    return $b['tongHoatDong'] - $a['tongHoatDong'];
});

if (!empty($selectedNhanVien)) {
    $thongKeNhanVien = array_filter($thongKeNhanVien, function($item) use ($selectedNhanVien) {
        return $item['username'] == $selectedNhanVien;
    });
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê hoạt động nhân viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Thống kê hoạt động nhân viên (Cải thiện)
                    </h3>
                </div>
                
                <div class="card-body">
                    <!-- Form lọc -->
                    <form method="GET" class="mb-4">
                        <input type="hidden" name="req" value="thongKeNhanVienCaiThien">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Từ ngày:</label>
                                <input type="date" class="form-control" name="tu_ngay" value="<?php echo $tuNgay; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Đến ngày:</label>
                                <input type="date" class="form-control" name="den_ngay" value="<?php echo $denNgay; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nhân viên:</label>
                                <select class="form-select" name="nhan_vien">
                                    <option value="">Tất cả nhân viên</option>
                                    <?php foreach ($danhSachNhanVien as $nv): ?>
                                        <option value="<?php echo $nv['username']; ?>" <?php echo $selectedNhanVien == $nv['username'] ? 'selected' : ''; ?>>
                                            <?php echo $nv['tenNV']; ?> (<?php echo $nv['username']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Lọc
                                    </button>
                                    <a href="?req=thongKeNhanVienCaiThien" class="btn btn-secondary">
                                        <i class="fas fa-refresh me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Thống kê tổng quan -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>Tổng hoạt động</h4>
                                            <h2><?php echo array_sum(array_column($thongKeNhanVien, 'tongHoatDong')); ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-bar fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>Đăng nhập</h4>
                                            <h2><?php echo array_sum(array_column($thongKeNhanVien, 'soLanDangNhap')); ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-sign-in-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>Thêm mới</h4>
                                            <h2><?php echo array_sum(array_column($thongKeNhanVien, 'soLanThemMoi')); ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-plus-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>Cập nhật</h4>
                                            <h2><?php echo array_sum(array_column($thongKeNhanVien, 'soLanCapNhat')); ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng thống kê chi tiết -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>STT</th>
                                    <th>Nhân viên</th>
                                    <th>Username</th>
                                    <th>Phân hệ quản lý</th>
                                    <th>Tổng HĐ</th>
                                    <th>Đăng nhập</th>
                                    <th>Thêm mới</th>
                                    <th>Cập nhật</th>
                                    <th>Xóa</th>
                                    <th>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($thongKeNhanVien) > 0): ?>
                                    <?php foreach ($thongKeNhanVien as $index => $tk): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo $tk['tenNhanVien']; ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo $tk['username']; ?></code>
                                            </td>
                                            <td>
                                                <?php if (!empty($tk['phanHeGan'])): ?>
                                                    <?php foreach ($tk['phanHeGan'] as $ph): ?>
                                                        <span class="badge bg-secondary me-1"><?php echo $ph->tenPhanHe; ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa gán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary fs-6"><?php echo $tk['tongHoatDong']; ?></span>
                                            </td>
                                            <td><?php echo $tk['soLanDangNhap']; ?></td>
                                            <td><?php echo $tk['soLanThemMoi']; ?></td>
                                            <td><?php echo $tk['soLanCapNhat']; ?></td>
                                            <td><?php echo $tk['soLanXoa']; ?></td>
                                            <td>
                                                <a href="?req=nhatKyHoatDongTichHop&tab=chitiet&username=<?php echo $tk['username']; ?>&tu_ngay=<?php echo $tuNgay; ?>&den_ngay=<?php echo $denNgay; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Không có dữ liệu hoạt động trong khoảng thời gian đã chọn
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Nút hành động -->
                    <div class="mt-4">
                        <a href="?req=nhatKyHoatDongTichHop" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại thống kê cũ
                        </a>
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i>Xuất Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function exportToExcel() {
    alert('Tính năng xuất Excel sẽ được phát triển trong phiên bản tiếp theo!');
}
</script>

</body>
</html>
