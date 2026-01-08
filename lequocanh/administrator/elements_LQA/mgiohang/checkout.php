<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/hanghoaCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';
require_once '../../elements_LQA/mod/database.php';

$giohang = new GioHang();
$hanghoa = new hanghoa();

if (!$giohang->canUseCart()) {
    if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {

        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../../userLogin.php');
    } else {

        header('Location: ../../index.php');
    }
    exit();
}

if (!isset($_POST['selected_products']) || empty($_POST['selected_products'])) {

    if (isset($_GET['test']) && $_GET['test'] == '1') {
        $_POST['selected_products'] = json_encode([
            ['productId' => 1, 'quantity' => 1]
        ]);
    } else {

        header('Location: giohangView.php');
        exit();
    }
} else {
    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!csrf_validate($token)) {
            Logger::warning("CSRF validation failed in checkout", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            die('CSRF token validation failed. Please try again.');
        }
    }
}

$selectedProducts = json_decode($_POST['selected_products'], true);

$giohang = new GioHang();
$hanghoa = new hanghoa();
$tonkho = new MTonKho();

$userAddress = '';
if (isset($_SESSION['USER'])) {
    require_once '../../elements_LQA/mod/userCls.php';
    $userObj = new user();
    $currentUser = $userObj->UserGetbyUsername($_SESSION['USER']);
    if ($currentUser && !empty($currentUser->diachi)) {
        $userAddress = $currentUser->diachi;
    }
}

$orderDetails = [];
$totalAmount = 0;

foreach ($selectedProducts as $product) {
    $productId = $product['productId'];
    $quantity = $product['quantity'];

    $productInfo = $hanghoa->HanghoaGetbyId($productId);

    if (!$productInfo) {

        continue;
    }

    $productStatus = $hanghoa->getProductStatusValue($productId);
    if ($productStatus != 1) {

        $statusText = ($productStatus == 2) ? 'ngừng bán' : 'hết hàng';
        $_SESSION['checkout_error'] = 'Sản phẩm "' . $productInfo->tenhanghoa . '" đã ' . $statusText . '. Vui lòng cập nhật giỏ hàng của bạn.';
        header('Location: giohangView.php');
        exit();
    }

    $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($productId);

    if (!$tonkhoInfo || $tonkhoInfo->soLuong < $quantity) {

        $_SESSION['checkout_error'] = 'Sản phẩm "' . $productInfo->tenhanghoa . '" không đủ số lượng trong kho.';
        header('Location: giohangView.php');
        exit();
    }

    $hinhanh = $hanghoa->GetHinhAnhById($productInfo->hinhanh);
    $imageSrc = "";

    $imageSrc = "https://via.placeholder.com/80x80/cccccc/666666?text=No+Image";

    $subtotal = $productInfo->giathamkhao * $quantity;
    $totalAmount += $subtotal;

    $orderDetails[] = [
        'id' => $productId,
        'name' => $productInfo->tenhanghoa,
        'price' => $productInfo->giathamkhao,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
        'image' => $imageSrc
    ];
}

$vatRate = 0.10;
$vatAmount = $totalAmount * $vatRate;

$shippingFee = 0;

$finalTotal = $totalAmount + $vatAmount + $shippingFee;

$_SESSION['order_details'] = $orderDetails;
$_SESSION['subtotal'] = $totalAmount;
$_SESSION['vat_rate'] = $vatRate;
$_SESSION['vat_amount'] = $vatAmount;
$_SESSION['shipping_fee'] = $shippingFee;
$_SESSION['total_amount'] = $finalTotal;

$db = Database::getInstance();
$conn = $db->getConnection();

$checkTableSql = "SHOW TABLES LIKE 'cau_hinh_thanh_toan'";
$checkTableStmt = $conn->prepare($checkTableSql);
$checkTableStmt->execute();

$paymentConfig = [
    'ten_ngan_hang' => '',
    'so_tai_khoan' => '',
    'ten_tai_khoan' => ''
];

