<?php
/**
 * Trang xem hóa đơn và đánh giá sản phẩm
 * Hiển thị sau khi admin duyệt đơn
 */

require_once '../administrator/elements_LQA/mod/sessionManager.php';
require_once '../administrator/elements_LQA/mod/database.php';

SessionManager::start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Location: ../administrator/userLogin.php');
    exit();
}

$userId = $_SESSION['USER'];
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: order_history.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Lấy thông tin đơn hàng
$orderSql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
$stmt = $conn->prepare($orderSql);
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='order_history.php';</script>";
    exit();
}

// Lấy chi tiết sản phẩm
$itemsSql = "SELECT cdh.*, hh.ten_hang_hoa, hh.hinh_anh 
             FROM chi_tiet_don_hang cdh
             JOIN tbl_hanghoa hh ON cdh.ma_san_pham = hh.id
             WHERE cdh.ma_don_hang = ?";
$stmt = $conn->prepare($itemsSql);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra đơn hàng đã được duyệt chưa
$isApproved = ($order['trang_thai'] == 'approved' || $order['trang_thai_thanh_toan'] == 'paid');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #<?php echo $order['ma_don_hang_text']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 20px 0; }
        .invoice-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
        .invoice-header { border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-header h2 { color: #007bff; margin-bottom: 5px; }
        .invoice-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .info-box h5 { font-size: 14px; color: #666; margin-bottom: 10px; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 600; display: inline-block; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .items-table { margin: 30px 0; }
        .items-table table { width: 100%; border-collapse: collapse; }
        .items-table th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; }
        .items-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .total-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .total-row.final { font-size: 1.3rem; font-weight: 700; color: #dc3545; border-top: 2px solid #dee2e6; padding-top: 15px; margin-top: 10px; }
        .action-buttons { margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-print { background: #007bff; color: white; }
        .btn-download { background: #28a745; color: white; }
        @media print {
            .action-buttons, .no-print { display: none !important; }
            body { background: white; }
            .invoice-container { box-shadow: none; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2><i class="fas fa-file-invoice"></i> HÓA ĐƠN</h2>
                    <p class="text-muted mb-0">Mã đơn hàng: <strong><?php echo $order['ma_don_hang_text']; ?></strong></p>
                    <p class="text-muted">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></p>
                </div>
                <div class="text-end">
                    <h4>CỬA HÀNG ĐIỆN THOẠI</h4>
                    <p class="mb-0">Địa chỉ: 123 Đường ABC, TP.HCM</p>
                    <p class="mb-0">Điện thoại: 0123 456 789</p>
                    <p class="mb-0">Email: support@shop.com</p>
                </div>
            </div>
        </div>

        <!-- Thông tin đơn hàng -->
        <div class="invoice-info">
            <div class="info-box">
                <h5><i class="fas fa-user"></i> THÔNG TIN KHÁCH HÀNG</h5>
                <p><strong>Mã khách hàng:</strong> <?php echo $order['ma_nguoi_dung']; ?></p>
                <p><strong>Địa chỉ giao hàng:</strong><br><?php echo nl2br(htmlspecialchars($order['dia_chi_giao_hang'])); ?></p>
            </div>
            
            <div class="info-box">
                <h5><i class="fas fa-info-circle"></i> TRẠNG THÁI ĐƠN HÀNG</h5>
                <p><strong>Trạng thái:</strong> 
                    <?php if ($order['trang_thai'] == 'approved'): ?>
                        <span class="status-badge status-approved"><i class="fas fa-check-circle"></i> Đã duyệt</span>
                    <?php elseif ($order['trang_thai'] == 'pending'): ?>
                        <span class="status-badge status-pending"><i class="fas fa-clock"></i> Chờ duyệt</span>
                    <?php else: ?>
                        <span class="status-badge"><?php echo $order['trang_thai']; ?></span>
                    <?php endif; ?>
                </p>
                <p><strong>Thanh toán:</strong> 
                    <?php if ($order['trang_thai_thanh_toan'] == 'paid'): ?>
                        <span class="status-badge status-paid"><i class="fas fa-check"></i> Đã thanh toán</span>
                    <?php else: ?>
                        <span class="status-badge status-pending"><i class="fas fa-clock"></i> Chờ thanh toán</span>
                    <?php endif; ?>
                </p>
                <p><strong>Phương thức:</strong> 
                    <?php
                    switch ($order['phuong_thuc_thanh_toan']) {
                        case 'momo': echo '<i class="fas fa-mobile-alt"></i> MoMo'; break;
                        case 'bank_transfer': echo '<i class="fas fa-university"></i> Chuyển khoản'; break;
                        case 'cod': echo '<i class="fas fa-truck"></i> COD'; break;
                        default: echo $order['phuong_thuc_thanh_toan'];
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="items-table">
            <h5><i class="fas fa-shopping-cart"></i> CHI TIẾT SẢN PHẨM</h5>
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($items as $item): 
                        $itemTotal = $item['gia'] * $item['so_luong'];
                        $subtotal += $itemTotal;
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($item['hinh_anh']): ?>
                                        <img src="../administrator/elements_LQA/<?php echo $item['hinh_anh']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['ten_hang_hoa']); ?>" 
                                             class="product-img">
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($item['ten_hang_hoa']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo number_format($item['gia'], 0, ',', '.'); ?> đ</td>
                            <td><?php echo $item['so_luong']; ?></td>
                            <td><strong><?php echo number_format($itemTotal, 0, ',', '.'); ?> đ</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tổng tiền -->
        <div class="total-section">
            <div class="total-row">
                <span>Tổng tiền hàng:</span>
                <span><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="total-row">
                <span>Thuế VAT (8%):</span>
                <span><?php echo number_format($order['thue'] ?? 0, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="total-row">
                <span>Phí vận chuyển:</span>
                <span>
                    <?php if (($order['phi_van_chuyen'] ?? 0) == 0): ?>
                        <span class="text-success">Miễn phí</span>
                    <?php else: ?>
                        <?php echo number_format($order['phi_van_chuyen'], 0, ',', '.'); ?> đ
                    <?php endif; ?>
                </span>
            </div>
            <div class="total-row final">
                <span>TỔNG THANH TOÁN:</span>
                <span><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> đ</span>
            </div>
        </div>

        <!-- Nút hành động -->
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> In hóa đơn
            </button>
            <a href="../administrator/elements_LQA/madmin/print_invoice.php?order_id=<?php echo $orderId; ?>" 
               target="_blank" class="btn btn-download">
                <i class="fas fa-download"></i> Tải PDF
            </a>
            <a href="order_history.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <!-- Widget đánh giá (chỉ hiển thị khi đã duyệt) -->
        <?php if ($isApproved): ?>
            <div class="mt-5 no-print">
                <hr>
                <?php 
                $orderId = $orderId; // Pass to widget
                include '../components/product_review_widget.php'; 
                ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4 no-print">
                <i class="fas fa-info-circle"></i> 
                Bạn có thể đánh giá sản phẩm sau khi đơn hàng được duyệt.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
