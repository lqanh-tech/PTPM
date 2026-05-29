<?php

require_once '../administrator/elements_LQA/mod/sessionManager.php';
require_once '../administrator/elements_LQA/mod/database.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['USER'];
$db = Database::getInstance();
$conn = $db->getConnection();

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT dh.*, 
        (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ma_don_hang = dh.id) as so_san_pham,
        (SELECT SUM(so_luong) FROM chi_tiet_don_hang WHERE ma_don_hang = dh.id) as tong_so_luong
        FROM don_hang dh 
        WHERE dh.ma_nguoi_dung = ?";

$params = [$userId];

if ($activeTab === 'orders' && $statusFilter !== 'all') {
    $sql .= " AND dh.trang_thai = ?";
    $params[] = $statusFilter;
} elseif ($activeTab === 'payments') {
    $sql .= " AND dh.trang_thai = 'completed' AND dh.trang_thai_thanh_toan IN ('paid', 'completed')";
}

$sql .= " ORDER BY dh.ngay_tao DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statsSql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN trang_thai = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN trang_thai = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN trang_thai = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN trang_thai = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN trang_thai = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN trang_thai = 'completed' AND trang_thai_thanh_toan IN ('paid', 'completed') THEN tong_tien ELSE 0 END) as total_paid
    FROM don_hang WHERE ma_nguoi_dung = ?";
$statsStmt = $conn->prepare($statsSql);
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$userSql = "SELECT iduser, username, password, hoten, email, sodienthoai, diachi, role, trangthai, setlock, avatar_url, auth_provider, created_at FROM user WHERE username = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->execute([$userId]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    $map = [
        'pending' => ['class' => 'warning', 'icon' => 'fa-clock', 'text' => 'Chờ xác nhận'],
        'approved' => ['class' => 'info', 'icon' => 'fa-check-circle', 'text' => 'Đã duyệt'],
        'delivered' => ['class' => 'primary', 'icon' => 'fa-truck', 'text' => 'Đang giao'],
        'completed' => ['class' => 'success', 'icon' => 'fa-check-double', 'text' => 'Hoàn tất'],
        'cancelled' => ['class' => 'danger', 'icon' => 'fa-times-circle', 'text' => 'Đã hủy'],
    ];
    return $map[$status] ?? ['class' => 'secondary', 'icon' => 'fa-question', 'text' => $status];
}

function getPaymentBadge($status) {
    $map = [
        'pending' => ['class' => 'warning', 'icon' => 'fa-clock', 'text' => 'Chờ thanh toán'],
        'paid' => ['class' => 'success', 'icon' => 'fa-check', 'text' => 'Đã thanh toán'],
        'completed' => ['class' => 'success', 'icon' => 'fa-check-double', 'text' => 'Đã thanh toán'],
        'failed' => ['class' => 'danger', 'icon' => 'fa-times', 'text' => 'Thanh toán thất bại'],
    ];
    return $map[$status] ?? ['class' => 'secondary', 'icon' => 'fa-question', 'text' => $status];
}

