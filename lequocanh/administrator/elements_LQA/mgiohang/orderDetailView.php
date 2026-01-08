<?php

require_once __DIR__ . '/../mod/sessionManager.php';

SessionManager::start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../../userLogin.php');
    exit();
}

require_once '../mod/database.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if ($orderId <= 0) {
    $_SESSION['error_message'] = 'ID đơn hàng không hợp lệ!';
    header('Location: giohangView.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

if (isset($_SESSION['ADMIN'])) {

    $sql = "SELECT * FROM don_hang WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
} else {

    $sql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId, $username]);
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error_message'] = 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này!';
    header('Location: giohangView.php');
    exit();
}

$itemsSql = "SELECT oi.*, h.tenhanghoa 
             FROM chi_tiet_don_hang oi
             JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
             WHERE oi.ma_don_hang = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public_files/toast-notification.css">
    <style>
        body { background-color: #f8f9fa; }
        .order-container { max-width: 1000px; margin: 30px auto; }
        .card { border-radius: 15px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1); }
        .card-header { border-radius: 15px 15px 0 0 !important; }
        .info-label { font-weight: 600; color: #495057; }
        .badge { padding: 8px 12px; font-size: 0.9rem; }
        .product-table { margin-top: 20px; }
        .total-row { background-color: #f8f9fa; font-size: 1.1rem; }
        .btn-back { margin-right: 10px; }
        @media print {
            .no-print { display: none; }
            body { background-color: white; }
        }
    </style>
</head>
<body>
    <div class="order-container">
        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Chi tiết đơn hàng #<?php echo $order['id']; ?>
                    </h4>
                    <div class="no-print">
                        <a href="giohangView.php" class="btn btn-light btn-sm btn-back">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                        <button onclick="window.print()" class="btn btn-light btn-sm">
                            <i class="fas fa-print me-1"></i>In
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Thông tin đơn hàng -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Thông tin đơn hàng</h5>
                        <div class="mb-2">
                            <span class="info-label">Mã đơn hàng:</span>
                            <span class="ms-2"><?php echo htmlspecialchars($order['ma_don_hang_text']); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="info-label">Ngày đặt:</span>
                            <span class="ms-2"><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="info-label">Trạng thái:</span>
                            <span class="ms-2">
                                <?php
                                switch ($order['trang_thai']) {
                                    case 'pending':
                                        echo '<span class="badge bg-warning text-dark">Chờ xác nhận</span>';
                                        break;
                                    case 'approved':
                                        echo '<span class="badge bg-info">Đã duyệt - Đang giao</span>';
                                        break;
                                    case 'delivered':
                                        echo '<span class="badge bg-primary">Đã giao hàng</span>';
                                        break;
                                    case 'completed':
                                        echo '<span class="badge bg-success">Hoàn tất</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="badge bg-danger">Đã hủy</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">Không xác định</span>';
                                }
                                ?>
                            </span>
                        </div>
                        <?php if (isset($order['trang_thai_thanh_toan'])): ?>
                        <div class="mb-2">
                            <span class="info-label">Thanh toán:</span>
                            <span class="ms-2">
                                <?php
                                switch ($order['trang_thai_thanh_toan']) {
                                    case 'paid':
                                        echo '<span class="badge bg-success">Đã thanh toán</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="badge bg-warning text-dark">Chưa thanh toán</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($order['trang_thai_thanh_toan']) . '</span>';
                                }
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($order['phuong_thuc_thanh_toan'])): ?>
                        <div class="mb-2">
                            <span class="info-label">Phương thức thanh toán:</span>
                            <span class="ms-2">
                                <?php
                                switch ($order['phuong_thuc_thanh_toan']) {
                                    case 'cod': echo 'COD (Thanh toán khi nhận hàng)'; break;
                                    case 'momo': echo 'MoMo'; break;
                                    case 'bank_transfer': echo 'Chuyển khoản ngân hàng'; break;
                                    default: echo htmlspecialchars($order['phuong_thuc_thanh_toan']);
                                }
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Thông tin khách hàng</h5>
                        <div class="mb-2">
                            <span class="info-label">Tài khoản:</span>
                            <span class="ms-2"><?php echo htmlspecialchars($order['ma_nguoi_dung']); ?></span>
                        </div>
                        <?php if (!empty($order['dia_chi_giao_hang'])): ?>
                        <div class="mb-2">
                            <span class="info-label">Địa chỉ giao hàng:</span>
                            <div class="mt-2 p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($order['dia_chi_giao_hang'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>

                <!-- Danh sách sản phẩm -->
                <h5 class="mb-3"><i class="fas fa-shopping-bag me-2 text-primary"></i>Danh sách sản phẩm</h5>
                <div class="table-responsive product-table">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Thành tiền</th>
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
                                <td><?php echo htmlspecialchars($item['tenhanghoa']); ?></td>
                                <td class="text-end"><?php echo number_format($item['gia'], 0, ',', '.'); ?> ₫</td>
                                <td class="text-center"><?php echo $item['so_luong']; ?></td>
                                <td class="text-end"><?php echo number_format($itemTotal, 0, ',', '.'); ?> ₫</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php

                            $taxAmount = isset($order['thue']) ? floatval($order['thue']) : 0;
                            $shippingFee = isset($order['phi_van_chuyen']) ? floatval($order['phi_van_chuyen']) : 0;
                            
                            $shippingMethodName = isset($order['shipping_method_name']) ? $order['shipping_method_name'] : '';
                            $estimatedDelivery = isset($order['estimated_delivery']) ? $order['estimated_delivery'] : '';
                            
                            $couponCode = isset($order['coupon_code']) ? $order['coupon_code'] : null;
                            $couponDiscount = isset($order['coupon_discount']) ? floatval($order['coupon_discount']) : 0;
                            
                            $hasDetailedBreakdown = ($taxAmount > 0 || $shippingFee > 0);
                            ?>
                            
                            <tr>
                                <td colspan="3" class="text-end">Tạm tính:</td>
                                <td class="text-end"><?php echo number_format($subtotal, 0, ',', '.'); ?> ₫</td>
                            </tr>
                            
                            <?php if ($hasDetailedBreakdown): ?>
                                <?php if ($taxAmount > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end">Thuế VAT (10%):</td>
                                    <td class="text-end"><?php echo number_format($taxAmount, 0, ',', '.'); ?> ₫</td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($shippingFee > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end">
                                        Phí vận chuyển:
                                        <?php if (!empty($shippingMethodName)): ?>
                                            <br><small class="text-muted">(<?php echo htmlspecialchars($shippingMethodName); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format($shippingFee, 0, ',', '.'); ?> ₫
                                        <?php if (!empty($estimatedDelivery)): ?>
                                            <br><small class="text-success">Dự kiến: <?php echo htmlspecialchars($estimatedDelivery); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($couponCode && $couponDiscount > 0): ?>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end">
                                        <i class="fas fa-ticket-alt me-1 text-success"></i>
                                        Mã giảm giá: <strong><?php echo htmlspecialchars($couponCode); ?></strong>
                                    </td>
                                    <td class="text-end text-success">
                                        -<?php echo number_format($couponDiscount, 0, ',', '.'); ?> ₫
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-end">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Đơn hàng cũ - chưa có chi tiết thuế/phí vận chuyển
                                        </small>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                            
                            <tr class="total-row">
                                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td class="text-end">
                                    <strong class="text-danger fs-5">
                                        <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Ghi chú và trạng thái -->
                <?php if ($order['trang_thai'] == 'pending'): ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Đơn hàng của bạn đang chờ xác nhận. Chúng tôi sẽ liên hệ với bạn sớm nhất có thể.
                </div>
                <?php elseif ($order['trang_thai'] == 'approved'): ?>
                <div class="alert alert-success mt-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Thông báo:</strong> Đơn hàng của bạn đã được xác nhận và đang được xử lý.
                    <?php if ($order['phuong_thuc_thanh_toan'] == 'cod'): ?>
                    <br><small>Vui lòng chuẩn bị <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>₫ khi nhận hàng.</small>
                    <?php endif; ?>
                </div>
                <?php elseif ($order['trang_thai'] == 'delivered'): ?>
                <div class="alert alert-warning mt-4">
                    <i class="fas fa-truck me-2"></i>
                    <strong>Đơn hàng đã được giao!</strong>
                    <?php if (!isset($_SESSION['ADMIN'])): ?>
                    <p class="mb-2 mt-2">
                        <?php if ($order['phuong_thuc_thanh_toan'] == 'cod'): ?>
                            Nếu bạn đã nhận được hàng và thanh toán, vui lòng xác nhận bên dưới.
                        <?php else: ?>
                            Nếu bạn đã nhận được hàng, vui lòng xác nhận bên dưới.
                        <?php endif; ?>
                    </p>
                    <form action="confirmDeliveryAct.php" method="POST" class="d-inline" onsubmit="return confirm('Xác nhận bạn đã nhận được hàng?');">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="action" value="customer_confirm_received">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php if ($order['phuong_thuc_thanh_toan'] == 'cod'): ?>
                                Xác nhận đã nhận hàng & thanh toán
                            <?php else: ?>
                                Xác nhận đã nhận hàng
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php elseif ($order['trang_thai'] == 'completed'): ?>
                <div class="alert alert-success mt-4">
                    <i class="fas fa-check-double me-2"></i>
                    <strong>Đơn hàng đã hoàn tất!</strong> Cảm ơn bạn đã mua hàng.
                    <?php if (!empty($order['ngay_nhan_hang'])): ?>
                    <br><small>Ngày nhận hàng: <?php echo date('d/m/Y H:i', strtotime($order['ngay_nhan_hang'])); ?></small>
                    <?php endif; ?>
                </div>
                <?php elseif ($order['trang_thai'] == 'cancelled'): ?>
                <div class="alert alert-danger mt-4">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Thông báo:</strong> Đơn hàng này đã bị hủy.
                </div>
                <?php endif; ?>
                
                <!-- Thông tin đổi trả -->
                <?php 
                $returnStatus = isset($order['trang_thai_doi_tra']) ? $order['trang_thai_doi_tra'] : 'none';
                if ($returnStatus != 'none'): 
                ?>
                <div class="alert alert-<?php 
                    echo $returnStatus == 'requested' ? 'warning' : 
                        ($returnStatus == 'approved' ? 'success' : 'danger'); 
                ?> mt-4">
                    <h6><i class="fas fa-exchange-alt me-2"></i>Trạng thái đổi/trả hàng</h6>
                    <p class="mb-0">
                        <strong>Trạng thái:</strong> 
                        <?php
                        switch ($returnStatus) {
                            case 'requested':
                                echo '<span class="badge bg-warning text-dark">Đang chờ xử lý</span>';
                                break;
                            case 'approved':
                                echo '<span class="badge bg-success">Đã chấp nhận</span>';
                                break;
                            case 'rejected':
                                echo '<span class="badge bg-danger">Đã từ chối</span>';
                                break;
                        }
                        ?>
                    </p>
                    <?php if (!empty($order['ly_do_doi_tra'])): ?>
                        <p class="mb-0 mt-2"><strong>Lý do:</strong> <?php echo htmlspecialchars($order['ly_do_doi_tra']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['ngay_yeu_cau_doi_tra'])): ?>
                        <p class="mb-0 mt-1"><small class="text-muted">Ngày yêu cầu: <?php echo date('d/m/Y H:i', strtotime($order['ngay_yeu_cau_doi_tra'])); ?></small></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Nút hành động -->
                <div class="mt-4 no-print">
                    <a href="giohangView.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Quay lại giỏ hàng
                    </a>
                    
                    <!-- Nút Yêu cầu đổi trả cho User -->
                    <?php if (!isset($_SESSION['ADMIN']) && $order['trang_thai'] == 'approved' && $returnStatus == 'none'): ?>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#returnRequestModal">
                            <i class="fas fa-exchange-alt me-2"></i>Yêu cầu đổi/trả hàng
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['ADMIN'])): ?>
                    <a href="../../index.php?req=don_hang&action=view&id=<?php echo $order['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-list me-2"></i>Quản lý đơn hàng
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Widget đánh giá sản phẩm (chỉ hiển thị cho khách hàng khi đơn đã duyệt) -->
                <?php if (!isset($_SESSION['ADMIN']) && ($order['trang_thai'] == 'approved' || $order['trang_thai_thanh_toan'] == 'paid')): ?>
                    <div class="mt-5 no-print">
                        <hr>
                        <h4 class="mb-4"><i class="fas fa-star text-warning"></i> Đánh giá sản phẩm</h4>
                        <?php 
                        $orderId = $order['id'];
                        include __DIR__ . '/../../../components/product_review_widget.php'; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Yêu cầu đổi trả -->
    <div class="modal fade" id="returnRequestModal" tabindex="-1" aria-labelledby="returnRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnRequestModalLabel">
                        <i class="fas fa-exchange-alt me-2"></i>Yêu cầu đổi/trả hàng
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="returnRequestHandler.php" method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="returnReason" class="form-label">Lý do đổi/trả: <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="returnReason" name="reason" rows="4" required 
                                      placeholder="Vui lòng mô tả chi tiết lý do bạn muốn đổi/trả hàng..."></textarea>
                            <div class="form-text">Ví dụ: Sản phẩm bị lỗi, không đúng mô tả, muốn đổi size/màu...</div>
                        </div>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Lưu ý quan trọng:</h6>
                            <ul class="mb-0 small">
                                <li>Yêu cầu của bạn sẽ được Ban quản trị xem xét trong vòng 24-48 giờ</li>
                                <li>Sản phẩm cần còn nguyên vẹn, chưa qua sử dụng</li>
                                <li>Vui lòng giữ nguyên bao bì, hóa đơn và phụ kiện đi kèm</li>
                                <li>Nếu được chấp nhận, bạn sẽ nhận được hướng dẫn gửi trả hàng</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Đóng
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public_files/toast-notification.js"></script>
</body>
</html>
