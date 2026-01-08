<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('adminGiohangView', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mod/giohangCls.php';
require_once './elements_LQA/mod/userCls.php';

$giohang = new GioHang();
$user = new user();
$users = $user->UserGetAll();

$totalCartsValue = 0;
$activeCartsCount = 0;
$totalItemsCount = 0;

foreach ($users as $u) {
    if ($u->username !== 'admin') {
        $cart = $giohang->getCartByUserId($u->username);
        if (!empty($cart)) {
            $activeCartsCount++;
            foreach ($cart as $item) {
                $totalCartsValue += $item['giathamkhao'] * $item['quantity'];
                $totalItemsCount += $item['quantity'];
            }
        }
    }
}
?>

<div class="admin-title">Quản lý giỏ hàng</div>
<hr>

<div class="admin-dashboard">
    <div class="dashboard-cards">
        <div class="dashboard-card primary">
            <div class="card-content">
                <div class="card-info">
                    <h4>Tổng giỏ hàng đang hoạt động</h4>
                    <h2><?php echo $activeCartsCount; ?></h2>
                </div>
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-card success">
            <div class="card-content">
                <div class="card-info">
                    <h4>Tổng số sản phẩm trong giỏ</h4>
                    <h2><?php echo $totalItemsCount; ?></h2>
                </div>
                <div class="card-icon">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-card info">
            <div class="card-content">
                <div class="card-info">
                    <h4>Tổng giá trị giỏ hàng</h4>
                    <h2><?php echo number_format($totalCartsValue, 0, ',', '.'); ?> đ</h2>
                </div>
                <div class="card-icon">
                    <i class="fas fa-money-bill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-content">
        <div class="content-header">
            <h3>Chi tiết giỏ hàng theo người dùng</h3>
            <button class="btn-print" onclick="printReport()">
                <i class="fas fa-print"></i> In báo cáo
            </button>
        </div>

        <div id="print-section">
            <div class="print-header">
                <h2>Báo Cáo Chi Tiết Giỏ Hàng</h2>
                <p>Ngày in: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <div class="dashboard-summary">
                <div class="summary-item">
                    <span>Tổng giỏ hàng: <?php echo $activeCartsCount; ?></span>
                </div>
                <div class="summary-item">
                    <span>Tổng sản phẩm: <?php echo $totalItemsCount; ?></span>
                </div>
                <div class="summary-item">
                    <span>Tổng giá trị: <?php echo number_format($totalCartsValue, 0, ',', '.'); ?> đ</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Tên sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u):
                            if ($u->username !== 'admin'):
                                $cart = $giohang->getCartByUserId($u->username);
                                if (!empty($cart)):
                                    foreach ($cart as $item):
                                        $subtotal = $item['giathamkhao'] * $item['quantity'];
                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u->username); ?></td>
                                            <td><?php echo htmlspecialchars($item['tenhanghoa']); ?></td>
                                            <td><?php echo number_format($item['giathamkhao'], 0, ',', '.'); ?> đ</td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</td>
                                        </tr>
                        <?php
                                    endforeach;
                                endif;
                            endif;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>

    @media print {

        body * {
            visibility: hidden;
        }

        #print-section,
        #print-section * {
            visibility: visible;
        }

        #print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .btn-print,
        .btn-delete,
        nav,
        footer {
            display: none !important;
        }

        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-header h2 {
            margin: 0;
            color: #333;
        }

        .print-header p {
            margin: 5px 0;
            color: #666;
        }

        .table-responsive {
            overflow: visible;
            margin-top: 20px;
        }

        .content-table {
            border-collapse: collapse;
            width: 100%;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .dashboard-summary {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .summary-item {
            text-align: center;
        }

        @page {
            size: landscape;
            margin: 2cm;
        }
    }

    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }

    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }

    .border-left-info {
        border-left: 4px solid #36b9cc !important;
    }

    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .table> :not(caption)>*>* {
        padding: 0.75rem;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
    }

    .content-table th:nth-child(5),
    .content-table td:nth-child(5) {
        min-width: 120px;
        width: auto;
        text-align: right;
        white-space: nowrap;
    }
</style>

<script>
    function printReport() {
        window.print();
    }

    function exportExcel() {
        alert('Tính năng đang được phát triển');
    }

    function removeItem(userId, productId) {
        if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {

            alert('Tính năng đang được phát triển');
        }
    }

    const style = document.createElement('style');
    style.textContent = `
    @media print {
        .btn, nav, footer {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
        }
        .table {
            width: 100% !important;
        }
        @page {
            size: landscape;
        }
    }
`;
    document.head.appendChild(style);
</script>