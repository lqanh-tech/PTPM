<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('sanPhamBanChayView', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mbaocao/baocaoCls.php';
$baoCao = new BaoCao();

$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));
$limit = 10;
$isAllTime = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

    if (isset($_POST['filter_all_time'])) {
        $isAllTime = true;
        $startDate = null;
        $endDate = null;
    } elseif (isset($_POST['startDate']) && isset($_POST['endDate']) && !empty($_POST['startDate']) && !empty($_POST['endDate'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];

        if (strtotime($startDate) > strtotime($endDate)) {
            list($startDate, $endDate) = [$endDate, $startDate];
        }
    }
}

$bestSellingProducts = $baoCao->getSanPhamBanChay($startDate, $endDate, $limit);

$chartLabels = [];
$chartData = [];
$chartColors = [];

$colors = [
    'rgba(255, 99, 132, 0.7)',
    'rgba(54, 162, 235, 0.7)',
    'rgba(255, 206, 86, 0.7)',
    'rgba(75, 192, 192, 0.7)',
    'rgba(153, 102, 255, 0.7)',
    'rgba(255, 159, 64, 0.7)',
    'rgba(199, 199, 199, 0.7)',
    'rgba(83, 102, 255, 0.7)',
    'rgba(40, 159, 64, 0.7)',
    'rgba(210, 105, 30, 0.7)'
];

