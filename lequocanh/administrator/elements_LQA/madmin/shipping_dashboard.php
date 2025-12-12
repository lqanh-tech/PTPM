<?php
/**
 * Shipping Dashboard
 * 
 * Dashboard tổng quan về vận chuyển
 * - Thống kê đơn hàng theo trạng thái vận chuyển
 * - Biểu đồ vận chuyển
 * - Đơn hàng cần xử lý
 */

require_once __DIR__ . '/../mod/database.php';

// Check permission - must be called from index.php with proper session
if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    die('Access denied. Please login.');
}

$db = Database::getInstance()->getConnection();

// Get statistics
try {
    // Tổng số đơn hàng
    $stmt = $db->query("SELECT COUNT(*) as total FROM don_hang");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Đơn hàng theo trạng thái vận chuyển
    $stmt = $db->query("
        SELECT 
            shipping_status,
            COUNT(*) as count,
            SUM(phi_van_chuyen) as total_fee
        FROM don_hang 
        WHERE shipping_status IS NOT NULL
        GROUP BY shipping_status
    ");
    $shippingStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đơn hàng cần xử lý (pending)
    $stmt = $db->query("
        SELECT 
            id,
            ma_don_hang,
            ten_khach_hang,
            dia_chi_giao_hang,
            phi_van_chuyen,
            trang_thai,
            shipping_status,
            ngay_dat_hang
        FROM don_hang 
        WHERE shipping_status = 'pending' OR shipping_status IS NULL
        ORDER BY ngay_dat_hang DESC
        LIMIT 10
    ");
    $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Thống kê theo phương thức vận chuyển
    $stmt = $db->query("
        SELECT 
            shipping_method_name,
            COUNT(*) as count,
            SUM(phi_van_chuyen) as total_fee
        FROM don_hang 
        WHERE shipping_method_name IS NOT NULL
        GROUP BY shipping_method_name
    ");
    $methodStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Doanh thu vận chuyển theo tháng
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(ngay_dat_hang, '%Y-%m') as month,
            COUNT(*) as orders,
            SUM(phi_van_chuyen) as revenue
        FROM don_hang 
        WHERE ngay_dat_hang >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(ngay_dat_hang, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Shipping Dashboard Error: " . $e->getMessage());
    $shippingStats = [];
    $pendingOrders = [];
    $methodStats = [];
    $monthlyStats = [];
}

// Status labels
$statusLabels = [
    'pending' => 'Chờ xử lý',
    'picking' => 'Đang lấy hàng',
    'shipping' => 'Đang vận chuyển',
    'delivered' => 'Đã giao',
    'failed' => 'Giao thất bại',
    'returned' => 'Đã hoàn trả'
];

$statusColors = [
    'pending' => '#ffc107',
    'picking' => '#17a2b8',
    'shipping' => '#007bff',
    'delivered' => '#28a745',
    'failed' => '#dc3545',
    'returned' => '#6c757d'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .chart-container {
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
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .btn-action {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-shipping-fast"></i> Dashboard Vận Chuyển</h1>
                    <p class="mb-0">Tổng quan và quản lý vận chuyển đơn hàng</p>
                </div>
                <div>
                    <a href="shipping_config.php" class="btn btn-light">
                        <i class="fas fa-cog"></i> Cấu hình
                    </a>
                    <a href="shipping_report.php" class="btn btn-light">
                        <i class="fas fa-chart-bar"></i> Báo cáo
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-box"></i> Tổng đơn hàng
                    </div>
                    <div class="stat-number text-primary">
                        <?= number_format($totalOrders) ?>
                    </div>
                </div>
            </div>
            
            <?php
            $pendingCount = 0;
            $shippingCount = 0;
            $deliveredCount = 0;
            
            foreach ($shippingStats as $stat) {
                if ($stat['shipping_status'] === 'pending') $pendingCount = $stat['count'];
                if ($stat['shipping_status'] === 'shipping') $shippingCount = $stat['count'];
                if ($stat['shipping_status'] === 'delivered') $deliveredCount = $stat['count'];
            }
            ?>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> Chờ xử lý
                    </div>
                    <div class="stat-number text-warning">
                        <?= number_format($pendingCount) ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-truck"></i> Đang vận chuyển
                    </div>
                    <div class="stat-number text-info">
                        <?= number_format($shippingCount) ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i> Đã giao
                    </div>
                    <div class="stat-number text-success">
                        <?= number_format($deliveredCount) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie"></i> Trạng thái vận chuyển</h5>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar"></i> Phương thức vận chuyển</h5>
                    <canvas id="methodChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue Chart -->
        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-line"></i> Doanh thu vận chuyển 6 tháng gần đây</h5>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pending Orders Table -->
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <h5><i class="fas fa-list"></i> Đơn hàng cần xử lý</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Địa chỉ</th>
                                    <th>Phí vận chuyển</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingOrders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-inbox"></i> Không có đơn hàng cần xử lý
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($pendingOrders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['ma_don_hang']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['ten_khach_hang']) ?></td>
                                    <td><?= htmlspecialchars(substr($order['dia_chi_giao_hang'], 0, 50)) ?>...</td>
                                    <td><?= number_format($order['phi_van_chuyen'], 0, ',', '.') ?>₫</td>
                                    <td>
                                        <span class="status-badge" style="background: <?= $statusColors[$order['shipping_status'] ?? 'pending'] ?>; color: white;">
                                            <?= $statusLabels[$order['shipping_status'] ?? 'pending'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['ngay_dat_hang'])) ?></td>
                                    <td>
                                        <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary btn-action">
                                            <i class="fas fa-eye"></i> Xem
                                        </a>
                                        <button class="btn btn-sm btn-success btn-action" onclick="createShipment(<?= $order['id'] ?>)">
                                            <i class="fas fa-shipping-fast"></i> Tạo vận đơn
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Status Chart
        const statusData = <?= json_encode($shippingStats) ?>;
        const statusLabels = <?= json_encode($statusLabels) ?>;
        const statusColors = <?= json_encode(array_values($statusColors)) ?>;
        
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => statusLabels[s.shipping_status] || s.shipping_status),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: statusData.map(s => statusColors[s.shipping_status] || '#6c757d')
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Method Chart
        const methodData = <?= json_encode($methodStats) ?>;
        
        new Chart(document.getElementById('methodChart'), {
            type: 'bar',
            data: {
                labels: methodData.map(m => m.shipping_method_name || 'Chưa xác định'),
                datasets: [{
                    label: 'Số đơn hàng',
                    data: methodData.map(m => m.count),
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Revenue Chart
        const monthlyData = <?= json_encode($monthlyStats) ?>;
        
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: monthlyData.map(m => m.month),
                datasets: [{
                    label: 'Doanh thu vận chuyển (₫)',
                    data: monthlyData.map(m => m.revenue),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN').format(value) + '₫';
                            }
                        }
                    }
                }
            }
        });

        // Create shipment function
        function createShipment(orderId) {
            if (confirm('Tạo vận đơn GHN cho đơn hàng này?')) {
                // TODO: Implement create shipment
                alert('Chức năng đang được phát triển');
            }
        }
    </script>
</body>
</html>
