<?php

require_once 'administrator/elements_LQA/mod/database.php';

$orderCode = $_GET['code'] ?? '';
$orderInfo = null;
$trackingHistory = [];
$error = '';

if ($orderCode) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                dh.*,
                u.hoten as ten_khach_hang,
                u.dienthoai as so_dien_thoai
            FROM don_hang dh
            LEFT JOIN user u ON dh.ma_nguoi_dung COLLATE utf8mb4_general_ci = u.username COLLATE utf8mb4_general_ci
            WHERE dh.ma_don_hang_text = ? OR dh.id = ?
        ");
        $stmt->execute([$orderCode, is_numeric($orderCode) ? $orderCode : 0]);
        $orderInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($orderInfo) {

            $itemsStmt = $db->prepare("
                SELECT ctdh.*, h.tenhanghoa 
                FROM chi_tiet_don_hang ctdh
                LEFT JOIN hanghoa h ON ctdh.ma_san_pham = h.idhanghoa
                WHERE ctdh.ma_don_hang = ?
            ");
            $itemsStmt->execute([$orderInfo['id']]);
            $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            try {
                $stmt = $db->prepare("
                    SELECT * FROM shipment_tracking 
                    WHERE order_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$orderInfo['id']]);
                $trackingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $trackingHistory = [];
            }
        } else {
            $error = 'Không tìm thấy đơn hàng với mã: ' . htmlspecialchars($orderCode);
        }
        
    } catch (Exception $e) {
        error_log("Track Order Error: " . $e->getMessage());
        $error = 'Có lỗi xảy ra khi tra cứu đơn hàng';
    }
}

$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'approved' => 'Đã duyệt - Đang giao',
    'delivered' => 'Đã giao hàng',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
    'picking' => 'Đang lấy hàng',
    'shipping' => 'Đang vận chuyển',
    'failed' => 'Giao thất bại',
    'returned' => 'Đã hoàn trả'
];

$statusIcons = [
    'pending' => 'fa-clock',
    'approved' => 'fa-check',
    'delivered' => 'fa-truck',
    'completed' => 'fa-check-double',
    'cancelled' => 'fa-times-circle',
    'picking' => 'fa-box',
    'shipping' => 'fa-shipping-fast',
    'failed' => 'fa-exclamation-circle',
    'returned' => 'fa-undo'
];

$paymentLabels = [
    'cod' => 'COD (Thanh toán khi nhận hàng)',
    'momo' => 'Ví MoMo',
    'bank_transfer' => 'Chuyển khoản ngân hàng'
];