if ($checkTableStmt->rowCount() > 0) {

    $configSql = "SELECT * FROM cau_hinh_thanh_toan LIMIT 1";
    $configStmt = $conn->prepare($configSql);
    $configStmt->execute();

    if ($configStmt->rowCount() > 0) {
        $config = $configStmt->fetch(PDO::FETCH_ASSOC);

        $paymentConfig = [
            'bank_name' => $config['ten_ngan_hang'],
            'account_number' => $config['so_tai_khoan'],
            'account_name' => $config['ten_tai_khoan']
        ];
    }
}

$orderCode = 'ORDER' . time() . rand(1000, 9999);
$_SESSION['order_code'] = $orderCode;

$transferContent = $orderCode;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../stylecss_LQA/mycss.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .payment-methods {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .payment-method {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method.active {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .payment-method img {
            height: 40px;
            margin-bottom: 10px;
        }

        .qr-container {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background-color: #f8f9fa;
        }

        .qr-code {
            max-width: 300px;
            margin: 0 auto;
        }

        .bank-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 10px;
        }

        .shipping-result.error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

    </style>
</head>

<body>
    <div class="checkout-container">
        <h2 class="mb-4">Thanh toán đơn hàng</h2>

        <!-- Thông tin địa chỉ giao hàng -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Địa chỉ giao hàng</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="shipping-address" class="form-label">Địa chỉ nhận hàng</label>
                    <textarea class="form-control" id="shipping-address" rows="3"
                        placeholder="Nhập địa chỉ giao hàng"><?php echo htmlspecialchars($userAddress); ?></textarea>
                    <div class="form-text">Vui lòng nhập địa chỉ đầy đủ để chúng tôi giao hàng đến bạn.</div>
                </div>
            </div>
        </div>

        <!-- Phương thức vận chuyển V2 - TÍNH PHÍ ĐỘNG -->
        <?php include 'shipping_method_selector_v2.php'; ?>

        <!-- Mã giảm giá (Coupon) -->
        <?php include 'coupon_input_component.php'; ?>

        <!-- Thông tin đơn hàng -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <p><strong>Mã đơn hàng:</strong> <?php echo $orderCode; ?></p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderDetails as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $item['image']; ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image me-3">
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> ₫</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end">Tạm tính:</td>
                            <td id="subtotal-display"><?php echo number_format($totalAmount, 0, ',', '.'); ?> ₫</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">Thuế VAT (10%):</td>
                            <td id="vat-display"><?php echo number_format($vatAmount, 0, ',', '.'); ?> ₫</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">
                                Phí vận chuyển:
                                <small class="text-muted d-block" id="shipping-status">Xem phương thức vận chuyển</small>
                            </td>
                            <td id="shipping-display">
                                <span id="shipping-fee-value">0 ₫</span>
                            </td>
                        </tr>
                        <tr id="coupon-discount-row" style="display: <?php echo (isset($_SESSION['coupon_discount']) && $_SESSION['coupon_discount'] > 0) ? 'table-row' : 'none'; ?>;">
                            <td colspan="3" class="text-end text-success">
                                <i class="fas fa-ticket-alt me-1"></i>Giảm giá (Coupon):
                            </td>
                            <td class="text-success" id="coupon-discount-display">
                                -<?php echo number_format($_SESSION['coupon_discount'] ?? 0, 0, ',', '.'); ?> ₫
                            </td>
                        </tr>
                        <tr class="table-active">
                            <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                            <td><strong class="text-danger fs-5" id="final-total-display"><?php echo number_format($finalTotal - ($_SESSION['coupon_discount'] ?? 0), 0, ',', '.'); ?> ₫</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Phương thức thanh toán -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Phương thức thanh toán</h5>
            </div>
            <div class="card-body">
                <div class="payment-methods">
                    <div class="payment-method" id="momo-payment">
                        <img src="https://developers.momo.vn/v3/assets/images/square-logo.svg" alt="MoMo" style="height: 40px; margin-bottom: 10px;">
                        <h5>Thanh toán MoMo</h5>
                        <p class="text-muted">Thanh toán nhanh chóng và an toàn qua ví MoMo</p>
                    </div>
                    <div class="payment-method active" id="bank-transfer">
                        <i class="fas fa-university" style="font-size: 2rem; color: #0d6efd; margin-bottom: 10px;"></i>
                        <h5>Chuyển khoản ngân hàng</h5>
                        <p class="text-muted">Quét mã QR để thanh toán qua ứng dụng ngân hàng</p>
                    </div>
                    <div class="payment-method" id="cod-payment">
                        <i class="fas fa-truck" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                        <h5>Thanh toán khi nhận hàng (COD)</h5>
                        <p class="text-muted">Thanh toán bằng tiền mặt khi nhận hàng</p>
                    </div>
                </div>

                <!-- Thông tin thanh toán MoMo -->
                <div class="qr-container" id="momo-payment-details" style="display: none;">
                    <h5>Thanh toán qua MoMo</h5>
                    <div class="text-center">
                        <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="height: 60px; margin-bottom: 20px;">
                        <p>Bạn sẽ được chuyển hướng đến trang thanh toán MoMo để hoàn tất giao dịch.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Sau khi thanh toán thành công trên MoMo, bạn sẽ được tự động chuyển về trang xác nhận đơn hàng.
                        </div>
                    </div>
                </div>

                <!-- Thông tin thanh toán qua VietQR -->
                <div class="qr-container" id="bank-transfer-details">
                    <?php if (!empty($paymentConfig['account_number']) && !empty($paymentConfig['bank_name'])): ?>
                        <h5>Quét mã QR để thanh toán</h5>
                        <div class="qr-code">
                            <?php

                            $bankCode = '';
                            $amount = $totalAmount;
                            $description = $transferContent;

                            switch (strtoupper($paymentConfig['bank_name'])) {
                                case 'VIETCOMBANK':
                                case 'VCB':
                                    $bankCode = 'VCB';
                                    break;
                                case 'AGRIBANK':
                                    $bankCode = 'AGR';
                                    break;
                                case 'VIETINBANK':
                                case 'VIETTINBANK':
                                    $bankCode = 'ICB';
                                    break;
                                case 'BIDV':
                                    $bankCode = 'BIDV';
                                    break;
                                case 'TECHCOMBANK':
                                case 'TCB':
                                    $bankCode = 'TCB';
                                    break;
                                case 'MB':
                                case 'MBB':
                                    $bankCode = 'MB';
                                    break;
                                case 'ACB':
                                    $bankCode = 'ACB';
                                    break;
                                case 'TPB':
                                case 'TPBANK':
                                    $bankCode = 'TPB';
                                    break;
                                default:
                                    $bankCode = '';
                            }

                            if (empty($bankCode)) {
                                $bankCode = 'TCB';
                            }

                            $encodedAccountName = urlencode($paymentConfig['account_name']);
                            $encodedDescription = urlencode($description);

                            $vietQrUrl = "https://img.vietqr.io/image/{$bankCode}-{$paymentConfig['account_number']}-compact.png?amount={$amount}&addInfo={$encodedDescription}&accountName={$encodedAccountName}";

                            error_log("VietQR URL: " . $vietQrUrl);
                            ?>
                            <img src="<?php echo $vietQrUrl; ?>" alt="QR Code" class="img-fluid">
                        </div>
                        <div class="bank-info mt-3">
                            <p><strong>Ngân hàng:</strong> <?php echo htmlspecialchars($paymentConfig['bank_name']); ?></p>
                            <p><strong>Số tài khoản:</strong>
                                <?php echo htmlspecialchars($paymentConfig['account_number']); ?></p>
                            <p><strong>Chủ tài khoản:</strong>
                                <?php echo htmlspecialchars($paymentConfig['account_name']); ?></p>
                            <p><strong>Nội dung chuyển khoản:</strong> <?php echo $transferContent; ?></p>
                        </div>
                        <div class="alert alert-info mt-3">
                            <p>Sau khi thanh toán, vui lòng nhấn nút "Xác nhận đã thanh toán" bên dưới.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>Chưa có thông tin tài khoản ngân hàng. Vui lòng liên hệ quản trị viên để cập nhật.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin thanh toán COD -->
                <div class="qr-container" id="cod-payment-details" style="display: none;">
                    <h5>Thanh toán khi nhận hàng (COD)</h5>
                    <div class="text-center">
                        <i class="fas fa-truck" style="font-size: 80px; color: #28a745; margin-bottom: 20px;"></i>
                        <p class="lead">Bạn sẽ thanh toán bằng tiền mặt khi nhận hàng</p>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>Ưu điểm của COD:</h6>
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-check me-2"></i>Không cần thanh toán trước</li>
                                <li><i class="fas fa-check me-2"></i>Kiểm tra hàng trước khi thanh toán</li>
                                <li><i class="fas fa-check me-2"></i>An toàn và tiện lợi</li>
                            </ul>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Vui lòng chuẩn bị đủ tiền mặt khi nhận hàng.
                            Số tiền cần thanh toán: <strong><?php echo number_format($totalAmount, 0, ',', '.'); ?> đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nút xác nhận thanh toán -->
        <div class="d-flex justify-content-between">
            <a href="giohangView.php" class="btn btn-secondary">Quay lại giỏ hàng</a>
            <button id="confirmPaymentBtn" class="btn btn-primary">Xác nhận đã thanh toán</button>
        </div>

        <!-- Thông báo đang xử lý -->
        <div id="processingPayment" class="mt-3 text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang xử lý...</span>
            </div>
            <p class="mt-2">Đang xử lý thanh toán, vui lòng đợi...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
            const processingPayment = document.getElementById('processingPayment');
            const momoPaymentMethod = document.getElementById('momo-payment');
            const bankTransferMethod = document.getElementById('bank-transfer');
            const codPaymentMethod = document.getElementById('cod-payment');
            const momoDetails = document.getElementById('momo-payment-details');
            const bankDetails = document.getElementById('bank-transfer-details');
            const codDetails = document.getElementById('cod-payment-details');

            let selectedPaymentMethod = 'bank-transfer';

            window.currentShippingFee = 0;
            window.currentVatAmount = <?php echo $vatAmount; ?>;
            window.currentSubtotal = <?php echo $totalAmount; ?>;
            
            let currentShippingFee = window.currentShippingFee;
            let currentVatAmount = window.currentVatAmount;
            let currentSubtotal = window.currentSubtotal;

            document.addEventListener('shippingMethodChanged', function(e) {
                const { method, fee } = e.detail;
                console.log('Shipping method changed:', method, 'Fee:', fee);
                
                window.currentShippingFee = fee;
                currentShippingFee = fee;
                
                window.updateFinalTotal();
            });

            window.updateFinalTotal = function() {
                const couponDiscount = window.currentCouponDiscount || 0;
                const finalTotal = window.currentSubtotal + window.currentVatAmount + window.currentShippingFee - couponDiscount;
                document.getElementById('final-total-display').textContent = 
                    new Intl.NumberFormat('vi-VN').format(finalTotal) + ' ₫';
                
                const couponRow = document.getElementById('coupon-discount-row');
                const couponDisplay = document.getElementById('coupon-discount-display');
                if (couponRow && couponDisplay) {
                    if (couponDiscount > 0) {
                        couponRow.style.display = 'table-row';
                        couponDisplay.textContent = '-' + new Intl.NumberFormat('vi-VN').format(couponDiscount) + ' ₫';
                    } else {
                        couponRow.style.display = 'none';
                    }
                }
            };
            
            const updateFinalTotal = window.updateFinalTotal;

            momoPaymentMethod.addEventListener('click', function() {
                console.log('🚀 MoMo payment method clicked!');

                const shippingAddress = document.getElementById('shipping-address').value.trim();
                if (!shippingAddress) {
                    alert('Vui lòng nhập địa chỉ giao hàng trước khi thanh toán!');
                    return;
                }

                momoPaymentMethod.classList.add('active');
                bankTransferMethod.classList.remove('active');
                momoDetails.style.display = 'block';
                bankDetails.style.display = 'none';
                selectedPaymentMethod = 'momo';
                confirmPaymentBtn.textContent = 'Đang xử lý MoMo...';
                confirmPaymentBtn.disabled = true;

                processMoMoPayment(shippingAddress);
            });

            bankTransferMethod.addEventListener('click', function() {

                bankTransferMethod.classList.add('active');
                momoPaymentMethod.classList.remove('active');
                codPaymentMethod.classList.remove('active');
                bankDetails.style.display = 'block';
                momoDetails.style.display = 'none';
                codDetails.style.display = 'none';
                selectedPaymentMethod = 'bank-transfer';
                confirmPaymentBtn.textContent = 'Xác nhận đã thanh toán';
            });

            codPaymentMethod.addEventListener('click', function() {

                codPaymentMethod.classList.add('active');
                momoPaymentMethod.classList.remove('active');
                bankTransferMethod.classList.remove('active');
                codDetails.style.display = 'block';
                momoDetails.style.display = 'none';
                bankDetails.style.display = 'none';
                selectedPaymentMethod = 'cod';
                confirmPaymentBtn.textContent = 'Xác nhận đặt hàng COD';
            });

            confirmPaymentBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('🔥 BUTTON CLICKED! Payment method:', selectedPaymentMethod);

                if (selectedPaymentMethod === 'momo') {
                    console.log('✅ MoMo payment selected!');

                    const shippingAddress = document.getElementById('shipping-address').value.trim();
                    if (!shippingAddress) {
                        alert('Vui lòng nhập địa chỉ giao hàng!');
                        return;
                    }

                    processMoMoPayment(shippingAddress);
                    return;
                }

                if (selectedPaymentMethod === 'cod') {
                    console.log('✅ COD payment selected!');

                    const shippingAddress = document.getElementById('shipping-address').value.trim();
                    if (!shippingAddress) {
                        alert('Vui lòng nhập địa chỉ giao hàng!');
                        return;
                    }

                    processCODPayment(shippingAddress);
                    return;
                }

                const shippingAddress = document.getElementById('shipping-address').value.trim();

                if (!shippingAddress) {
                    alert('Vui lòng nhập địa chỉ giao hàng');
                    return;
                }

                confirmPaymentBtn.disabled = true;
                processingPayment.style.display = 'block';

                processBankTransferPayment(shippingAddress);
            });

            function processMoMoPayment(shippingAddress) {
                console.log('🚀 processMoMoPayment called!');
                console.log('Shipping address:', shippingAddress);
                console.log('Order code:', '<?php echo $orderCode; ?>');
                
                const subtotal = <?php echo $totalAmount; ?>;
                const vatAmount = <?php echo $vatAmount; ?>;
                const shippingFee = window.currentShippingFee || 0;
                const couponDiscount = window.currentCouponDiscount || 0;
                const finalAmount = subtotal + vatAmount + shippingFee - couponDiscount;
                
                const couponCode = document.getElementById('coupon_code_hidden')?.value || '';
                
                console.log('Subtotal:', subtotal);
                console.log('VAT:', vatAmount);
                console.log('Shipping Fee:', shippingFee);
                console.log('Coupon Discount:', couponDiscount);
                console.log('Coupon Code:', couponCode);
                console.log('Final Amount:', finalAmount);

                const formData = new FormData();
                formData.append('payment_method', 'momo');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('amount', finalAmount);
                formData.append('subtotal', subtotal);
                formData.append('vat_amount', vatAmount);
                formData.append('shipping_fee', shippingFee);
                formData.append('coupon_code', couponCode);
                formData.append('coupon_discount', couponDiscount);
                formData.append('shipping_method', document.getElementById('selected_shipping_method')?.value || 'standard');

                formData.append('selected_products', '<?php echo addslashes(json_encode($orderDetails)); ?>');

                const currentUrl = window.location.origin;
                const relativePath = './momo_payment.php';
                console.log('🌐 Current URL:', currentUrl);
                console.log('🔗 Relative API Path:', relativePath);
                console.log('🔗 Full URL:', window.location.href);

                fetch('./momo_payment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('MoMo Response:', data);

                        if (data.success && data.payUrl) {

                            const subtotal = <?php echo $totalAmount; ?>;
                            const vatAmount = <?php echo $vatAmount; ?>;
                            const shippingFee = window.currentShippingFee || 0;
                            const couponDiscount = window.currentCouponDiscount || 0;

                            const finalAmount = subtotal + vatAmount + shippingFee - couponDiscount;
                            
                            sessionStorage.setItem('pendingOrder', JSON.stringify({
                                orderId: data.orderId,
                                amount: finalAmount,
                                subtotal: subtotal,
                                vat: vatAmount,
                                shipping_fee: shippingFee,
                                coupon_discount: couponDiscount,
                                shipping_address: shippingAddress
                            }));

                            console.log('Redirecting to MoMo:', data.payUrl);
                            window.location.href = data.payUrl;
                        } else {
                            console.error('MoMo Error:', data);
                            alert('Lỗi khi tạo thanh toán MoMo: ' + (data.message || 'Unknown error'));
                            confirmPaymentBtn.disabled = false;
                            processingPayment.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi MoMo:', error);
                        alert('Đã xảy ra lỗi khi xử lý thanh toán MoMo. Vui lòng thử lại.');
                        confirmPaymentBtn.disabled = false;
                        processingPayment.style.display = 'none';
                    });
            }

            function processBankTransferPayment(shippingAddress) {
                console.log('🏦 processBankTransferPayment called!');
                console.log('Shipping address:', shippingAddress);
                
                const subtotal = <?php echo $totalAmount; ?>;
                const vatAmount = <?php echo $vatAmount; ?>;
                const shippingFee = window.currentShippingFee || 0;
                const couponDiscount = window.currentCouponDiscount || 0;
                const finalAmount = subtotal + vatAmount + shippingFee - couponDiscount;
                
                const couponCode = document.getElementById('coupon_code_hidden')?.value || '';
                
                console.log('Bank Transfer - Subtotal:', subtotal);
                console.log('Bank Transfer - VAT:', vatAmount);
                console.log('Bank Transfer - Shipping Fee:', shippingFee);
                console.log('Bank Transfer - Coupon Discount:', couponDiscount);
                console.log('Bank Transfer - Final Amount:', finalAmount);
                
                const formData = new FormData();
                formData.append('payment_method', 'bank_transfer');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('shipping_fee', shippingFee);
                formData.append('coupon_code', couponCode);
                formData.append('coupon_discount', couponDiscount);
                formData.append('selected_shipping_method', document.getElementById('selected_shipping_method')?.value || 'standard');
                formData.append('selected_shipping_fee', shippingFee);

                fetch('payment_confirm.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text().then(text => {
                                if (text.includes('order_success.php')) {
                                    const match = text.match(/order_success\.php\?order_id=(\d+)/);
                                    if (match && match[1]) {
                                        window.location.href = 'order_success.php?order_id=' + match[1];
                                    } else {
                                        window.location.href = 'giohangView.php';
                                    }
                                } else {
                                    window.location.href = 'giohangView.php';
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi:', error);
                        alert('Đã xảy ra lỗi khi xử lý thanh toán. Vui lòng thử lại.');
                        confirmPaymentBtn.disabled = false;
                        processingPayment.style.display = 'none';
                    });
            }

            function processCODPayment(shippingAddress) {
                console.log('🚚 processCODPayment called!');
                console.log('Shipping address:', shippingAddress);

                confirmPaymentBtn.disabled = true;
                processingPayment.style.display = 'block';
                
                const couponCode = document.getElementById('coupon_code_hidden')?.value || '';
                const couponDiscount = window.currentCouponDiscount || 0;
                const shippingFee = window.currentShippingFee || 0;

                const formData = new FormData();
                formData.append('payment_method', 'cod');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('coupon_code', couponCode);
                formData.append('coupon_discount', couponDiscount);
                formData.append('selected_shipping_method', document.getElementById('selected_shipping_method')?.value || 'standard');
                formData.append('selected_shipping_fee', shippingFee);

                fetch('payment_confirm.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text().then(text => {
                                if (text.includes('order_success.php')) {
                                    const match = text.match(/order_success\.php\?order_id=(\d+)/);
                                    if (match && match[1]) {
                                        window.location.href = 'order_success.php?order_id=' + match[1];
                                    } else {
                                        window.location.href = 'giohangView.php';
                                    }
                                } else {
                                    window.location.href = 'giohangView.php';
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi COD:', error);
                        alert('Đã xảy ra lỗi khi xử lý đặt hàng COD. Vui lòng thử lại.');
                        confirmPaymentBtn.disabled = false;
                        processingPayment.style.display = 'none';
                    });
            }
        });
    </script>
</body>

</html>