function getPaymentMethodLabel($method) {
    $map = [
        'cod' => ['icon' => 'fa-money-bill-wave', 'text' => 'COD (Tiền mặt)'],
        'bank_transfer' => ['icon' => 'fa-university', 'text' => 'Chuyển khoản'],
        'momo' => ['icon' => 'fa-mobile-alt', 'text' => 'Ví MoMo'],
    ];
    return $map[$method] ?? ['icon' => 'fa-credit-card', 'text' => $method];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng & thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--gray-50);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--gray-900);
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 16px 80px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 4px;
        }

        .page-header p {
            color: var(--gray-500);
            margin: 0;
            font-size: 0.95rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            transition: box-shadow 0.2s, border-color 0.2s;
        }

        .stat-card:hover {
            border-color: var(--primary-light);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
        }

        .stat-card .stat-num {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .stat-pending .stat-num { color: var(--warning); }
        .stat-approved .stat-num { color: var(--info); }
        .stat-delivered .stat-num { color: var(--primary); }
        .stat-completed .stat-num { color: var(--success); }
        .stat-cancelled .stat-num { color: var(--danger); }

        .main-tabs {
            display: flex;
            gap: 4px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .main-tab {
            flex: 1;
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            background: transparent;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-500);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }

        .main-tab:hover {
            color: var(--gray-700);
            background: var(--gray-100);
        }

        .main-tab.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
        }

        .status-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .status-tab {
            padding: 7px 14px;
            border-radius: 20px;
            border: 1.5px solid var(--gray-200);
            background: #fff;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--gray-500);
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            white-space: nowrap;
        }

        .status-tab:hover {
            border-color: var(--primary-light);
            color: var(--primary);
        }

        .status-tab.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .status-tab .count {
            display: inline-block;
            background: rgba(0,0,0,0.08);
            border-radius: 10px;
            padding: 1px 7px;
            font-size: 0.72rem;
            margin-left: 4px;
            font-weight: 600;
        }

        .status-tab.active .count {
            background: rgba(255,255,255,0.25);
        }

        .order-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            margin-bottom: 14px;
            overflow: hidden;
            transition: box-shadow 0.2s, border-color 0.2s;
        }

        .order-card:hover {
            border-color: var(--gray-500);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .order-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--gray-100);
            flex-wrap: wrap;
            gap: 8px;
        }

        .order-top-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .order-code {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--gray-900);
        }

        .order-date {
            font-size: 0.82rem;
            color: var(--gray-500);
        }

        .order-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-status.bg-warning { background: #fef3c7; color: #92400e; }
        .badge-status.bg-info { background: #dbeafe; color: #1e40af; }
        .badge-status.bg-primary { background: #e0e7ff; color: #3730a3; }
        .badge-status.bg-success { background: #d1fae5; color: #065f46; }
        .badge-status.bg-danger { background: #fee2e2; color: #991b1b; }

        .order-body {
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-info .label {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .order-info .value {
            font-size: 0.88rem;
            color: var(--gray-700);
        }

        .order-total {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
        }

        .order-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-order {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1.5px solid var(--gray-200);
            background: #fff;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-order:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #eef2ff;
        }

        .btn-order.btn-primary-action {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .btn-order.btn-primary-action:hover {
            background: var(--primary-light);
            border-color: var(--primary-light);
        }

        .btn-order.btn-danger-action {
            color: var(--danger);
            border-color: #fecaca;
        }

        .btn-order.btn-danger-action:hover {
            background: #fef2f2;
            border-color: var(--danger);
        }

        .payment-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .payment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--gray-100);
            flex-wrap: wrap;
            gap: 8px;
        }

        .payment-body {
            padding: 16px 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .payment-item .label {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .payment-item .value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-top: 2px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--gray-200);
        }

        .empty-state i {
            font-size: 56px;
            color: var(--gray-200);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .empty-state p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .breadcrumb-nav {
            display: flex;
            gap: 6px;
            align-items: center;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        .breadcrumb-nav a {
            color: var(--gray-500);
            text-decoration: none;
        }

        .breadcrumb-nav a:hover {
            color: var(--primary);
        }

        .breadcrumb-nav .sep {
            color: var(--gray-200);
        }

        .breadcrumb-nav .current {
            color: var(--gray-700);
            font-weight: 500;
        }

        @media (max-width: 640px) {
            .stats-row {
                grid-template-columns: repeat(3, 1fr);
            }
            .order-body {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-actions {
                width: 100%;
            }
            .btn-order {
                flex: 1;
                justify-content: center;
            }
            .payment-body {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <div class="breadcrumb-nav">
        <a href="../index.php"><i class="fas fa-home"></i></a>
        <span class="sep"><i class="fas fa-chevron-right" style="font-size:0.65rem"></i></span>
        <span class="current">Lịch sử đơn hàng</span>
    </div>

    <div class="page-header">
        <h1>Xin chào, <?php echo htmlspecialchars($userInfo['hoTen'] ?? 'Bạn'); ?> 👋</h1>
        <p>Quản lý đơn hàng và lịch sử thanh toán của bạn</p>
    </div>

    <div class="stats-row">
        <div class="stat-card stat-pending">
            <div class="stat-num"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Chờ xử lý</div>
        </div>
        <div class="stat-card stat-approved">
            <div class="stat-num"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Đã duyệt</div>
        </div>
        <div class="stat-card stat-delivered">
            <div class="stat-num"><?php echo $stats['delivered']; ?></div>
            <div class="stat-label">Đang giao</div>
        </div>
        <div class="stat-card stat-completed">
            <div class="stat-num"><?php echo $stats['completed']; ?></div>
            <div class="stat-label">Hoàn tất</div>
        </div>
        <div class="stat-card stat-cancelled">
            <div class="stat-num"><?php echo $stats['cancelled']; ?></div>
            <div class="stat-label">Đã hủy</div>
        </div>
    </div>

    <div class="main-tabs">
        <a href="?tab=orders&status=<?php echo $statusFilter; ?>" class="main-tab <?php echo $activeTab === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list me-1"></i> Đơn hàng
        </a>
        <a href="?tab=payments" class="main-tab <?php echo $activeTab === 'payments' ? 'active' : ''; ?>">
            <i class="fas fa-receipt me-1"></i> Lịch sử thanh toán
        </a>
    </div>

    <?php if ($activeTab === 'orders'): ?>
        <div class="status-tabs">
            <a href="?tab=orders&status=all" class="status-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                Tất cả <span class="count"><?php echo $stats['total']; ?></span>
            </a>
            <a href="?tab=orders&status=pending" class="status-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock me-1"></i>Chờ xác nhận <span class="count"><?php echo $stats['pending']; ?></span>
            </a>
            <a href="?tab=orders&status=approved" class="status-tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle me-1"></i>Đã duyệt <span class="count"><?php echo $stats['approved']; ?></span>
            </a>
            <a href="?tab=orders&status=delivered" class="status-tab <?php echo $statusFilter === 'delivered' ? 'active' : ''; ?>">
                <i class="fas fa-truck me-1"></i>Đang giao <span class="count"><?php echo $stats['delivered']; ?></span>
            </a>
            <a href="?tab=orders&status=completed" class="status-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                <i class="fas fa-check-double me-1"></i>Hoàn tất <span class="count"><?php echo $stats['completed']; ?></span>
            </a>
            <a href="?tab=orders&status=cancelled" class="status-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                <i class="fas fa-times-circle me-1"></i>Đã hủy <span class="count"><?php echo $stats['cancelled']; ?></span>
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Không có đơn hàng nào</h3>
                <p><?php echo $statusFilter !== 'all' ? 'Không có đơn hàng nào ở trạng thái này.' : 'Bạn chưa có đơn hàng nào. Hãy mua sắm ngay!'; ?></p>
                <a href="../index.php" class="btn-order btn-primary-action" style="margin-top:12px;display:inline-flex;">
                    <i class="fas fa-shopping-bag"></i> Mua sắm
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php $s = getStatusBadge($order['trang_thai']); ?>
                <?php $p = getPaymentBadge($order['trang_thai_thanh_toan']); ?>
                <?php $pm = getPaymentMethodLabel($order['phuong_thuc_thanh_toan']); ?>

                <div class="order-card">
                    <div class="order-top">
                        <div class="order-top-left">
                            <span class="order-code">#<?php echo htmlspecialchars($order['ma_don_hang_text']); ?></span>
                            <span class="order-date"><i class="far fa-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></span>
                        </div>
                        <div class="order-badges">
                            <span class="badge-status bg-<?php echo $s['class']; ?>">
                                <i class="fas <?php echo $s['icon']; ?>"></i> <?php echo $s['text']; ?>
                            </span>
                            <?php if ($order['trang_thai'] !== 'cancelled'): ?>
                                <span class="badge-status bg-<?php echo $p['class']; ?>">
                                    <i class="fas <?php echo $p['icon']; ?>"></i> <?php echo $p['text']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="order-info">
                            <span class="label">Thanh toán</span>
                            <span class="value"><i class="fas <?php echo $pm['icon']; ?> me-1"></i><?php echo $pm['text']; ?></span>
                        </div>
                        <div class="order-info">
                            <span class="label">Sản phẩm</span>
                            <span class="value"><?php echo $order['so_san_pham']; ?> sản phẩm (<?php echo $order['tong_so_luong']; ?> món)</span>
                        </div>
                        <div class="order-info">
                            <span class="label">Giao đến</span>
                            <span class="value"><?php echo htmlspecialchars($order['dia_chi_giao_hang'] ?? '—'); ?></span>
                        </div>
                        <div class="order-total">
                            <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>₫
                        </div>
                        <div class="order-actions">
                            <button class="btn-order" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i> Chi tiết
                            </button>
                            <?php if ($order['trang_thai'] === 'completed'): ?>
                                <a href="order_invoice.php?order_id=<?php echo $order['id']; ?>" class="btn-order" target="_blank">
                                    <i class="fas fa-file-invoice"></i> Hóa đơn
                                </a>
                            <?php endif; ?>
                            <?php if ($order['trang_thai'] === 'pending' && $order['phuong_thuc_thanh_toan'] === 'bank_transfer' && $order['trang_thai_thanh_toan'] === 'pending'): ?>
                                <button class="btn-order btn-primary-action" onclick="showPaymentInfo(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-info-circle"></i> TT thanh toán
                                </button>
                            <?php endif; ?>
                            <?php if ($order['trang_thai'] === 'pending'): ?>
                                <button class="btn-order btn-danger-action" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-times"></i> Hủy đơn
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php elseif ($activeTab === 'payments'): ?>
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>Chưa có thanh toán nào</h3>
                <p>Chỉ hiển thị các đơn hàng đã hoàn tất và đã thanh toán.</p>
            </div>
        <?php else: ?>
            <div style="margin-bottom:16px;padding:12px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:0.88rem;color:#166534;">
                <i class="fas fa-check-circle me-1"></i>
                <strong><?php echo count($orders); ?></strong> đơn đã thanh toán &mdash; Tổng: <strong><?php echo number_format($stats['total_paid'], 0, ',', '.'); ?>₫</strong>
            </div>

            <?php foreach ($orders as $order): ?>
                <?php $pm = getPaymentMethodLabel($order['phuong_thuc_thanh_toan']); ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <div class="order-top-left">
                            <span class="order-code">#<?php echo htmlspecialchars($order['ma_don_hang_text']); ?></span>
                            <span class="order-date"><i class="far fa-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></span>
                        </div>
                        <span class="badge-status bg-success">
                            <i class="fas fa-check-double"></i> Đã thanh toán
                        </span>
                    </div>
                    <div class="payment-body">
                        <div class="payment-item">
                            <div class="label">Số tiền</div>
                            <div class="value" style="color:var(--success);font-size:1.1rem;">
                                <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>₫
                            </div>
                        </div>
                        <div class="payment-item">
                            <div class="label">Phương thức</div>
                            <div class="value"><i class="fas <?php echo $pm['icon']; ?> me-1"></i><?php echo $pm['text']; ?></div>
                        </div>
                        <div class="payment-item">
                            <div class="label">Mã giao dịch</div>
                            <div class="value" style="font-family:monospace;font-size:0.82rem;">
                                <?php echo htmlspecialchars($order['id']); ?>
                            </div>
                        </div>
                        <div class="payment-item">
                            <div class="label">Trạng thái đơn</div>
                            <div class="value">
                                <?php $s = getStatusBadge($order['trang_thai']); ?>
                                <span class="badge-status bg-<?php echo $s['class']; ?>">
                                    <i class="fas <?php echo $s['icon']; ?>"></i> <?php echo $s['text']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div style="padding:12px 18px;border-top:1px solid var(--gray-100);display:flex;gap:8px;">
                        <button class="btn-order" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                            <i class="fas fa-eye"></i> Chi tiết đơn hàng
                        </button>
                        <a href="order_invoice.php?order_id=<?php echo $order['id']; ?>" class="btn-order" target="_blank">
                            <i class="fas fa-file-invoice"></i> Xem hóa đơn
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

</div>

<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailContent"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewOrderDetail(orderId) {
    fetch(`../administrator/elements_LQA/mkhachhang/orderDetailAjax.php?order_id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            alert('Không thể tải chi tiết đơn hàng');
        });
}

function showPaymentInfo(orderId) {
    alert('Vui lòng chuyển khoản theo thông tin đã cung cấp khi đặt hàng.');
}

function cancelOrder(orderId) {
    if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
        alert('Chức năng hủy đơn hàng đang được phát triển.');
    }
}
</script>
</body>
</html>
