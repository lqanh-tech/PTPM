<?php

session_start();

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../userLogin.php');
    exit();
}

require_once '../../elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$where = ["1=1"];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "dh.trang_thai = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['payment_method'])) {
    $where[] = "dh.phuong_thuc_thanh_toan = ?";
    $params[] = $_GET['payment_method'];
}

if (!empty($_GET['date_from'])) {
    $where[] = "DATE(dh.ngay_tao) >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where[] = "DATE(dh.ngay_tao) <= ?";
    $params[] = $_GET['date_to'];
}

if (!empty($_GET['search'])) {
    $where[] = "(dh.ma_don_hang_text LIKE ? OR u.ten LIKE ? OR u.dien_thoai LIKE ?)";
    $searchTerm = "%{$_GET['search']}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql = "SELECT 
            dh.id,
            dh.ma_don_hang_text,
            dh.ngay_tao,
            dh.tong_tien,
            dh.trang_thai,
            dh.phuong_thuc_thanh_toan,
            dh.trang_thai_thanh_toan,
            u.ten as ten_khach_hang,
            u.dien_thoai,
            u.email
        FROM don_hang dh
        LEFT JOIN user u ON dh.ma_nguoi_dung = u.username
        WHERE " . implode(" AND ", $where) . "
        ORDER BY dh.ngay_tao DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalOrders = count($orders);
$totalRevenue = array_sum(array_column($orders, 'tong_tien'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Export</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css_LQA/order_export.css">
    
    <style>
        body { background: #f5f7fa; padding: 20px; }
        .page-header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .stats-row { display: flex; gap: 20px; justify-content: space-around; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        .orders-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .orders-table table { width: 100%; margin: 0; }
        .orders-table th { background: #f8f9fa; padding: 15px; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .orders-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
        .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-shopping-cart"></i> Quản lý đơn hàng</h2>
            <p class="text-muted mb-0">Xem, tìm kiếm và xuất đơn hàng</p>
        </div>
        
        <!-- Stats -->
        <div class="stats-card">
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value"><?= $totalOrders ?></div>
                    <div class="stat-label">Tổng đơn hàng</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($totalRevenue) ?> đ</div>
                    <div class="stat-label">Tổng doanh thu</div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Trạng thái</label>
                        <select name="status" id="filter-status">
                            <option value="">Tất cả</option>
                            <option value="Chờ xác nhận" <?= ($_GET['status'] ?? '') == 'Chờ xác nhận' ? 'selected' : '' ?>>Chờ xác nhận</option>
                            <option value="Đã duyệt" <?= ($_GET['status'] ?? '') == 'Đã duyệt' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="Đã hủy" <?= ($_GET['status'] ?? '') == 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Phương thức thanh toán</label>
                        <select name="payment_method" id="filter-payment">
                            <option value="">Tất cả</option>
                            <option value="cod" <?= ($_GET['payment_method'] ?? '') == 'cod' ? 'selected' : '' ?>>COD</option>
                            <option value="momo" <?= ($_GET['payment_method'] ?? '') == 'momo' ? 'selected' : '' ?>>MoMo</option>
                            <option value="bank" <?= ($_GET['payment_method'] ?? '') == 'bank' ? 'selected' : '' ?>>Chuyển khoản</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Từ ngày</label>
                        <input type="date" name="date_from" id="filter-date-from" value="<?= $_GET['date_from'] ?? '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Đến ngày</label>
                        <input type="date" name="date_to" id="filter-date-to" value="<?= $_GET['date_to'] ?? '' ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Tìm kiếm</label>
                        <input type="text" name="search" id="search-input" placeholder="Mã đơn, tên KH, SĐT..." value="<?= $_GET['search'] ?? '' ?>">
                    </div>
                    
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Export Toolbar -->
        <div class="export-toolbar">
            <div class="export-toolbar-left">
                <div class="select-all-container">
                    <input type="checkbox" id="select-all-orders">
                    <label for="select-all-orders">Chọn tất cả</label>
                </div>
                
                <span class="selected-count" id="selected-count" style="display: none;">
                    Đã chọn: <span id="count-number">0</span>
                </span>
            </div>
            
            <div class="export-toolbar-right">
                <!-- Xuất chi tiết các đơn đã chọn -->
                <button class="btn-export btn-export-pdf" id="btn-export-pdf" disabled>
                    <i class="fas fa-file-pdf"></i> Xuất PDF
                </button>
                
                <button class="btn-export btn-export-excel" id="btn-export-excel" disabled>
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
                
                <!-- Xuất tổng hợp theo bộ lọc -->
                <button class="btn-export btn-export-summary" id="btn-export-summary-pdf">
                    <i class="fas fa-file-pdf"></i> Báo cáo PDF
                </button>
                
                <button class="btn-export btn-export-summary" id="btn-export-summary-excel">
                    <i class="fas fa-file-excel"></i> Báo cáo Excel
                </button>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all-orders-table">
                        </th>
                        <th style="width: 60px;">ID</th>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Điện thoại</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>PT Thanh toán</th>
                        <th style="width: 200px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                            <p style="margin-top: 10px; color: #999;">Không có đơn hàng nào</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): 
                            $statusClass = 'status-pending';
                            if ($order['trang_thai'] == 'Đã duyệt') $statusClass = 'status-completed';
                            if ($order['trang_thai'] == 'Đã hủy') $statusClass = 'status-cancelled';
                        ?>
                        <tr>
                            <td class="order-checkbox-cell">
                                <input type="checkbox" class="order-checkbox" value="<?= $order['id'] ?>">
                            </td>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td><code><?= htmlspecialchars($order['ma_don_hang_text']) ?></code></td>
                            <td><?= htmlspecialchars($order['ten_khach_hang']) ?></td>
                            <td><?= htmlspecialchars($order['dien_thoai']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['ngay_tao'])) ?></td>
                            <td><strong><?= number_format($order['tong_tien']) ?> đ</strong></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($order['trang_thai']) ?>
                                </span>
                            </td>
                            <td><?= strtoupper($order['phuong_thuc_thanh_toan']) ?></td>
                            <td class="order-actions">
                                <button class="btn-action btn-action-print" 
                                        onclick="orderExporter.printInvoice(<?= $order['id'] ?>)"
                                        title="In hóa đơn">
                                    <i class="fas fa-print"></i>
                                </button>
                                
                                <button class="btn-action btn-action-export" 
                                        onclick="orderExporter.exportSinglePDF(<?= $order['id'] ?>)"
                                        title="Xuất PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                
                                <button class="btn-action btn-action-export" 
                                        onclick="orderExporter.exportSingleExcel(<?= $order['id'] ?>)"
                                        title="Xuất Excel">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js_LQA/order_export.js"></script>
    
    <script>

        document.getElementById('select-all-orders-table').addEventListener('change', function(e) {
            document.getElementById('select-all-orders').checked = e.target.checked;
            document.getElementById('select-all-orders').dispatchEvent(new Event('change'));
        });
        
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('order-checkbox') || e.target.id === 'select-all-orders') {
                const count = document.querySelectorAll('.order-checkbox:checked').length;
                const countDisplay = document.getElementById('selected-count');
                const countNumber = document.getElementById('count-number');
                
                if (count > 0) {
                    countDisplay.style.display = 'block';
                    countNumber.textContent = count;
                } else {
                    countDisplay.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
