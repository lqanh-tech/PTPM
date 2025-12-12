<?php
/**
 * Get Order Detail
 * API endpoint để lấy thông tin chi tiết đơn hàng
 */

// Use SessionManager for safe session handling
require_once '../mod/sessionManager.php';
SessionManager::start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Bạn không có quyền truy cập!</div>';
    exit();
}

require_once '../mod/database.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    echo '<div class="alert alert-danger">ID đơn hàng không hợp lệ!</div>';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Lấy thông tin đơn hàng
    $orderSql = "SELECT * FROM don_hang WHERE id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo '<div class="alert alert-danger">Không tìm thấy đơn hàng!</div>';
        exit();
    }
    
    // Lấy chi tiết sản phẩm
    $itemsSql = "SELECT cdh.*, h.tenhanghoa, h.hinhanh 
                 FROM chi_tiet_don_hang cdh
                 LEFT JOIN hanghoa h ON cdh.ma_san_pham = h.idhanghoa
                 WHERE cdh.ma_don_hang = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính toán
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['gia'] * $item['so_luong'];
    }
    
    $vatAmount = isset($order['thue']) ? $order['thue'] : 0;
    $shippingFee = isset($order['phi_van_chuyen']) ? $order['phi_van_chuyen'] : 0;
    $finalTotal = $order['tong_tien'];
    
    ?>
    
    <style>
        .order-detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .order-detail-section h5 {
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        .product-info {
            flex-grow: 1;
        }
        .product-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }
        .product-price {
            color: #6c757d;
            font-size: 14px;
        }
        .product-quantity {
            font-weight: 600;
            color: #667eea;
        }
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .total-final {
            font-size: 24px;
            font-weight: bold;
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 15px;
            margin-top: 10px;
        }
    </style>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Thông tin đơn hàng -->
            <div class="col-md-6">
                <div class="order-detail-section">
                    <h5><i class="fas fa-info-circle me-2"></i>Thông Tin Đơn Hàng</h5>
                    
                    <div class="info-row">
                        <span class="info-label">Mã đơn hàng:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($order['ma_don_hang_text']); ?></strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Khách hàng:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['ma_nguoi_dung']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Ngày đặt:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Trạng thái:</span>
                        <span class="info-value">
                            <?php
                            switch ($order['trang_thai']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning text-dark">Chờ xác nhận</span>';
                                    break;
                                case 'approved':
                                    echo '<span class="badge bg-success">Đã duyệt</span>';
                                    break;
                                case 'cancelled':
                                    echo '<span class="badge bg-danger">Đã hủy</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">' . $order['trang_thai'] . '</span>';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Thanh toán:</span>
                        <span class="info-value">
                            <?php
                            switch ($order['phuong_thuc_thanh_toan']) {
                                case 'momo':
                                    echo '<span class="badge bg-primary">MoMo</span>';
                                    break;
                                case 'cod':
                                    echo '<span class="badge bg-success">COD</span>';
                                    break;
                                case 'bank_transfer':
                                    echo '<span class="badge bg-info">Chuyển khoản</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">' . $order['phuong_thuc_thanh_toan'] . '</span>';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Trạng thái TT:</span>
                        <span class="info-value">
                            <?php
                            $paymentStatus = isset($order['trang_thai_thanh_toan']) ? $order['trang_thai_thanh_toan'] : 'pending';
                            switch ($paymentStatus) {
                                case 'paid':
                                    echo '<span class="badge bg-success">Đã thanh toán</span>';
                                    break;
                                case 'pending':
                                    echo '<span class="badge bg-warning text-dark">Chờ thanh toán</span>';
                                    break;
                                case 'failed':
                                    echo '<span class="badge bg-danger">Thất bại</span>';
                                    break;
                                default:
                                    echo '<span class="badge bg-secondary">' . $paymentStatus . '</span>';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Địa chỉ giao hàng -->
            <div class="col-md-6">
                <div class="order-detail-section">
                    <h5><i class="fas fa-map-marker-alt me-2"></i>Địa Chỉ Giao Hàng</h5>
                    
                    <div class="info-row">
                        <span class="info-value">
                            <?php echo nl2br(htmlspecialchars($order['dia_chi_giao_hang'])); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($order['ghi_chu'])): ?>
                    <div class="info-row mt-3">
                        <span class="info-label">Ghi chú:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">
                            <?php echo nl2br(htmlspecialchars($order['ghi_chu'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php
                // Hiển thị thông tin đổi/trả nếu có
                $returnStatus = isset($order['trang_thai_doi_tra']) ? $order['trang_thai_doi_tra'] : 'none';
                if ($returnStatus != 'none'):
                ?>
                <div class="order-detail-section">
                    <h5><i class="fas fa-exchange-alt me-2"></i>Thông Tin Đổi/Trả</h5>
                    
                    <div class="info-row">
                        <span class="info-label">Trạng thái:</span>
                        <span class="info-value">
                            <?php
                            switch ($returnStatus) {
                                case 'requested':
                                    echo '<span class="badge bg-warning text-dark">Yêu cầu đổi/trả</span>';
                                    break;
                                case 'approved':
                                    echo '<span class="badge bg-info">Đã duyệt đổi/trả</span>';
                                    break;
                                case 'rejected':
                                    echo '<span class="badge bg-secondary">Từ chối đổi/trả</span>';
                                    break;
                            }
                            ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($order['ly_do_doi_tra'])): ?>
                    <div class="info-row">
                        <span class="info-label">Lý do:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">
                            <?php echo nl2br(htmlspecialchars($order['ly_do_doi_tra'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Danh sách sản phẩm -->
        <div class="order-detail-section">
            <h5><i class="fas fa-box me-2"></i>Sản Phẩm Đã Đặt</h5>
            
            <?php foreach ($items as $item): ?>
            <div class="product-item">
                <?php if (!empty($item['hinhanh'])): ?>
                    <img src="../../public_files/images/<?php echo htmlspecialchars($item['hinhanh']); ?>" 
                         alt="<?php echo htmlspecialchars($item['tenhanghoa']); ?>" 
                         class="product-image"
                         onerror="this.src='../../public_files/images/no-image.png'">
                <?php else: ?>
                    <div class="product-image bg-secondary d-flex align-items-center justify-content-center">
                        <i class="fas fa-image text-white"></i>
                    </div>
                <?php endif; ?>
                
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($item['tenhanghoa'] ?: 'Sản phẩm #' . $item['ma_san_pham']); ?></div>
                    <div class="product-price">
                        Đơn giá: <?php echo number_format($item['gia'], 0, ',', '.'); ?>₫
                    </div>
                </div>
                
                <div class="text-end">
                    <div class="product-quantity">x<?php echo $item['so_luong']; ?></div>
                    <div class="fw-bold text-danger">
                        <?php echo number_format($item['gia'] * $item['so_luong'], 0, ',', '.'); ?>₫
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Tổng tiền -->
        <div class="total-section">
            <div class="total-row">
                <span>Tổng tiền hàng:</span>
                <span><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</span>
            </div>
            
            <div class="total-row">
                <span>Thuế VAT (10%):</span>
                <span><?php echo number_format($vatAmount, 0, ',', '.'); ?>₫</span>
            </div>
            
            <?php 
            // Lấy thông tin phương thức vận chuyển
            $shippingMethodName = isset($order['shipping_method_name']) ? $order['shipping_method_name'] : '';
            $estimatedDelivery = isset($order['estimated_delivery']) ? $order['estimated_delivery'] : '';
            ?>
            <div class="total-row">
                <span>
                    Phí vận chuyển:
                    <?php if (!empty($shippingMethodName)): ?>
                        <small style="opacity: 0.8;">(<?php echo htmlspecialchars($shippingMethodName); ?>)</small>
                    <?php endif; ?>
                </span>
                <span>
                    <?php if ($shippingFee == 0): ?>
                        <span class="badge bg-light text-dark">Miễn phí</span>
                    <?php else: ?>
                        <?php echo number_format($shippingFee, 0, ',', '.'); ?>₫
                    <?php endif; ?>
                    <?php if (!empty($estimatedDelivery)): ?>
                        <br><small style="opacity: 0.8;">Dự kiến: <?php echo htmlspecialchars($estimatedDelivery); ?></small>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php 
            // Hiển thị mã giảm giá nếu có
            $couponCode = isset($order['coupon_code']) ? $order['coupon_code'] : null;
            $couponDiscount = isset($order['coupon_discount']) ? floatval($order['coupon_discount']) : 0;
            
            if ($couponCode && $couponDiscount > 0): 
            ?>
            <div class="total-row" style="color: #90EE90;">
                <span>
                    <i class="fas fa-ticket-alt me-1"></i>
                    Mã giảm giá: <strong><?php echo htmlspecialchars($couponCode); ?></strong>
                </span>
                <span>-<?php echo number_format($couponDiscount, 0, ',', '.'); ?>₫</span>
            </div>
            <?php endif; ?>
            
            <div class="total-row total-final">
                <span>TỔNG THANH TOÁN:</span>
                <span><?php echo number_format($finalTotal, 0, ',', '.'); ?>₫</span>
            </div>
        </div>
    </div>
    
    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
    error_log("Error in get_order_detail.php: " . $e->getMessage());
}
?>