$paymentStatusLabels = [
    'pending' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'completed' => 'Đã thanh toán'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra Cứu Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .tracking-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .search-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .result-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .timeline {
            position: relative;
            padding-left: 50px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -35px;
            top: 0;
            width: 2px;
            height: 100%;
            background: #e9ecef;
        }
        .timeline-item:last-child::before {
            display: none;
        }
        .timeline-icon {
            position: absolute;
            left: -47px;
            top: 0;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .timeline-icon.active {
            background: #28a745;
            box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
        }
        .order-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-picking { background: #e2e3e5; color: #383d41; }
        .status-shipping { background: #cce5ff; color: #004085; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-returned { background: #e2e3e5; color: #383d41; }
        .table { margin-bottom: 0; }
        .badge { font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="tracking-container">
        <!-- Search Card -->
        <div class="search-card">
            <div class="text-center mb-4">
                <h1><i class="fas fa-search-location"></i> Tra Cứu Đơn Hàng</h1>
                <p class="text-muted">Nhập mã đơn hàng để tra cứu trạng thái vận chuyển</p>
            </div>
            
            <form method="GET" class="row g-3">
                <div class="col-md-9">
                    <input type="text" 
                           name="code" 
                           class="form-control form-control-lg" 
                           placeholder="Nhập mã đơn hàng (VD: DH20250101001)"
                           value="<?= htmlspecialchars($orderCode) ?>"
                           required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search"></i> Tra cứu
                    </button>
                </div>
            </form>
            
            <?php if ($error): ?>
            <div class="alert alert-danger mt-3">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Result Card -->
        <?php if ($orderInfo): ?>
        <div class="result-card">
            <!-- Order Info -->
            <div class="order-info">
                <h5 class="mb-3"><i class="fas fa-box"></i> Thông tin đơn hàng</h5>
                
                <div class="info-row">
                    <span><strong>Mã đơn hàng:</strong></span>
                    <span><code><?= htmlspecialchars($orderInfo['ma_don_hang_text'] ?? $orderInfo['id']) ?></code></span>
                </div>
                
                <div class="info-row">
                    <span><strong>Ngày đặt:</strong></span>
                    <span><?= date('d/m/Y H:i', strtotime($orderInfo['ngay_tao'])) ?></span>
                </div>
                
                <div class="info-row">
                    <span><strong>Khách hàng:</strong></span>
                    <span><?= htmlspecialchars($orderInfo['ten_khach_hang'] ?? $orderInfo['ma_nguoi_dung']) ?></span>
                </div>
                
                <?php if (!empty($orderInfo['so_dien_thoai'])): ?>
                <div class="info-row">
                    <span><strong>Số điện thoại:</strong></span>
                    <span><?= htmlspecialchars($orderInfo['so_dien_thoai']) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span><strong>Địa chỉ giao hàng:</strong></span>
                    <span><?= htmlspecialchars($orderInfo['dia_chi_giao_hang'] ?? 'Chưa có') ?></span>
                </div>
                
                <div class="info-row">
                    <span><strong>Phương thức thanh toán:</strong></span>
                    <span><?= $paymentLabels[$orderInfo['phuong_thuc_thanh_toan']] ?? htmlspecialchars($orderInfo['phuong_thuc_thanh_toan']) ?></span>
                </div>
                
                <div class="info-row">
                    <span><strong>Trạng thái thanh toán:</strong></span>
                    <span>
                        <?php if ($orderInfo['trang_thai_thanh_toan'] == 'paid'): ?>
                            <span class="badge bg-success">Đã thanh toán</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Chưa thanh toán</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if (!empty($orderInfo['shipping_method_name'])): ?>
                <div class="info-row">
                    <span><strong>Phương thức vận chuyển:</strong></span>
                    <span><?= htmlspecialchars($orderInfo['shipping_method_name']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($orderInfo['phi_van_chuyen']) && $orderInfo['phi_van_chuyen'] > 0): ?>
                <div class="info-row">
                    <span><strong>Phí vận chuyển:</strong></span>
                    <span><?= number_format($orderInfo['phi_van_chuyen'], 0, ',', '.') ?>₫</span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span><strong>Tổng tiền:</strong></span>
                    <span class="text-danger fw-bold"><?= number_format($orderInfo['tong_tien'], 0, ',', '.') ?>₫</span>
                </div>
                
                <div class="info-row">
                    <span><strong>Trạng thái đơn hàng:</strong></span>
                    <span>
                        <?php 
                        $status = $orderInfo['trang_thai'] ?? 'pending';
                        $statusClass = match($status) {
                            'pending' => 'bg-warning text-dark',
                            'approved' => 'bg-info',
                            'delivered' => 'bg-primary',
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <i class="fas <?= $statusIcons[$status] ?? 'fa-question' ?>"></i>
                            <?= $statusLabels[$status] ?? 'Không xác định' ?>
                        </span>
                    </span>
                </div>
                
                <?php if (!empty($orderInfo['estimated_delivery'])): ?>
                <div class="info-row">
                    <span><strong>Dự kiến giao hàng:</strong></span>
                    <span><?= htmlspecialchars($orderInfo['estimated_delivery']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Items -->
            <?php if (!empty($orderItems)): ?>
            <div class="order-info">
                <h5 class="mb-3"><i class="fas fa-shopping-cart"></i> Sản phẩm đã đặt</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-center">SL</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['tenhanghoa'] ?? 'Sản phẩm #'.$item['ma_san_pham']) ?></td>
                            <td class="text-center"><?= $item['so_luong'] ?></td>
                            <td class="text-end"><?= number_format($item['gia'], 0, ',', '.') ?>₫</td>
                            <td class="text-end"><?= number_format($item['gia'] * $item['so_luong'], 0, ',', '.') ?>₫</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Tracking Timeline -->
            <h5 class="mb-3"><i class="fas fa-route"></i> Lịch sử vận chuyển</h5>
            
            <?php if (empty($trackingHistory)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Chưa có thông tin vận chuyển
            </div>
            <?php else: ?>
            <div class="timeline">
                <?php foreach ($trackingHistory as $index => $track): ?>
                <div class="timeline-item">
                    <div class="timeline-icon <?= $index === 0 ? 'active' : '' ?>">
                        <i class="fas <?= $statusIcons[$track['status']] ?>"></i>
                    </div>
                    <div>
                        <strong><?= $statusLabels[$track['status']] ?></strong>
                        <p class="text-muted mb-1"><?= htmlspecialchars($track['status_description']) ?></p>
                        <?php if ($track['location']): ?>
                        <p class="text-muted mb-1">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($track['location']) ?>
                        </p>
                        <?php endif; ?>
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($track['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> In thông tin
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
