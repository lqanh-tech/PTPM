<?php
/**
 * MoMo Return Handler - Xử lý khi user quay lại từ MoMo
 */

session_start();

// Include required files
require_once __DIR__ . '/../../../payment/MoMoPayment.php';
require_once __DIR__ . '/../mod/database.php';

// Log callback data
error_log('MoMo Return Callback: ' . json_encode($_GET));

// Lấy dữ liệu từ callback
$partnerCode = $_GET['partnerCode'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$requestId = $_GET['requestId'] ?? '';
$amount = $_GET['amount'] ?? '';
$orderInfo = $_GET['orderInfo'] ?? '';
$orderType = $_GET['orderType'] ?? '';
$transId = $_GET['transId'] ?? '';
$resultCode = $_GET['resultCode'] ?? '';
$message = $_GET['message'] ?? '';
$payType = $_GET['payType'] ?? '';
$responseTime = $_GET['responseTime'] ?? '';
$extraData = $_GET['extraData'] ?? '';
$signature = $_GET['signature'] ?? '';

// Decode extraData
$extraDataDecoded = json_decode(urldecode($extraData), true);
$orderCode = $extraDataDecoded['order_code'] ?? '';
$userId = $extraDataDecoded['user_id'] ?? '';
$shippingAddress = $extraDataDecoded['shipping_address'] ?? '';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thanh Toán MoMo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header <?php echo ($resultCode == '0') ? 'bg-success' : 'bg-danger'; ?> text-white text-center">
                        <h4 class="mb-0">
                            <?php if ($resultCode == '0'): ?>
                                <i class="fas fa-check-circle"></i> Thanh Toán Thành Công
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> Thanh Toán Thất Bại
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($resultCode == '0'): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check"></i> Giao dịch thành công!</h5>
                                <p>Cảm ơn bạn đã thanh toán. Đơn hàng của bạn đang được xử lý.</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Giao dịch thất bại!</h5>
                                <p><strong>Lỗi:</strong> <?php echo htmlspecialchars(urldecode($message)); ?></p>
                                <p><strong>Mã lỗi:</strong> <?php echo htmlspecialchars($resultCode); ?></p>
                            </div>
                        <?php endif; ?>

                        <h5>Thông Tin Giao Dịch</h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>Mã đơn hàng:</strong></td>
                                <td><?php echo htmlspecialchars($orderCode); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Số tiền:</strong></td>
                                <td><?php echo number_format($amount, 0, ',', '.'); ?> ₫</td>
                            </tr>
                            <tr>
                                <td><strong>Mã giao dịch MoMo:</strong></td>
                                <td><?php echo htmlspecialchars($transId); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Thời gian:</strong></td>
                                <td><?php echo date('d/m/Y H:i:s', intval($responseTime/1000)); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phương thức:</strong></td>
                                <td><?php echo htmlspecialchars($payType); ?></td>
                            </tr>
                        </table>

                        <?php if (!empty($shippingAddress)): ?>
                        <h5>Thông Tin Giao Hàng</h5>
                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars(urldecode($shippingAddress)); ?></p>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <?php if ($resultCode == '0'): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-spinner fa-spin"></i> Đang chuyển đến trang hóa đơn...
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                </div>
                                <div id="manualRedirectBtn" class="mt-3" style="display: none;">
                                    <p class="text-muted">Nếu không tự động chuyển hướng, vui lòng click nút bên dưới:</p>
                                    <a href="#" id="viewInvoiceBtn" class="btn btn-success btn-lg">
                                        <i class="fas fa-file-invoice"></i> Xem Hóa Đơn
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="checkout.php" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Thử Lại
                                </a>
                                <a href="giohangView.php" class="btn btn-secondary">
                                    <i class="fas fa-shopping-cart"></i> Về Giỏ Hàng
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Debug Info (chỉ hiển thị khi development) -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <pre><?php print_r($_GET); ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($resultCode == '0'): ?>
    <script>
        // Redirect ngay lập tức nếu thanh toán thành công
        let redirectUrl = null;
        let redirectTimer = null;
        let manualBtnTimer = null;
        
        function performRedirect() {
            if (redirectUrl) {
                console.log('Redirecting to:', redirectUrl);
                window.location.href = redirectUrl;
            } else {
                console.log('Waiting for redirect URL...');
                // Nếu sau 5 giây vẫn không có URL, hiển thị nút thủ công
                if (!redirectTimer) {
                    redirectTimer = setTimeout(function() {
                        console.log('Timeout - showing manual redirect button');
                        document.getElementById('manualRedirectBtn').style.display = 'block';
                        
                        // Set href cho nút
                        if (redirectUrl) {
                            document.getElementById('viewInvoiceBtn').href = redirectUrl;
                        } else {
                            document.getElementById('viewInvoiceBtn').href = 'giohangView.php';
                        }
                    }, 5000);
                }
            }
        }
        
        // Hiển thị nút thủ công sau 3 giây (backup)
        manualBtnTimer = setTimeout(function() {
            if (redirectUrl) {
                document.getElementById('manualRedirectBtn').style.display = 'block';
                document.getElementById('viewInvoiceBtn').href = redirectUrl;
            }
        }, 3000);
        
        // Thử redirect sau 1 giây
        setTimeout(performRedirect, 1000);
    </script>
    <?php endif; ?>
</body>
</html>

<?php
// Cập nhật trạng thái đơn hàng trong database nếu thanh toán thành công
if ($resultCode == '0') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Tìm đơn hàng theo orderId từ MoMo
        $findOrderSql = "SELECT id, ma_nguoi_dung, tong_tien, ma_don_hang_text FROM don_hang WHERE ma_don_hang_text = ? LIMIT 1";
        $findStmt = $conn->prepare($findOrderSql);
        $findStmt->execute([$orderId]);
        $order = $findStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $dbOrderId = $order['id'];
            $orderUserId = $order['ma_nguoi_dung'];
            $orderTotal = $order['tong_tien'];
            $orderCode = $order['ma_don_hang_text'];
            
            // Cập nhật trạng thái đơn hàng
            $updateSql = "UPDATE don_hang SET 
                          trang_thai_thanh_toan = 'paid',
                          trang_thai = 'approved',
                          ngay_cap_nhat = NOW()
                          WHERE id = ?";
            
            $stmt = $conn->prepare($updateSql);
            $stmt->execute([$dbOrderId]);
            
            // GHI NHẬN SỬ DỤNG COUPON (nếu có)
            if (isset($_SESSION['pending_coupon']) && !empty($_SESSION['pending_coupon'])) {
                try {
                    require_once __DIR__ . '/../mod/CouponCls.php';
                    $couponManager = new Coupon();
                    
                    $pendingCoupon = $_SESSION['pending_coupon'];
                    $couponResult = $couponManager->applyCoupon(
                        $pendingCoupon['code'], 
                        $dbOrderId, 
                        $orderUserId, 
                        $pendingCoupon['discount']
                    );
                    
                    if ($couponResult) {
                        error_log("MoMo Return - Coupon applied successfully: {$pendingCoupon['code']}, discount: {$pendingCoupon['discount']}");
                    } else {
                        error_log("MoMo Return - Failed to apply coupon: {$pendingCoupon['code']}");
                    }
                    
                    // Xóa coupon khỏi session sau khi đã sử dụng
                    unset($_SESSION['pending_coupon']);
                    unset($_SESSION['applied_coupon']);
                    unset($_SESSION['coupon_discount']);
                    unset($_SESSION['coupon_data']);
                    
                } catch (Exception $couponError) {
                    error_log("MoMo Return - Coupon apply error: " . $couponError->getMessage());
                }
            }
            
            // GỬI THÔNG BÁO ĐẾN KHÁCH HÀNG
            try {
                require_once __DIR__ . '/../mod/CustomerNotificationManager.php';
                $notificationManager = new CustomerNotificationManager();
                
                // Gửi thông báo xác nhận thanh toán
                $notificationManager->notifyPaymentConfirmed($dbOrderId, $orderUserId);
                
                error_log("MoMo Return - Notification sent for order $dbOrderId to user $orderUserId");
            } catch (Exception $notifError) {
                error_log("MoMo Return - Error sending notification: " . $notifError->getMessage());
            }
            
            error_log("MoMo Return - Order updated successfully: $orderId (DB ID: $dbOrderId)");
            
            // LƯU Ý: Tồn kho đã được trừ khi tạo đơn hàng trong momo_payment.php
            // Ở đây chỉ cần xóa giỏ hàng và cập nhật trạng thái
            error_log("MoMo Return - Inventory was already deducted when order was created");
            
            // Xóa các sản phẩm đã thanh toán khỏi giỏ hàng
            if (!empty($userId)) {
                require_once __DIR__ . '/../mod/giohangCls.php';
                $giohang = new GioHang();
                
                // Lấy tổng số sản phẩm trong giỏ TRƯỚC KHI xóa
                $cartBeforeSql = "SELECT COUNT(*) as total FROM tbl_giohang WHERE user_id = ?";
                $cartBeforeStmt = $conn->prepare($cartBeforeSql);
                $cartBeforeStmt->execute([$userId]);
                $cartBefore = $cartBeforeStmt->fetch(PDO::FETCH_ASSOC);
                $totalBeforeRemoval = $cartBefore['total'] ?? 0;
                
                error_log("MoMo Return - Cart items BEFORE removal: $totalBeforeRemoval for user: $userId");
                
                // QUAN TRỌNG: Ưu tiên lấy danh sách sản phẩm từ session (đã được lưu khi tạo đơn hàng)
                // Điều này đảm bảo chỉ xóa đúng các sản phẩm đã thanh toán
                $orderItems = [];
                
                if (isset($_SESSION['pending_order']['purchased_product_ids']) && 
                    is_array($_SESSION['pending_order']['purchased_product_ids'])) {
                    $orderItems = $_SESSION['pending_order']['purchased_product_ids'];
                    error_log("MoMo Return - Using purchased_product_ids from session: " . implode(', ', $orderItems));
                } else {
                    // Fallback: Lấy từ chi_tiet_don_hang (chỉ khi session không có)
                    error_log("MoMo Return - Session purchased_product_ids not found, falling back to database");
                    $orderItemsSql = "SELECT ma_san_pham FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                    $orderItemsStmt = $conn->prepare($orderItemsSql);
                    $orderItemsStmt->execute([$dbOrderId]);
                    $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_COLUMN);
                }
                
                error_log("MoMo Return - Products to remove from cart: " . implode(', ', $orderItems));
                
                // Chỉ xóa các sản phẩm đã thanh toán
                $removedCount = 0;
                foreach ($orderItems as $productId) {
                    if ($giohang->removeFromCart($productId)) {
                        $removedCount++;
                        error_log("MoMo Return - Successfully removed product ID: $productId from cart");
                    } else {
                        error_log("MoMo Return - Failed to remove product ID: $productId from cart (may not exist)");
                    }
                }
                
                // Kiểm tra số sản phẩm còn lại trong giỏ SAU KHI xóa
                $cartAfterSql = "SELECT COUNT(*) as total FROM tbl_giohang WHERE user_id = ?";
                $cartAfterStmt = $conn->prepare($cartAfterSql);
                $cartAfterStmt->execute([$userId]);
                $cartAfter = $cartAfterStmt->fetch(PDO::FETCH_ASSOC);
                $totalAfterRemoval = $cartAfter['total'] ?? 0;
                
                error_log("MoMo Return - Cart items AFTER removal: $totalAfterRemoval for user: $userId");
                error_log("MoMo Return - Removed $removedCount purchased items from cart (Expected: " . count($orderItems) . ")");
                
                // Xóa thông tin pending_order khỏi session sau khi xử lý xong
                unset($_SESSION['pending_order']);
            }
            
            // Set session để order_success.php không redirect về giỏ hàng
            $_SESSION['payment_success'] = true;
            $_SESSION['order_id'] = $dbOrderId;
            
            // Redirect đến trang hóa đơn - Sử dụng JavaScript để set URL
            echo "<script>
                redirectUrl = 'order_success.php?order_id={$dbOrderId}';
                console.log('Order processed successfully. Redirect URL set:', redirectUrl);
                // Redirect ngay lập tức
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, 1500);
            </script>";
            
            // Flush output để đảm bảo JavaScript được gửi
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            
        } else {
            error_log("Order not found in database: $orderId");
            echo "<script>
                redirectUrl = 'giohangView.php';
                console.log('Order not found. Redirecting to cart.');
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, 1500);
            </script>";
            
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        
    } catch (Exception $e) {
        error_log("Error updating order: " . $e->getMessage());
        echo "<script>
            redirectUrl = 'giohangView.php';
            console.log('Error processing order:', '<?php echo addslashes($e->getMessage()); ?>');
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 1500);
        </script>";
        
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
} else {
    // Nếu thanh toán thất bại, không cần redirect
    echo "<script>
        console.log('Payment failed. Result code:', '<?php echo $resultCode; ?>');
    </script>";
}
?>