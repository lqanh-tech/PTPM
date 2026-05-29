<?php
/**
 * Order Tracking - Theo dõi trạng thái đơn hàng
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    header('Location: ../../userLogin.php');
    exit();
}

require_once __DIR__ . '/../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$orderCode = $_GET['code'] ?? '';
$order = null;
$orderItems = [];
$timeline = [];

if (!empty($orderCode)) {
    // Tìm đơn hàng
    $stmt = $conn->prepare("SELECT id, ma_don_hang, ma_don_hang_text, ma_nguoi_dung, ho_ten, so_dien_thoai, email, dia_chi_giao_hang, ghi_chu, tong_tien, trang_thai, phuong_thuc_thanh_toan, shipping_method, phi_van_chuyen, thue, coupon_discount, trang_thai_thanh_toan, ngay_tao, ngay_cap_nhat FROM don_hang WHERE ma_don_hang_text = ? AND ma_nguoi_dung = ?");
    $stmt->execute([$orderCode, $_SESSION['USER']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Lấy chi tiết đơn hàng
        $stmt = $conn->prepare("SELECT ct.*, h.tenhanghoa, h.hinhanh 
                                FROM chi_tiet_don_hang ct 
                                JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa 
                                WHERE ct.ma_don_hang = ?");
        $stmt->execute([$order['id']]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tạo timeline
        $timeline = [
            ['status' => 'pending', 'label' => 'Đặt hàng thành công', 'icon' => '📝', 'done' => true],
            ['status' => 'approved', 'label' => 'Đơn hàng đã được duyệt', 'icon' => '✅', 'done' => in_array($order['trang_thai'], ['approved', 'delivered', 'completed'])],
            ['status' => 'delivered', 'label' => 'Đang giao hàng', 'icon' => '🚚', 'done' => in_array($order['trang_thai'], ['delivered', 'completed'])],
            ['status' => 'completed', 'label' => 'Giao hàng thành công', 'icon' => '🎉', 'done' => $order['trang_thai'] === 'completed'],
        ];
        
        if ($order['trang_thai'] === 'cancelled') {
            $timeline[] = ['status' => 'cancelled', 'label' => 'Đơn hàng đã bị hủy', 'icon' => '❌', 'done' => true, 'cancelled' => true];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Theo dõi đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f5; }
        .tracking-container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .search-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 60px;
            margin-bottom: 30px;
        }
        .timeline-item:last-child { margin-bottom: 0; }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 1;
        }
        .timeline-icon.done { background: #27ae60; color: white; }
        .timeline-icon.active { background: #3498db; color: white; animation: pulse 2s infinite; }
        .timeline-icon.pending { background: #e9ecef; color: #adb5bd; }
        .timeline-icon.cancelled { background: #e74c3c; color: white; }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
        }
        
        .timeline-content h6 { margin: 0; font-weight: 600; }
        .timeline-content p { margin: 5px 0 0; color: #6c757d; font-size: 14px; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f5;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6c757d; }
        .info-value { font-weight: 600; }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f5;
        }
        .product-item:last-child { border-bottom: none; }
        .product-item img {
            width: 60px; height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        
        .badge-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../components/navbar.php'; ?>
    
    <div class="tracking-container">
        <h2 class="mb-4"><i class="fas fa-shipping-fast me-2"></i>Theo dõi đơn hàng</h2>
        
        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" class="d-flex gap-3">
                <input type="text" name="code" class="form-control form-control-lg" 
                       placeholder="Nhập mã đơn hàng (VD: ORDER1234567890)" 
                       value="<?= htmlspecialchars($orderCode) ?>">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-1"></i>Tra cứu
                </button>
            </form>
        </div>
        
        <?php if (!empty($orderCode) && !$order): ?>
            <!-- Not Found -->
            <div class="order-card text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>Không tìm thấy đơn hàng</h4>
                <p class="text-muted">Vui lòng kiểm tra lại mã đơn hàng hoặc liên hệ hỗ trợ.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($order): ?>
            <!-- Order Info -->
            <div class="order-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="mb-1">Đơn hàng #<?= htmlspecialchars($order['ma_don_hang_text']) ?></h4>
                        <p class="text-muted mb-0">Đặt ngày <?= date('d/m/Y H:i', strtotime($order['ngay_tao'])) ?></p>
                    </div>
                    <?php
                    $statusConfig = [
                        'pending' => ['bg-warning text-dark', 'Chờ xử lý'],
                        'approved' => ['bg-info', 'Đã duyệt'],
                        'delivered' => ['bg-primary', 'Đang giao'],
                        'completed' => ['bg-success', 'Hoàn tất'],
                        'cancelled' => ['bg-danger', 'Đã hủy'],
                    ];
                    $status = $statusConfig[$order['trang_thai']] ?? ['bg-secondary', $order['trang_thai']];
                    ?>
                    <span class="badge-status <?= $status[0] ?>"><?= $status[1] ?></span>
                </div>
                
                <!-- Timeline -->
                <div class="timeline">
                    <?php foreach ($timeline as $step): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon <?= $step['done'] ? ($step['cancelled'] ?? false ? 'cancelled' : 'done') : 'pending' ?>">
                                <?= $step['icon'] ?>
                            </div>
                            <div class="timeline-content">
                                <h6><?= $step['label'] ?></h6>
                                <?php if ($step['done'] && !($step['cancelled'] ?? false)): ?>
                                    <p>Hoàn thành</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="order-card">
                <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
                
                <div class="info-row">
                    <span class="info-label">Phương thức thanh toán</span>
                    <span class="info-value"><?= htmlspecialchars($order['phuong_thuc_thanh_toan']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái thanh toán</span>
                    <span class="info-value">
                        <?php
                        $paymentStatus = match($order['trang_thai_thanh_toan'] ?? 'pending') {
                            'paid' => '<span class="text-success">Đã thanh toán</span>',
                            'completed' => '<span class="text-success">Đã thanh toán</span>',
                            'failed' => '<span class="text-danger">Thất bại</span>',
                            default => '<span class="text-warning">Chờ thanh toán</span>'
                        };
                        echo $paymentStatus;
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Địa chỉ giao hàng</span>
                    <span class="info-value"><?= htmlspecialchars($order['dia_chi_giao_hang']) ?></span>
                </div>
                <?php if (!empty($order['order_notes'])): ?>
                <div class="info-row">
                    <span class="info-label">Ghi chú</span>
                    <span class="info-value"><?= htmlspecialchars($order['order_notes']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Products -->
            <div class="order-card">
                <h5 class="mb-3"><i class="fas fa-box me-2"></i>Sản phẩm</h5>
                
                <?php foreach ($orderItems as $item): ?>
                    <div class="product-item">
                        <?php
                        $imgSrc = (!empty($item['hinhanh']) && $item['hinhanh'] > 0)
                            ? "../../elements_LQA/mhanghoa/displayImage.php?id=" . $item['hinhanh']
                            : "../../elements_LQA/img_LQA/no-image.png";
                        ?>
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['tenhanghoa']) ?>">
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?= htmlspecialchars($item['tenhanghoa']) ?></div>
                            <div class="text-muted">Số lượng: <?= $item['so_luong'] ?></div>
                        </div>
                        <div class="text-danger fw-bold"><?= number_format($item['gia'] * $item['so_luong'], 0, ',', '.') ?>₫</div>
                    </div>
                <?php endforeach; ?>
                
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tạm tính:</span>
                        <span><?= number_format($order['tong_tien'] - ($order['thue'] ?? 0) - ($order['phi_van_chuyen'] ?? 0), 0, ',', '.') ?>₫</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Thuế VAT:</span>
                        <span><?= number_format($order['thue'] ?? 0, 0, ',', '.') ?>₫</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Phí vận chuyển:</span>
                        <span><?= number_format($order['phi_van_chuyen'] ?? 0, 0, ',', '.') ?>₫</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 text-danger pt-2 border-top">
                        <span>Tổng cộng:</span>
                        <span><?= number_format($order['tong_tien'], 0, ',', '.') ?>₫</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../../../components/footer.php'; ?>
</body>
</html>