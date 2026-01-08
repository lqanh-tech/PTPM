<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('doanhThuView', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mbaocao/baocaoCls.php';
$baoCao = new BaoCao();

$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

if (isset($_POST['startDate']) && isset($_POST['endDate'])) {

    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    if (class_exists('Logger')) {
        Logger::debug("Revenue report form values", [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
        echo "<div class='alert alert-warning'>Ngày bắt đầu lớn hơn ngày kết thúc. Đã tự động đổi vị trí.</div>";
    }
}

$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : 'daily';

$reportData = [];
$chartLabels = [];
$chartData = [];

switch ($reportType) {
    case 'daily':

        $reportData = $baoCao->getDoanhThuTheoNgayTrongKhoang($startDate, $endDate);

        if (empty($reportData)) {
            $period = new DatePeriod(
                new DateTime($startDate),
                new DateInterval('P1D'),
                (new DateTime($endDate))->modify('+1 day')
            );

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $reportData[] = [
                    'ngay' => $dateStr,
                    'doanh_thu' => 0,
                    'so_don_hang' => 0
                ];
            }
        }

        foreach ($reportData as $item) {
            $chartLabels[] = date('d/m/Y', strtotime($item['ngay']));
            $chartData[] = floatval($item['doanh_thu']);
        }
        break;

    case 'monthly':
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

        $reportData = $baoCao->getDoanhThuTheoThangTrongNam($year);

        if (empty($reportData)) {
            for ($month = 1; $month <= 12; $month++) {
                $reportData[] = [
                    'thang' => $month,
                    'doanh_thu' => 0,
                    'so_don_hang' => 0
                ];
            }
        }

        $monthNames = [
            1 => 'Tháng 1',
            2 => 'Tháng 2',
            3 => 'Tháng 3',
            4 => 'Tháng 4',
            5 => 'Tháng 5',
            6 => 'Tháng 6',
            7 => 'Tháng 7',
            8 => 'Tháng 8',
            9 => 'Tháng 9',
            10 => 'Tháng 10',
            11 => 'Tháng 11',
            12 => 'Tháng 12'
        ];

        $monthData = [];
        foreach ($reportData as $item) {
            $monthData[$item['thang']] = $item;
        }

        $sortedData = [];
        for ($month = 1; $month <= 12; $month++) {
            if (isset($monthData[$month])) {
                $sortedData[] = $monthData[$month];
            } else {
                $sortedData[] = [
                    'thang' => $month,
                    'doanh_thu' => 0,
                    'so_don_hang' => 0
                ];
            }

            $chartLabels[] = $monthNames[$month];
            $chartData[] = isset($monthData[$month]) ? floatval($monthData[$month]['doanh_thu']) : 0;
        }

        $reportData = $sortedData;
        break;

    case 'yearly':

        $currentYear = date('Y');
        $years = [];

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $years[] = $year;
            $doanhThu = $baoCao->getDoanhThuNam($year);
            $reportData[] = [
                'nam' => $year,
                'doanh_thu' => $doanhThu,
            ];
        }

        $reportData = array_reverse($reportData);

        foreach ($reportData as $item) {
            $chartLabels[] = $item['nam'];
            $chartData[] = floatval($item['doanh_thu']);
        }
        break;
}

$totalRevenue = 0;
foreach ($reportData as $item) {
    $totalRevenue += isset($item['doanh_thu']) ? $item['doanh_thu'] : 0;
}
?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-money-bill-wave"></i> Báo cáo doanh thu</h2>
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
        <form method="post" action="index.php?req=doanhThuView" id="reportForm">
            <div class="filter-group">
                <label for="reportType">Loại báo cáo:</label>
                <select name="reportType" id="reportType" onchange="changeReportType()">
                    <option value="daily" <?php echo $reportType == 'daily' ? 'selected' : ''; ?>>Theo ngày</option>
                    <option value="monthly" <?php echo $reportType == 'monthly' ? 'selected' : ''; ?>>Theo tháng
                    </option>
                    <option value="yearly" <?php echo $reportType == 'yearly' ? 'selected' : ''; ?>>Theo năm</option>
                </select>
            </div>

            <div id="dateRangeFilter" class="filter-group"
                <?php echo $reportType != 'daily' ? 'style="display:none;"' : ''; ?>>
                <label for="startDate">Từ ngày:</label>
                <input type="date" name="startDate" id="startDate" value="<?php echo $startDate; ?>">

                <label for="endDate">Đến ngày:</label>
                <input type="date" name="endDate" id="endDate" value="<?php echo $endDate; ?>">
            </div>

            <div id="yearFilter" class="filter-group"
                <?php echo $reportType != 'monthly' ? 'style="display:none;"' : ''; ?>>
                <label for="year">Năm:</label>
                <select name="year" id="year">
                    <?php
                    $currentYear = date('Y');
                    $selectedYear = isset($_POST['year']) ? intval($_POST['year']) : $currentYear;

                    for ($i = 0; $i < 10; $i++) {
                        $year = $currentYear - $i;
                        $selected = ($selectedYear == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Lọc
            </button>
        </form>
    </div>

    <div class="report-summary">
        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="summary-info">
                <h3>Tổng doanh thu</h3>
                <p class="summary-value"><?php echo number_format($totalRevenue, 0, ',', '.'); ?> đ</p>
                <p class="summary-subtitle">
                    <?php
                    if ($reportType == 'daily') {
                        echo 'Từ ' . date('d/m/Y', strtotime($startDate)) . ' đến ' . date('d/m/Y', strtotime($endDate));
                    } elseif ($reportType == 'monthly') {
                        echo 'Năm ' . (isset($_POST['year']) ? $_POST['year'] : date('Y'));
                    } else {
                        echo '5 năm gần nhất';
                    }
                    ?>
                </p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="summary-info">
                <h3>Số đơn hàng đã duyệt</h3>
                <p class="summary-value">
                    <?php
                    $totalOrders = 0;
                    foreach ($reportData as $item) {
                        $totalOrders += isset($item['so_don_hang']) ? intval($item['so_don_hang']) : 0;
                    }
                    echo $totalOrders;
                    ?>
                </p>
                <p class="summary-subtitle">Chỉ tính đơn hàng đã duyệt</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="summary-info">
                <h3>Doanh thu trung bình/đơn</h3>
                <p class="summary-value">
                    <?php
                    $avgRevenue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
                    echo number_format($avgRevenue, 0, ',', '.') . ' đ';
                    ?>
                </p>
                <p class="summary-subtitle">
                    <?php
                    if ($totalOrders > 0) {
                        echo 'Dựa trên ' . $totalOrders . ' đơn hàng';
                    } else {
                        echo 'Không có đơn hàng nào';
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="report-chart">
        <canvas id="revenueChart"></canvas>
    </div>

    <div class="report-table">
        <h3>Chi tiết doanh thu</h3>
        <table class="table">
            <thead>
                <tr>
                    <?php if ($reportType == 'daily'): ?>
                    <th>Ngày</th>
                    <th>Doanh thu</th>
                    <th>Số đơn hàng</th>
                    <th>Doanh thu trung bình/đơn</th>
                    <?php elseif ($reportType == 'monthly'): ?>
                    <th>Tháng</th>
                    <th>Doanh thu</th>
                    <th>Số đơn hàng</th>
                    <th>Doanh thu trung bình/đơn</th>
                    <?php else: ?>
                    <th>Năm</th>
                    <th>Doanh thu</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($reportType == 'daily'): ?>
                <?php foreach ($reportData as $item): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($item['ngay'])); ?></td>
                    <td><?php echo number_format($item['doanh_thu'], 0, ',', '.'); ?> đ</td>
                    <td><?php echo $item['so_don_hang']; ?></td>
                    <td>
                        <?php
                                $avgOrderValue = $item['so_don_hang'] > 0 ? $item['doanh_thu'] / $item['so_don_hang'] : 0;
                                echo number_format($avgOrderValue, 0, ',', '.') . ' đ';
                                ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php elseif ($reportType == 'monthly'): ?>
                <?php
                    $monthNames = [
                        1 => 'Tháng 1',
                        2 => 'Tháng 2',
                        3 => 'Tháng 3',
                        4 => 'Tháng 4',
                        5 => 'Tháng 5',
                        6 => 'Tháng 6',
                        7 => 'Tháng 7',
                        8 => 'Tháng 8',
                        9 => 'Tháng 9',
                        10 => 'Tháng 10',
                        11 => 'Tháng 11',
                        12 => 'Tháng 12'
                    ];
                    ?>
                <?php foreach ($reportData as $item): ?>
                <tr>
                    <td><?php echo $monthNames[$item['thang']]; ?></td>
                    <td><?php echo number_format($item['doanh_thu'], 0, ',', '.'); ?> đ</td>
                    <td><?php echo $item['so_don_hang']; ?></td>
                    <td>
                        <?php
                                $avgOrderValue = $item['so_don_hang'] > 0 ? $item['doanh_thu'] / $item['so_don_hang'] : 0;
                                echo number_format($avgOrderValue, 0, ',', '.') . ' đ';
                                ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <?php foreach ($reportData as $item): ?>
                <tr>
                    <td><?php echo $item['nam']; ?></td>
                    <td><?php echo number_format($item['doanh_thu'], 0, ',', '.'); ?> đ</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
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
    background: #007bff;
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

.summary-subtitle {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #777;
    font-style: italic;
}

.report-chart {
    margin-bottom: 30px;
    height: 300px;
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

@media (max-width: 768px) {
    .report-summary {
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

    console.log('Chart labels:', <?php echo json_encode($chartLabels); ?>);
    console.log('Chart data raw:', <?php echo json_encode($chartData); ?>);
    
    const rawChartData = <?php echo json_encode($chartData); ?>;
    const chartData = Array.isArray(rawChartData) ? rawChartData.map(value => parseFloat(value) || 0) : [];
    
    console.log('Chart data processed:', chartData);
    
    const canvasElement = document.getElementById('revenueChart');
    if (!canvasElement) {
        console.error('Canvas element not found!');
        const container = document.querySelector('.report-chart');
        if (container) {
            container.innerHTML = '<div class="alert alert-danger">Lỗi: Không tìm thấy canvas element để vẽ biểu đồ</div>';
        }
        return;
    }
    
    try {
        const ctx = canvasElement.getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Doanh thu',
                    data: chartData,
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + context.raw.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
        
        console.log('Chart created successfully:', revenueChart);
        
        if (chartData.length === 0 || chartData.every(value => value === 0)) {
            const chartContainer = document.querySelector('.report-chart');
            const noDataMessage = document.createElement('div');
            noDataMessage.className = 'alert alert-info';
            noDataMessage.textContent = 'Không có dữ liệu doanh thu trong khoảng thời gian này';
            chartContainer.appendChild(noDataMessage);
        }
        
    } catch (error) {
        console.error('Error creating chart:', error);
        const container = document.querySelector('.report-chart');
        if (container) {
            container.innerHTML = '<div class="alert alert-danger">Lỗi tạo biểu đồ: ' + error.message + '</div>';
        }
    }
});

function changeReportType() {
    const reportType = document.getElementById('reportType').value;
    const dateRangeFilter = document.getElementById('dateRangeFilter');
    const yearFilter = document.getElementById('yearFilter');

    if (reportType === 'daily') {
        dateRangeFilter.style.display = 'flex';
        yearFilter.style.display = 'none';
    } else if (reportType === 'monthly') {
        dateRangeFilter.style.display = 'none';
        yearFilter.style.display = 'flex';
    } else {
        dateRangeFilter.style.display = 'none';
        yearFilter.style.display = 'none';
    }
}

function printReport() {
    window.print();
}

function exportToExcel() {
    const table = document.querySelector('.table');
    const wb = XLSX.utils.table_to_book(table, {
        sheet: "Báo cáo doanh thu"
    });
    XLSX.writeFile(wb, 'bao-cao-doanh-thu.xlsx');
}

function isValidDate(dateString) {

    const regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(dateString)) return false;

    const date = new Date(dateString);
    const timestamp = date.getTime();
    if (isNaN(timestamp)) return false;

    return date.toISOString().split('T')[0] === dateString;
}
</script>