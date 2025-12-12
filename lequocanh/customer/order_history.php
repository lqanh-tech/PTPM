<?php
// Customer Order History Page
require_once '../administrator/elements_LQA/mod/sessionManager.php';
require_once '../administrator/elements_LQA/mod/database.php';

// Start session safely
SessionManager::start();

// Check if user is logged in
if (!isset($_SESSION['USER'])) {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['USER'];

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get user orders
$sql = "SELECT dh.*, 
        (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ma_don_hang = dh.id) as so_san_pham,
        (SELECT SUM(so_luong) FROM chi_tiet_don_hang WHERE ma_don_hang = dh.id) as tong_so_luong
        FROM don_hang dh 
        WHERE dh.ma_nguoi_dung = ? 
        ORDER BY dh.ngay_tao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user info
$userSql = "SELECT * FROM user WHERE username = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->execute([$userId]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử mua hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .order-history-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .user-header h2 {
            margin: 0;
            font-size: 28px;
        }
        .user-info {
            margin-top: 10px;
            opacity: 0.9;
        }
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .order-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .order-body {
            padding: 20px;
        }
        .order-id {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .order-date {
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .payment-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .payment-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .empty-state i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .back-to-shop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Notification widget removed - notifications are shown on index page only -->

    <div class="order-history-container">
        <!-- User Header -->
        <div class="user-header">
            <h2><i class="fas fa-shopping-bag me-2"></i>Lịch sử mua hàng</h2>
            <div class="user-info">
                <p class="mb-1">Xin chào, <strong><?php echo htmlspecialchars($userInfo['hoTen'] ?? 'Khách hàng'); ?></strong></p>
                <p class="mb-0"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($userInfo['email'] ?? ''); ?></p>
            </div>
        </div>

        <!-- Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Lịch sử mua hàng</li>
            </ol>
        </nav>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>Bạn chưa có đơn hàng nào</h3>
                <p class="text-muted">Hãy khám phá và mua sắm ngay!</p>
                <a href="../index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-shopping-bag me-2"></i>Mua sắm ngay
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="order-id">Đơn hàng #<?php echo $order['id']; ?></div>
                                <div class="order-date">
                                    <i class="far fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="mb-2">
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($order['trang_thai']) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            $statusText = 'Chờ xử lý';
                                            break;
                                        case 'approved':
                                            $statusClass = 'status-approved';
                                            $statusText = 'Đã duyệt';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'status-cancelled';
                                            $statusText = 'Đã hủy';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>
                                <div>
                                    <?php
                                    $paymentClass = '';
                                    $paymentText = '';
                                    switch ($order['trang_thai_thanh_toan']) {
                                        case 'pending':
                                            $paymentClass = 'payment-pending';
                                            $paymentText = 'Chờ thanh toán';
                                            break;
                                        case 'paid':
                                            $paymentClass = 'payment-paid';
                                            $paymentText = 'Đã thanh toán';
                                            break;
                                        default:
                                            $paymentClass = 'payment-pending';
                                            $paymentText = $order['trang_thai_thanh_toan'];
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $paymentClass; ?>">
                                        <i class="fas fa-credit-card me-1"></i><?php echo $paymentText; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="fw-bold fs-5 text-primary">
                                    <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> đ
                                </div>
                                <div class="text-muted small">
                                    <?php echo $order['so_san_pham']; ?> sản phẩm
                                    (<?php echo $order['tong_so_luong']; ?> món)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="row">
                            <div class="col-md-8">
                                <p class="mb-2">
                                    <strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($order['ma_don_hang_text']); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Phương thức thanh toán:</strong> 
                                    <?php
                                    switch ($order['phuong_thuc_thanh_toan']) {
                                        case 'cod':
                                            echo '<i class="fas fa-truck me-1"></i>COD';
                                            break;
                                        case 'bank_transfer':
                                            echo '<i class="fas fa-university me-1"></i>Chuyển khoản';
                                            break;
                                        case 'momo':
                                            echo '<i class="fas fa-mobile-alt me-1"></i>MoMo';
                                            break;
                                        default:
                                            echo $order['phuong_thuc_thanh_toan'];
                                    }
                                    ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Địa chỉ giao hàng:</strong> 
                                    <?php echo htmlspecialchars($order['dia_chi_giao_hang'] ?? 'Chưa có'); ?>
                                </p>
                                <?php if (isset($order['thue']) && $order['thue'] > 0): ?>
                                <p class="mb-2">
                                    <strong>Thuế VAT:</strong> 
                                    <span class="text-muted"><?php echo number_format($order['thue'], 0, ',', '.'); ?> đ</span>
                                </p>
                                <?php endif; ?>
                                <?php if (isset($order['phi_van_chuyen']) && $order['phi_van_chuyen'] > 0): ?>
                                <p class="mb-2">
                                    <strong>Phí vận chuyển:</strong> 
                                    <span class="text-muted"><?php echo number_format($order['phi_van_chuyen'], 0, ',', '.'); ?> đ</span>
                                    <?php if (isset($order['shipping_method_name']) && !empty($order['shipping_method_name'])): ?>
                                        <small class="text-info">(<?php echo htmlspecialchars($order['shipping_method_name']); ?>)</small>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                                <?php if (isset($order['estimated_delivery']) && !empty($order['estimated_delivery'])): ?>
                                <p class="mb-0">
                                    <strong>Dự kiến giao:</strong> 
                                    <span class="text-success"><?php echo htmlspecialchars($order['estimated_delivery']); ?></span>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <div class="order-actions justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i>Chi tiết
                                    </button>
                                    <?php if ($order['trang_thai'] == 'pending' && $order['phuong_thuc_thanh_toan'] == 'bank_transfer' && $order['trang_thai_thanh_toan'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-warning" onclick="showPaymentInfo(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-info-circle me-1"></i>TT Thanh toán
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($order['trang_thai'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-times me-1"></i>Hủy đơn
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Back to Shop Button -->
    <div class="back-to-shop">
        <a href="../index.php" class="btn btn-primary btn-lg shadow">
            <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua hàng
        </a>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewOrderDetail(orderId) {
        // Load order details via AJAX
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
        // Show payment information
        alert('Vui lòng chuyển khoản theo thông tin đã cung cấp khi đặt hàng.');
    }

    function cancelOrder(orderId) {
        if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
            // Implement order cancellation
            alert('Chức năng hủy đơn hàng đang được phát triển.');
        }
    }
    </script>
</body>
</html>
