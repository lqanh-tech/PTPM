<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('loiNhuanView', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mbaocao/baocaoCls.php';
$baoCao = new BaoCao();

$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

if (isset($_POST['startDate']) && isset($_POST['endDate'])) {

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['startDate']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['endDate'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
    } else {
        echo "<div class='alert alert-warning'>Định dạng ngày không hợp lệ. Sử dụng định dạng YYYY-MM-DD.</div>";
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
        echo "<div class='alert alert-warning'>Ngày bắt đầu lớn hơn ngày kết thúc. Đã tự động đổi vị trí.</div>";
    }
}

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

$profitInfo = $baoCao->getLoiNhuan($startDate, $endDate);

$productProfits = $baoCao->getLoiNhuanTheoSanPham($startDate, $endDate, $limit);

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

foreach ($productProfits as $index => $product) {
    $chartLabels[] = $product['tenhanghoa'];
    $chartData[] = $product['loi_nhuan'];
    $chartColors[] = $colors[$index % count($colors)];
}
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-chart-pie"></i> Báo cáo lợi nhuận</h2>
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
        <form method="post" action="index.php?req=loiNhuanView">
            <div class="filter-group">
                <label for="startDate">Từ ngày:</label>
                <input type="date" name="startDate" id="startDate" value="<?php echo $startDate; ?>">
            </div>

            <div class="filter-group">
                <label for="endDate">Đến ngày:</label>
                <input type="date" name="endDate" id="endDate" value="<?php echo $endDate; ?>">
            </div>

            <div class="filter-group">
                <label for="limit">Số lượng sản phẩm:</label>
                <select name="limit" id="limit">
                    <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                </select>
            </div>

            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Lọc
            </button>
        </form>
    </div>

    <div class="profit-overview">
        <div class="profit-card revenue">
            <div class="profit-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="profit-info">
                <h3>Tổng doanh thu</h3>
                <p class="profit-value"><?php echo number_format($profitInfo['doanh_thu'], 0, ',', '.'); ?> đ</p>
            </div>
        </div>

        <div class="profit-card cost">
            <div class="profit-icon">
                <i class="fas fa-tags"></i>
            </div>
            <div class="profit-info">
                <h3>Tổng giá vốn</h3>
                <p class="profit-value"><?php echo number_format($profitInfo['gia_von'], 0, ',', '.'); ?> đ</p>
            </div>
        </div>

        <div class="profit-card profit">
            <div class="profit-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="profit-info">
                <h3>Tổng lợi nhuận</h3>
                <p class="profit-value"><?php echo number_format($profitInfo['loi_nhuan'], 0, ',', '.'); ?> đ</p>
            </div>
        </div>

        <div class="profit-card margin">
            <div class="profit-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="profit-info">
                <h3>Tỷ lệ lợi nhuận</h3>
                <p class="profit-value"><?php echo number_format($profitInfo['ti_le_loi_nhuan'], 1, ',', '.'); ?>%</p>
            </div>
        </div>
    </div>

    <div class="profit-charts">
        <div class="chart-container">
            <h3>Phân tích doanh thu và lợi nhuận</h3>
            <div style="height: 400px;">
                <canvas id="revenueVsProfitChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h3>Lợi nhuận theo sản phẩm</h3>
            <div style="height: 400px;">
                <canvas id="productProfitChart"></canvas>
            </div>
        </div>
    </div>

    <div class="report-table">
        <h3>Chi tiết lợi nhuận theo sản phẩm</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng bán</th>
                    <th>Doanh thu</th>
                    <th>Giá vốn</th>
                    <th>Lợi nhuận</th>
                    <th>Tỷ lệ LN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productProfits as $index => $product): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $product['tenhanghoa']; ?></td>
                        <td><?php echo number_format($product['so_luong_ban'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format($product['doanh_thu'], 0, ',', '.'); ?> đ</td>
                        <td><?php echo number_format($product['gia_von'], 0, ',', '.'); ?> đ</td>
                        <td class="<?php echo $product['loi_nhuan'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo number_format($product['loi_nhuan'], 0, ',', '.'); ?> đ
                        </td>
                        <td class="<?php echo $product['ti_le_loi_nhuan'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            <?php echo number_format($product['ti_le_loi_nhuan'], 1, ',', '.'); ?>%
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

    .btn-filter {
        background: #007bff;
        color: white;
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

    .profit-overview {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }

    .profit-card {
        flex: 1;
        min-width: 200px;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        color: white;
    }

    .profit-card.revenue {
        background: linear-gradient(135deg, #4e73df, #224abe);
    }

    .profit-card.cost {
        background: linear-gradient(135deg, #e74a3b, #be2617);
    }

    .profit-card.profit {
        background: linear-gradient(135deg, #1cc88a, #13855c);
    }

    .profit-card.margin {
        background: linear-gradient(135deg, #f6c23e, #dda20a);
    }

    .profit-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .profit-info h3 {
        margin: 0 0 5px 0;
        font-size: 16px;
        opacity: 0.9;
    }

    .profit-value {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
    }

    .profit-charts {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }

    .chart-container {
        flex: 1;
        min-width: 300px;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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

    .profit-positive {
        color: #28a745;
        font-weight: 600;
    }

    .profit-negative {
        color: #dc3545;
        font-weight: 600;
    }

    @media (max-width: 768px) {

        .profit-overview,
        .profit-charts {
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

        const revenueVsProfitCtx = document.getElementById('revenueVsProfitChart').getContext('2d');

        const doanhThu = parseFloat(<?php echo $profitInfo['doanh_thu']; ?>) || 0;
        const giaVon = parseFloat(<?php echo $profitInfo['gia_von']; ?>) || 0;
        const loiNhuan = parseFloat(<?php echo $profitInfo['loi_nhuan']; ?>) || 0;

        const revenueVsProfitChart = new Chart(revenueVsProfitCtx, {
            type: 'bar',
            data: {
                labels: ['Tổng quan'],
                datasets: [{
                        label: 'Doanh thu',
                        data: [doanhThu],
                        backgroundColor: 'rgba(78, 115, 223, 0.7)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Giá vốn',
                        data: [giaVon],
                        backgroundColor: 'rgba(231, 74, 59, 0.7)',
                        borderColor: 'rgba(231, 74, 59, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Lợi nhuận',
                        data: [loiNhuan],
                        backgroundColor: 'rgba(28, 200, 138, 0.7)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' đ';
                            },
                            maxTicksLimit: 5,
                            padding: 10
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        titleMarginBottom: 10,
                        titleFontSize: 14,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                }
            }
        });

        const productProfitCtx = document.getElementById('productProfitChart').getContext('2d');

        const chartData = <?php echo json_encode($chartData); ?>;
        const numericChartData = chartData.map(value => parseFloat(value) || 0);

        const productProfitChart = new Chart(productProfitCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Lợi nhuận',
                    data: numericChartData,
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
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' đ';
                            },
                            maxTicksLimit: 5,
                            padding: 10
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        },
                        title: {
                            display: true,
                            text: 'Lợi nhuận (đồng)'
                        }
                    },
                    y: {
                        ticks: {
                            callback: function(value) {

                                const label = this.getLabelForValue(value);
                                if (label && label.length > 20) {
                                    return label.substr(0, 20) + '...';
                                }
                                return label;
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        titleMarginBottom: 10,
                        titleFontSize: 14,
                        callbacks: {
                            title: function(tooltipItems) {

                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                const value = context.raw;
                                return 'Lợi nhuận: ' + value.toLocaleString('vi-VN') + ' đ';
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
            sheet: "Báo cáo lợi nhuận"
        });
        XLSX.writeFile(wb, 'bao-cao-loi-nhuan.xlsx');
    }
</script>