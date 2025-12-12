<?php
/**
 * Shipping Report
 * 
 * Báo cáo chi tiết về vận chuyển
 * - Báo cáo theo thời gian
 * - Báo cáo theo phương thức
 * - Báo cáo theo khu vực
 * - Xuất Excel/PDF
 */

require_once __DIR__ . '/../mod/database.php';

// Check permission - must be called from index.php with proper session
if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    die('Access denied. Please login.');
}

$db = Database::getInstance()->getConnection();

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$shippingMethod = $_GET['shipping_method'] ?? '';
$shippingStatus = $_GET['shipping_status'] ?? '';

// Build query
$query = "
    SELECT 
        dh.id,
        dh.ma_don_hang,
        dh.ten_khach_hang,
        dh.dia_chi_giao_hang,
        dh.phi_van_chuyen,
        dh.shipping_method_name,
        dh.shipping_status,
        dh.tracking_code,
        dh.carrier,
        dh.ngay_dat_hang,
        dh.estimated_delivery,
        p.name as province_name,
        d.name as district_name
    FROM don_hang dh
    LEFT JOIN provinces p ON dh.province_id = p.id
    LEFT JOIN districts d ON dh.district_id = d.id
    WHERE dh.ngay_dat_hang BETWEEN ? AND ?
";

$params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

if ($shippingMethod) {
    $query .= " AND dh.shipping_method_name = ?";
    $params[] = $shippingMethod;
}

if ($shippingStatus) {
    $query .= " AND dh.shipping_status = ?";
    $params[] = $shippingStatus;
}

$query .= " ORDER BY dh.ngay_dat_hang DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary
    $totalOrders = count($orders);
    $totalShippingFee = array_sum(array_column($orders, 'phi_van_chuyen'));
    $avgShippingFee = $totalOrders > 0 ? $totalShippingFee / $totalOrders : 0;
    
    // Get shipping methods for filter
    $stmt = $db->query("SELECT DISTINCT shipping_method_name FROM don_hang WHERE shipping_method_name IS NOT NULL");
    $shippingMethods = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    error_log("Shipping Report Error: " . $e->getMessage());
    $orders = [];
    $totalOrders = 0;
    $totalShippingFee = 0;
    $avgShippingFee = 0;
    $shippingMethods = [];
}

$statusLabels = [
    'pending' => 'Chờ xử lý',
    'picking' => 'Đang lấy hàng',
    'shipping' => 'Đang vận chuyển',
    'delivered' => 'Đã giao',
    'failed' => 'Giao thất bại',
    'returned' => 'Đã hoàn trả'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-box {
            text-align: center;
            padding: 15px;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-file-invoice"></i> Báo Cáo Vận Chuyển</h1>
                    <p class="mb-0">Báo cáo chi tiết về vận chuyển đơn hàng</p>
                </div>
                <div>
                    <button class="btn btn-light" onclick="exportExcel()">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                    <button class="btn btn-light" onclick="exportPDF()">
                        <i class="fas fa-file-pdf"></i> Xuất PDF
                    </button>
                    <a href="shipping_dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-card">
            <h5><i class="fas fa-filter"></i> Bộ lọc</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phương thức vận chuyển</label>
                    <select name="shipping_method" class="form-select">
                        <option value="">Tất cả</option>
                        <?php foreach ($shippingMethods as $method): ?>
                        <option value="<?= htmlspecialchars($method) ?>" <?= $shippingMethod === $method ? 'selected' : '' ?>>
                            <?= htmlspecialchars($method) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="shipping_status" class="form-select">
                        <option value="">Tất cả</option>
                        <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $shippingStatus === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary -->
        <div class="row">
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($totalOrders) ?></div>
                        <div class="stat-label">Tổng đơn hàng</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($totalShippingFee, 0, ',', '.') ?>₫</div>
                        <div class="stat-label">Tổng phí vận chuyển</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="stat-box">
                        <div class="stat-number"><?= number_format($avgShippingFee, 0, ',', '.') ?>₫</div>
                        <div class="stat-label">Phí trung bình</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <h5><i class="fas fa-table"></i> Chi tiết đơn hàng</h5>
            <div class="table-responsive">
                <table class="table table-hover" id="reportTable">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Khu vực</th>
                            <th>Phương thức</th>
                            <th>Phí vận chuyển</th>
                            <th>Trạng thái</th>
                            <th>Mã vận đơn</th>
                            <th>Ngày đặt</th>
                            <th>Dự kiến giao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="fas fa-inbox"></i> Không có dữ liệu
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($order['ma_don_hang']) ?></strong></td>
                            <td><?= htmlspecialchars($order['ten_khach_hang']) ?></td>
                            <td>
                                <?= htmlspecialchars($order['district_name'] ?? '') ?>, 
                                <?= htmlspecialchars($order['province_name'] ?? '') ?>
                            </td>
                            <td><?= htmlspecialchars($order['shipping_method_name'] ?? '-') ?></td>
                            <td><?= number_format($order['phi_van_chuyen'], 0, ',', '.') ?>₫</td>
                            <td>
                                <span class="badge bg-<?= $order['shipping_status'] === 'delivered' ? 'success' : ($order['shipping_status'] === 'pending' ? 'warning' : 'info') ?>">
                                    <?= $statusLabels[$order['shipping_status'] ?? 'pending'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($order['tracking_code'] ?? '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($order['ngay_dat_hang'])) ?></td>
                            <td><?= $order['estimated_delivery'] ? date('d/m/Y', strtotime($order['estimated_delivery'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportExcel() {
            const table = document.getElementById('reportTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Báo cáo vận chuyển"});
            XLSX.writeFile(wb, 'bao-cao-van-chuyen-<?= date('Y-m-d') ?>.xlsx');
        }

        function exportPDF() {
            window.print();
        }
    </script>
</body>
</html>