foreach ($bestSellingProducts as $index => $product) {
    $chartLabels[] = $product['tenhanghoa'];
    $chartData[] = $product['so_luong_ban'];
    $chartColors[] = $colors[$index % count($colors)];
}
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-chart-bar"></i> Thống kê sản phẩm bán chạy</h2>
        <div class="header-actions">
            <button class="btn-print" onclick="printReport()">
                <i class="fas fa-print"></i> In báo cáo
            </button>
            <button class="btn-export" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
        </div>
    </div>

    <div class="report-filters">
        <form method="post" action="index.php?req=sanPhamBanChayView">
            <div class="filter-group">
                <label for="startDate">Từ ngày:</label>
                <input type="date" name="startDate" id="startDate" value="<?php echo $isAllTime ? '' : $startDate; ?>"
                    <?php echo $isAllTime ? 'disabled' : ''; ?>>
            </div>

            <div class="filter-group">
                <label for="endDate">Đến ngày:</label>
                <input type="date" name="endDate" id="endDate" value="<?php echo $isAllTime ? '' : $endDate; ?>"
                    <?php echo $isAllTime ? 'disabled' : ''; ?>>
            </div>

            <div class="filter-group">
                <label for="limit">Số lượng sản phẩm:</label>
                <select name="limit" id="limit">
                    <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="0" <?php echo $limit == 0 ? 'selected' : ''; ?>>Tất cả</option>
                </select>
            </div>

            <button type="submit" class="btn-filter" name="filter_date_range">
                <i class="fas fa-filter"></i> Lọc theo ngày
            </button>
        </form>
    </div>

    <div class="report-summary">
        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="summary-info">
                <h3>Tổng số lượng bán</h3>
                <p class="summary-value">
                    <?php
                    $totalQuantity = 0;
                    foreach ($bestSellingProducts as $product) {
                        $totalQuantity += $product['so_luong_ban'];
                    }
                    echo number_format($totalQuantity, 0, ',', '.');
                    ?>
                </p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="summary-info">
                <h3>Tổng doanh thu</h3>
                <p class="summary-value">
                    <?php
                    $totalRevenue = 0;
                    foreach ($bestSellingProducts as $product) {
                        $totalRevenue += $product['doanh_thu'];
                    }
                    echo number_format($totalRevenue, 0, ',', '.') . ' đ';
                    ?>
                </p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="summary-info">
                <h3>Tổng đơn hàng</h3>
                <p class="summary-value">
                    <?php
                    $totalOrders = 0;
                    foreach ($bestSellingProducts as $product) {
                        $totalOrders += $product['so_don_hang'];
                    }
                    echo number_format($totalOrders, 0, ',', '.');
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="report-charts">
        <div class="chart-container">
            <h3>Số lượng bán theo sản phẩm</h3>
            <div style="height: 400px;">
                <canvas id="quantityChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h3>Doanh thu theo sản phẩm</h3>
            <div style="height: 400px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="report-table">
        <h3>Chi tiết sản phẩm bán chạy</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng bán</th>
                    <th>Doanh thu</th>
                    <th>Số đơn hàng</th>
                    <th>Trung bình/đơn</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bestSellingProducts as $index => $product): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $product['tenhanghoa']; ?></td>
                        <td><?php echo number_format($product['so_luong_ban'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format($product['doanh_thu'], 0, ',', '.'); ?> đ</td>
                        <td><?php echo $product['so_don_hang']; ?></td>
                        <td>
                            <?php
                            $avgPerOrder = $product['so_don_hang'] > 0 ? $product['so_luong_ban'] / $product['so_don_hang'] : 0;
                            echo number_format($avgPerOrder, 1, ',', '.');
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .admin-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .btn-print,
    .btn-export,
    .btn-filter {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: 500;
    }

    .btn-print {
        background: #6c757d;
        color: white;
    }

    .btn-export {
        background: #28a745;
        color: white;
    }

    .btn-filter,
    .btn-filter-all {
        background: #007bff;
        color: white;
    }

    .btn-filter-all {
        background: #17a2b8;
    }

    .report-filters {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .report-filters form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-weight: 500;
        font-size: 14px;
    }

    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 150px;
    }

    .report-summary {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .summary-card {
        flex: 1;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .summary-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #28a745;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .summary-info h3 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #555;
    }

    .summary-value {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        color: #333;
    }

    .report-charts {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .chart-container {
        flex: 1;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        min-width: 300px;
    }

    .chart-container h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 16px;
        color: #555;
        text-align: center;
    }

    .report-table {
        margin-top: 30px;
    }

    .report-table h3 {
        margin-bottom: 15px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .table tr:hover {
        background: #f8f9fa;
    }

    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }

    .no-image {
        width: 50px;
        height: 50px;
        background: #f1f1f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #999;
        border-radius: 4px;
    }

    @media (max-width: 768px) {

        .report-summary,
        .report-charts {
            flex-direction: column;
        }

        .report-filters form {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const quantityCtx = document.getElementById('quantityChart').getContext('2d');
        const quantityChart = new Chart(quantityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Số lượng bán',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: <?php echo json_encode($chartColors); ?>,
                    borderColor: <?php echo json_encode(array_map(function ($color) {
                                        return str_replace('0.7', '1', $color);
                                    }, $chartColors)); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Số lượng bán'
                        }
                    },
                    y: {
                        ticks: {
                            callback: function(value) {

                                const label = this.getLabelForValue(value);
                                if (label.length > 20) {
                                    return label.substr(0, 20) + '...';
                                }
                                return label;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {

                                return tooltipItems[0].label;
                            }
                        }
                    }
                }
            }
        });

        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php
                            $revenueData = [];
                            foreach ($bestSellingProducts as $product) {
                                $revenueData[] = floatval($product['doanh_thu']);
                            }
                            echo json_encode($revenueData);
                            ?>;

        const revenueChart = new Chart(revenueCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Doanh thu',
                    data: revenueData,
                    backgroundColor: <?php echo json_encode($chartColors); ?>,
                    borderColor: <?php echo json_encode(array_map(function ($color) {
                                        return str_replace('0.7', '1', $color);
                                    }, $chartColors)); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value.toLocaleString('vi-VN')} đ (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });

    function printReport() {
        window.print();
    }

    function exportToExcel() {
        const table = document.querySelector('.table');
        const wb = XLSX.utils.table_to_book(table, {
            sheet: "Sản phẩm bán chạy"
        });
        XLSX.writeFile(wb, 'san-pham-ban-chay.xlsx');
    }
</script>