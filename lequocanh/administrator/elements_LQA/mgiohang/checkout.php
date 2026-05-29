<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';
require_once '../../elements_LQA/mod/database.php';
require_once '../../elements_LQA/mod/OrderAutoCancel.php';
require_once __DIR__ . '/../../../app/autoload.php';

use App\Models\Product;
use App\Models\ProductImage;

// Auto-cancel expired pending orders
if (isset($_SESSION['USER'])) {
    cancelUserExpiredOrders($_SESSION['USER'], 15);
}

$giohang = new GioHang();

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
        if (!verify_csrf_token($token)) {
            Logger::warning("CSRF validation failed in checkout", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            die('CSRF token validation failed. Please try again.');
        }
    }
}

$selectedProducts = json_decode($_POST['selected_products'], true);

$giohang = new GioHang();
$tonkho = new MTonKho();

// Lấy thông tin người dùng từ database
$userInfo = [
    'hoten' => '',
    'dienthoai' => '',
    'diachi' => '',
    'province_id' => '',
    'district_id' => '',
    'ward_id' => ''
];

if (isset($_SESSION['USER'])) {
    require_once '../../elements_LQA/mod/userCls.php';
    $userObj = new user();
    $currentUser = $userObj->UserGetbyUsername($_SESSION['USER']);
    if ($currentUser) {
        $userInfo = [
            'hoten' => $currentUser->hoten ?? '',
            'dienthoai' => $currentUser->dienthoai ?? '',
            'diachi' => $currentUser->diachi ?? '',
            'province_id' => $currentUser->province_id ?? '',
            'district_id' => $currentUser->district_id ?? '',
            'ward_id' => $currentUser->ward_id ?? ''
        ];
    }
}

// Kiểm tra xem người dùng đã có đầy đủ thông tin địa chỉ chưa
$isAddressComplete = !empty($userInfo['hoten']) && !empty($userInfo['dienthoai']) && !empty($userInfo['diachi']) && !empty($userInfo['province_id']) && !empty($userInfo['district_id']);

$orderDetails = [];
$totalAmount = 0;

foreach ($selectedProducts as $product) {
    $productId = $product['productId'];
    $quantity = $product['quantity'];

    $productInfo = Product::getById((int)$productId);

    if (!$productInfo) {

        continue;
    }

    $productStatus = Product::getProductStatusValue((int)$productId);
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

    $hinhanh = ProductImage::getById((int)$productInfo->hinhanh);
    $imageSrc = "../../elements_LQA/mhanghoa/displayImage.php?id=" . $productInfo->hinhanh;
    if (!$hinhanh || (empty($hinhanh->du_lieu) && empty($hinhanh->duong_dan))) {
        $imageSrc = "../../elements_LQA/img_LQA/no-image.png";
    }

    // Kiểm tra sản phẩm có giảm giá không
    $hasDiscount = !empty($productInfo->giakhuyenmai) && $productInfo->giakhuyenmai > 0 && $productInfo->giakhuyenmai < $productInfo->giathamkhao;
    $currentPrice = $hasDiscount ? $productInfo->giakhuyenmai : $productInfo->giathamkhao;
    
    $subtotal = $currentPrice * $quantity;
    $totalAmount += $subtotal;

    $orderDetails[] = [
        'id' => $productId,
        'name' => $productInfo->tenhanghoa,
        'price' => $currentPrice,
        'original_price' => $productInfo->giathamkhao,
        'discount_price' => $hasDiscount ? $productInfo->giakhuyenmai : null,
        'has_discount' => $hasDiscount,
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
    'bank_name' => '',
    'account_number' => '',
    'account_name' => '',
    'momo_enabled' => true,
    'bank_transfer_enabled' => true,
    'cod_enabled' => true
];

if ($checkTableStmt->rowCount() > 0) {

    $configSql = "SELECT id, ten_ngan_hang, so_tai_khoan, ten_tai_khoan, ngay_tao, ngay_cap_nhat FROM cau_hinh_thanh_toan LIMIT 1";
    $configStmt = $conn->prepare($configSql);
    $configStmt->execute();

    if ($configStmt->rowCount() > 0) {
        $config = $configStmt->fetch(PDO::FETCH_ASSOC);

        $paymentConfig = [
            'bank_name' => $config['ten_ngan_hang'],
            'account_number' => $config['so_tai_khoan'],
            'account_name' => $config['ten_tai_khoan'],
            'momo_enabled' => !isset($config['momo_enabled']) || $config['momo_enabled'] == 1,
            'bank_transfer_enabled' => !isset($config['bank_transfer_enabled']) || $config['bank_transfer_enabled'] == 1,
            'cod_enabled' => !isset($config['cod_enabled']) || $config['cod_enabled'] == 1
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
        /* Toast Validation Notification */
        .validation-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            max-width: 420px;
            width: 100%;
            animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(120%);
                opacity: 0;
            }
        }

        .validation-toast.hiding {
            animation: slideOutRight 0.3s ease-in forwards;
        }

        .validation-toast .toast-box {
            background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(239, 68, 68, 0.15), 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .validation-toast .toast-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fff 100%);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #fecaca;
        }

        .validation-toast .toast-header .icon-circle {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .validation-toast .toast-header .icon-circle i {
            color: white;
            font-size: 16px;
        }

        .validation-toast .toast-header h6 {
            margin: 0;
            color: #991b1b;
            font-weight: 700;
            font-size: 15px;
        }

        .validation-toast .toast-header .close-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: #dc2626;
            font-size: 20px;
            cursor: pointer;
            padding: 0 4px;
            line-height: 1;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .validation-toast .toast-header .close-btn:hover {
            opacity: 1;
        }

        .validation-toast .toast-body {
            padding: 14px 18px;
        }

        .validation-toast .error-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            animation: fadeInUp 0.3s ease-out backwards;
        }

        .validation-toast .error-item:not(:last-child) {
            border-bottom: 1px dashed #fecaca;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .validation-toast .error-item .error-icon {
            width: 24px;
            height: 24px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .validation-toast .error-item .error-icon i {
            color: #ef4444;
            font-size: 11px;
        }

        .validation-toast .error-item .error-text {
            color: #7f1d1d;
            font-size: 14px;
            line-height: 1.5;
        }

        .validation-toast .toast-footer {
            background: #fef2f2;
            padding: 10px 18px;
            border-top: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .validation-toast .toast-footer i {
            color: #f59e0b;
            font-size: 13px;
        }

        .validation-toast .toast-footer small {
            color: #92400e;
            font-size: 12px;
        }

        /* Progress bar auto-close */
        .validation-toast .progress-bar {
            height: 3px;
            background: linear-gradient(90deg, #ef4444, #f87171);
            animation: shrink 5s linear forwards;
            border-radius: 0 0 12px 12px;
        }

        @keyframes shrink {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Highlight empty fields */
        .field-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

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
            flex-wrap: wrap;
        }

        .payment-method {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            min-width: 200px;
        }

        .payment-method.active {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .saved-address-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .saved-address-card:hover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }

        .saved-address-card.selected {
            border-color: #0d6efd;
            background: rgba(13, 110, 253, 0.05);
            box-shadow: 0 0 0 1px #0d6efd;
        }

        .saved-address-card .badge-default {
            background: #28a745;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .payment-method img {
            height: 40px;
            margin-bottom: 10px;
        }

        .qr-container {
            text-align: center;
            margin-top: 20px;
            padding: 30px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background-color: #f8f9fa;
        }

        .qr-code {
            max-width: 450px;
            margin: 0 auto;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        .qr-code:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .qr-fullscreen-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 999999;
            justify-content: center;
            align-items: center;
        }

        #qrFullscreenImg {
            width: 600px !important;
            height: auto !important;
            max-width: 90vw !important;
            max-height: none !important;
            display: block;
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

        /* ===== NEW: Breadcrumb ===== */
        .breadcrumb-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 0 5px;
        }
        .breadcrumb-custom { background: none; padding: 0; margin: 0; font-size: 14px; list-style: none; display: flex; gap: 8px; }
        .breadcrumb-custom li a { color: #3498db; text-decoration: none; }
        .breadcrumb-custom li a:hover { text-decoration: underline; }
        .breadcrumb-custom li::after { content: '/'; color: #adb5bd; margin-left: 8px; }
        .breadcrumb-custom li:last-child::after { content: ''; }
        .breadcrumb-custom li:last-child { color: #6c757d; }

        /* ===== NEW: Step Indicator ===== */
        .checkout-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0 30px;
            gap: 0;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .step-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px;
            transition: all 0.3s;
        }
        .step-circle.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            box-shadow: 0 3px 10px rgba(52,152,219,0.3);
        }
        .step-circle.done {
            background: #27ae60;
            color: white;
        }
        .step-circle.pending {
            background: #e9ecef;
            color: #adb5bd;
        }
        .step-label {
            font-size: 13px; font-weight: 600;
        }
        .step-label.active { color: #3498db; }
        .step-label.done { color: #27ae60; }
        .step-label.pending { color: #adb5bd; }
        .step-line {
            width: 60px; height: 3px;
            margin: 0 10px;
            border-radius: 2px;
        }
        .step-line.done { background: #27ae60; }
        .step-line.pending { background: #e9ecef; }

        /* ===== NEW: Card Headers with Gradient ===== */
        .card-header.gradient-blue {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        .card-header.gradient-green {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
        }
        .card-header.gradient-purple {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }

        /* ===== NEW: Order Notes ===== */
        .order-notes textarea {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            resize: vertical;
            min-height: 80px;
            transition: border-color 0.2s;
        }
        .order-notes textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        /* ===== NEW: Terms Checkbox ===== */
        .terms-checkbox {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }
        .terms-checkbox label { cursor: pointer; }
        .terms-checkbox a { color: #3498db; text-decoration: underline; }

        /* ===== NEW: Sticky Summary on Desktop ===== */
        @media (min-width: 769px) {
            .order-summary-sticky {
                position: sticky;
                top: 20px;
            }
        }

        /* ===== IMPROVED: Confirm Button ===== */
        #confirmPaymentBtn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            padding: 14px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        #confirmPaymentBtn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52,152,219,0.4);
        }
        #confirmPaymentBtn:disabled {
            background: #adb5bd;
            cursor: not-allowed;
        }

        /* ===== IMPROVED: Back Button ===== */
        .btn-back {
            background: white;
            color: #6c757d;
            border: 2px solid #dee2e6;
            padding: 12px 24px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
        }

        /* ===== IMPROVED: Payment Methods Mobile ===== */
        @media (max-width: 768px) {
            .payment-methods { flex-direction: column; gap: 10px; }
            .payment-method { min-width: 100%; padding: 15px; }
            .checkout-container { padding: 15px; margin: 10px auto; }
            .checkout-steps { flex-wrap: wrap; gap: 5px; }
            .step-line { width: 30px; }
            .step-label { font-size: 11px; }
        }

    </style>
</head>

<body>
    <!-- Breadcrumb -->
    <div class="breadcrumb-container">
        <ul class="breadcrumb-custom">
            <li><a href="../../../index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
            <li><a href="giohangView.php">Giỏ hàng</a></li>
            <li>Thanh toán</li>
        </ul>
    </div>

    <!-- Step Indicator -->
    <div class="checkout-steps">
        <div class="step-item">
            <div class="step-circle done"><i class="fas fa-check"></i></div>
            <span class="step-label done">Giỏ hàng</span>
        </div>
        <div class="step-line done"></div>
        <div class="step-item">
            <div class="step-circle active">2</div>
            <span class="step-label active">Thanh toán</span>
        </div>
        <div class="step-line pending"></div>
        <div class="step-item">
            <div class="step-circle pending">3</div>
            <span class="step-label pending">Hoàn tất</span>
        </div>
    </div>

    <div class="checkout-container">
        <h2 class="mb-4">Thanh toán đơn hàng</h2>

        <!-- Thông tin địa chỉ giao hàng -->
        <div class="card mb-4">
            <div class="card-header gradient-blue text-white">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ giao hàng</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['USER'])): ?>
                <!-- Địa chỉ đã lưu -->
                <div class="mb-4" id="saved-addresses-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="fas fa-bookmark me-1 text-primary"></i> Địa chỉ đã lưu</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadSavedAddresses()">
                            <i class="fas fa-sync-alt me-1"></i> Làm mới
                        </button>
                    </div>
                    <div id="saved-addresses-list" class="row g-2">
                        <div class="col-12 text-muted text-center py-3">
                            <i class="fas fa-spinner fa-spin me-1"></i> Đang tải địa chỉ...
                        </div>
                    </div>
                </div>
                <hr>
                <p class="text-muted mb-3"><i class="fas fa-edit me-1"></i> Hoặc nhập địa chỉ mới bên dưới:</p>
                <?php endif; ?>

                <?php if (!$isAddressComplete): ?>
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Thông tin địa chỉ chưa đầy đủ!</strong> Vui lòng nhập đầy đủ thông tin để tiếp tục mua hàng.
                    <br><small>Hoặc cập nhật thông tin tại <a href="../page.php?p=thongtintaikhoan" target="_blank">Thông tin tài khoản</a></small>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Họ tên người nhận -->
                    <div class="col-md-6 mb-3">
                        <label for="receiver-name" class="form-label">Họ tên người nhận <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="receiver-name" name="receiver_name"
                            placeholder="Nhập họ tên người nhận" required
                            value="<?php echo htmlspecialchars($userInfo['hoten']); ?>">
                    </div>
                    <!-- Số điện thoại -->
                    <div class="col-md-6 mb-3">
                        <label for="receiver-phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="receiver-phone" name="receiver_phone"
                            placeholder="Nhập số điện thoại" required
                            value="<?php echo htmlspecialchars($userInfo['dienthoai']); ?>">
                    </div>
                </div>

                <div class="row">
                    <!-- Tỉnh/Thành phố -->
                    <div class="col-md-4 mb-3">
                        <label for="province" class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                        <select class="form-select" id="province" name="province" required>
                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                        </select>
                    </div>
                    <!-- Quận/Huyện -->
                    <div class="col-md-4 mb-3">
                        <label for="district" class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                        <select class="form-select" id="district" name="district" disabled required>
                            <option value="">-- Chọn Quận/Huyện --</option>
                        </select>
                    </div>
                    <!-- Phường/Xã -->
                    <div class="col-md-4 mb-3">
                        <label for="ward" class="form-label">Phường/Xã</label>
                        <select class="form-select" id="ward" name="ward" disabled>
                            <option value="">-- Chọn Phường/Xã --</option>
                        </select>
                    </div>
                </div>

                <!-- Địa chỉ chi tiết -->
                <div class="mb-3">
                    <label for="detail-address" class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="detail-address" name="detail_address"
                        placeholder="Số nhà, tên đường, ngõ/hẻm..." required
                        value="<?php echo htmlspecialchars($userInfo['diachi']); ?>">
                    <div class="form-text"><i class="fas fa-info-circle me-1"></i>VD: Số 123, Đường Nguyễn Văn A, Ngõ 45</div>
                </div>

                <!-- Hidden fields để lưu thông tin đã chọn -->
                <input type="hidden" id="full-address" name="full_address" value="">
                <input type="hidden" id="province-name" name="province_name" value="">
                <input type="hidden" id="district-name" name="district_name" value="">
                <input type="hidden" id="ward-name" name="ward_name" value="">
                <input type="hidden" id="saved-province-id" value="<?php echo $userInfo['province_id']; ?>">
                <input type="hidden" id="saved-district-id" value="<?php echo $userInfo['district_id']; ?>">
                <input type="hidden" id="saved-ward-id" value="<?php echo $userInfo['ward_id']; ?>">

                <!-- Hiển thị địa chỉ đầy đủ -->
                <div class="alert alert-info" id="full-address-display" style="display: none;">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <strong>Địa chỉ giao hàng:</strong> <span id="full-address-text"></span>
                </div>

                <!-- Nút lưu địa chỉ -->
                <?php if (isset($_SESSION['USER'])): ?>
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-success btn-sm" id="saveAddressBtn" onclick="saveUserAddress()">
                        <i class="fas fa-save me-1"></i> Lưu địa chỉ này cho lần sau
                    </button>
                    <span id="saveAddressStatus" class="ms-2" style="display: none;"></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Phương thức vận chuyển V2 - TÍNH PHÍ ĐỘNG -->
        <?php include 'shipping_method_selector_v2.php'; ?>

        <!-- Mã giảm giá (Coupon) -->
        <?php include 'coupon_input_component.php'; ?>

        <!-- Thông tin đơn hàng -->
        <div class="card mb-4">
            <div class="card-header gradient-green text-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <p><strong>Mã đơn hàng:</strong> <?php echo $orderCode; ?></p>
                <div class="table-responsive"><table class="table">
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
                                <td>
                                    <?php if (!empty($item['has_discount'])): ?>
                                        <span class="text-danger fw-bold"><?= number_format($item['price'], 0, ',', '.'); ?> ₫</span>
                                        <br>
                                        <small class="text-muted text-decoration-line-through"><?= number_format($item['original_price'], 0, ',', '.'); ?> ₫</small>
                                    <?php else: ?>
                                        <?= number_format($item['price'], 0, ',', '.'); ?> ₫
                                    <?php endif; ?>
                                </td>
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
                </table></div>
            </div>
        </div>

        <!-- Phương thức thanh toán -->
        <div class="card mb-4">
            <div class="card-header gradient-purple text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Phương thức thanh toán</h5>
            </div>
            <div class="card-body">
                <div class="payment-methods">
                    <?php if ($paymentConfig['momo_enabled']): ?>
                    <div class="payment-method" id="momo-payment">
                        <img src="https://developers.momo.vn/v3/assets/images/square-logo.svg" alt="MoMo" style="height: 40px; margin-bottom: 10px;">
                        <h5>Thanh toán MoMo</h5>
                        <p class="text-muted">Thanh toán nhanh chóng và an toàn qua ví MoMo</p>
                    </div>
                    <?php endif; ?>
                    <?php if ($paymentConfig['bank_transfer_enabled']): ?>
                    <div class="payment-method <?php echo (!$paymentConfig['momo_enabled']) ? 'active' : ''; ?>" id="bank-transfer">
                        <i class="fas fa-university" style="font-size: 2rem; color: #0d6efd; margin-bottom: 10px;"></i>
                        <h5>Chuyển khoản ngân hàng</h5>
                        <p class="text-muted">Quét mã QR để thanh toán qua ứng dụng ngân hàng</p>
                    </div>
                    <?php endif; ?>
                    <?php if ($paymentConfig['cod_enabled']): ?>
                    <div class="payment-method" id="cod-payment">
                        <i class="fas fa-truck" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                        <h5>Thanh toán khi nhận hàng (COD)</h5>
                        <p class="text-muted">Thanh toán bằng tiền mặt khi nhận hàng</p>
                    </div>
                    <?php endif; ?>
                    <?php if (!$paymentConfig['momo_enabled'] && !$paymentConfig['bank_transfer_enabled'] && !$paymentConfig['cod_enabled']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Hiện tại chưa có phương thức thanh toán nào được kích hoạt. Vui lòng liên hệ quản trị viên.
                    </div>
                    <?php endif; ?>
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
                <div class="qr-container" id="bank-transfer-details" style="<?php echo $paymentConfig['bank_transfer_enabled'] ? '' : 'display: none;'; ?>">
                    <?php if (!empty($paymentConfig['account_number']) && !empty($paymentConfig['bank_name'])): ?>
                        <h5>Quét mã QR để thanh toán</h5>
                        <div class="qr-code" onclick="openQrFullscreen()">
                            <?php
                            $bankCode = 'VCB';
                            $amount = intval($finalTotal - ($_SESSION['coupon_discount'] ?? 0));
                            $description = $transferContent;
                            $accountNumber = $paymentConfig['account_number'];
                            $accountName = urlencode($paymentConfig['account_name']);

                            $bankNameUpper = strtoupper(trim($paymentConfig['bank_name']));
                            if (strpos($bankNameUpper, 'AGRI') !== false) $bankCode = 'AGR';
                            elseif (strpos($bankNameUpper, 'VIETIN') !== false) $bankCode = 'ICB';
                            elseif (strpos($bankNameUpper, 'BIDV') !== false) $bankCode = 'BIDV';
                            elseif (strpos($bankNameUpper, 'TECH') !== false) $bankCode = 'TCB';
                            elseif (strpos($bankNameUpper, 'MB') !== false) $bankCode = 'MB';
                            elseif (strpos($bankNameUpper, 'ACB') !== false) $bankCode = 'ACB';
                            elseif (strpos($bankNameUpper, 'TPB') !== false) $bankCode = 'TPB';
                            elseif (strpos($bankNameUpper, 'VCB') !== false || strpos($bankNameUpper, 'VIETCOM') !== false) $bankCode = 'VCB';

                            $vietQrUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-print.png?amount={$amount}&addInfo={$description}&accountName={$accountName}";
                            ?>
                            <img src="<?php echo $vietQrUrl; ?>" alt="QR Code" id="qrCodeImg" data-bank="<?php echo $bankCode; ?>" data-account="<?php echo $accountNumber; ?>" data-name="<?php echo $accountName; ?>" data-desc="<?php echo $description; ?>" style="width: 100%; border-radius: 10px;">
                            <p class="text-center text-muted mt-1" style="font-size: 13px;"><i class="fas fa-search-plus"></i> Nhấn để phóng to</p>
                        </div>
                        <div class="bank-info mt-3">
                            <p><strong>Ngân hàng:</strong> <?php echo htmlspecialchars($paymentConfig['bank_name']); ?></p>
                            <p><strong>Số tài khoản:</strong> <?php echo htmlspecialchars($paymentConfig['account_number']); ?></p>
                            <p><strong>Chủ tài khoản:</strong> <?php echo htmlspecialchars($paymentConfig['account_name']); ?></p>
                            <p><strong>Số tiền:</strong> <span id="qr-amount" style="color: #dc3545; font-size: 20px; font-weight: bold;"><?php echo number_format($amount, 0, ',', '.'); ?>₫</span></p>
                            <p><strong>Nội dung:</strong> <span style="color: #0d6efd; font-size: 16px; font-weight: bold;"><?php echo $description; ?></span></p>
                        </div>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle me-2"></i>
                            Quét mã QR bằng ứng dụng ngân hàng. Số tiền và nội dung đã được điền sẵn. Chỉ cần nhấn <strong>Thanh toán</strong>.
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
                            Số tiền cần thanh toán: <strong id="cod-amount"><?php echo number_format($finalTotal - ($_SESSION['coupon_discount'] ?? 0), 0, ',', '.'); ?> đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ghi chú đơn hàng -->
        <div class="card mb-4 order-notes">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Ghi chú đơn hàng</h5>
            </div>
            <div class="card-body">
                <textarea class="form-control" id="orderNotes" name="order_notes" 
                    placeholder="Ghi chú cho người giao hàng (VD: Giao giờ hành chính, gọi trước khi giao...)"
                    maxlength="500"></textarea>
                <small class="text-muted">Tối đa 500 ký tự</small>
            </div>
        </div>

        <!-- Điều khoản -->
        <div class="terms-checkbox mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="agreeTerms">
                <label class="form-check-label" for="agreeTerms">
                    Tôi đã đọc và đồng ý với <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">điều khoản & điều kiện</a> của cửa hàng
                </label>
            </div>
        </div>

        <!-- Nút xác nhận thanh toán -->
        <div class="d-flex justify-content-between align-items-center">
            <a href="giohangView.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Quay lại giỏ hàng
            </a>
            <button id="confirmPaymentBtn" class="btn btn-primary" disabled>
                <i class="fas fa-lock me-2"></i>Xác nhận đã thanh toán
            </button>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // ===== ADDRESS SELECTOR =====
        const AddressHandler = {
            province: null,
            district: null,
            ward: null,

            init: function() {
                this.bindEvents();
                this.loadProvincesAndRestore();
                if (typeof loadSavedAddresses === 'function') {
                    loadSavedAddresses();
                }
            },

            loadProvincesAndRestore: function() {
                const self = this;
                const savedProvinceId = $('#saved-province-id').val();
                const savedDistrictId = $('#saved-district-id').val();
                const savedWardId = $('#saved-ward-id').val();

                $.ajax({
                    url: '../../../api/get_address_data.php',
                    type: 'GET',
                    data: { action: 'get_all_provinces' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.provinces) {
                            let options = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
                            response.provinces.forEach(function(p) {
                                const selected = (savedProvinceId && p.id == savedProvinceId) ? ' selected' : '';
                                options += `<option value="${p.id}"${selected}>${p.name}</option>`;
                            });
                            $('#province').html(options);

                            // Nếu có tỉnh đã lưu, load quận
                            if (savedProvinceId) {
                                const selectedOption = $('#province option:selected');
                                self.province = { id: savedProvinceId, name: selectedOption.text() };
                                self.loadDistrictsAndRestore(savedProvinceId, savedDistrictId, savedWardId);
                            }
                            self.updateFullAddress();
                        }
                    }
                });
            },

            loadDistrictsAndRestore: function(provinceId, savedDistrictId, savedWardId) {
                const self = this;
                $.ajax({
                    url: '../../../api/get_address_data.php',
                    type: 'GET',
                    data: { action: 'get_districts', province_id: provinceId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.districts) {
                            let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                            response.districts.forEach(function(d) {
                                const selected = (savedDistrictId && d.id == savedDistrictId) ? ' selected' : '';
                                options += `<option value="${d.id}"${selected}>${d.name}</option>`;
                            });
                            $('#district').html(options).prop('disabled', false);

                            // Nếu có quận đã lưu, load phường
                            if (savedDistrictId) {
                                const selectedOption = $('#district option:selected');
                                self.district = { id: savedDistrictId, name: selectedOption.text() };
                                self.loadWardsAndRestore(savedDistrictId, savedWardId);
                            }
                            self.updateFullAddress();
                        }
                    }
                });
            },

            loadWardsAndRestore: function(districtId, savedWardId) {
                const self = this;
                $.ajax({
                    url: '../../../api/get_address_data.php',
                    type: 'GET',
                    data: { action: 'get_wards', district_id: districtId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.wards) {
                            let options = '<option value="">-- Chọn Phường/Xã --</option>';
                            response.wards.forEach(function(w) {
                                const selected = (savedWardId && w.code == savedWardId) ? ' selected' : '';
                                options += `<option value="${w.code}"${selected}>${w.name}</option>`;
                            });
                            $('#ward').html(options).prop('disabled', false);

                            // Nếu có phường đã lưu
                            if (savedWardId) {
                                const selectedOption = $('#ward option:selected');
                                self.ward = { code: savedWardId, name: selectedOption.text() };
                            }
                            self.updateFullAddress();
                        }
                    }
                });
            },

            bindEvents: function() {
                const self = this;
                $('#province').on('change', function() { self.onProvinceChange($(this).val(), $(this).find('option:selected').text()); });
                $('#district').on('change', function() { self.onDistrictChange($(this).val(), $(this).find('option:selected').text()); });
                $('#ward').on('change', function() { self.onWardChange($(this).val(), $(this).find('option:selected').text()); });
                $('#detail-address, #receiver-name, #receiver-phone').on('input', function() { self.updateFullAddress(); });
            },

            loadProvinces: function() {
                // Đã xử lý trong loadProvincesAndRestore
            },

            onProvinceChange: function(id, name) {
                this.province = id ? { id, name } : null;
                $('#district').prop('disabled', true).html('<option value="">-- Chọn Quận/Huyện --</option>');
                $('#ward').prop('disabled', true).html('<option value="">-- Chọn Phường/Xã --</option>');
                if (id) this.loadDistricts(id);
                this.updateFullAddress();
            },

            loadDistricts: function(provinceId, savedDistrictId, savedWardId) {
                const self = this;
                $.ajax({
                    url: '../../../api/get_address_data.php',
                    type: 'GET',
                    data: { action: 'get_districts', province_id: provinceId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.districts) {
                            let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                            response.districts.forEach(function(d) {
                                const selected = (savedDistrictId && d.id == savedDistrictId) ? ' selected' : '';
                                options += `<option value="${d.id}"${selected}>${d.name}</option>`;
                            });
                            $('#district').html(options).prop('disabled', false);
                            
                            if (savedDistrictId) {
                                const selectedOption = $('#district option:selected');
                                self.district = { id: savedDistrictId, name: selectedOption.text() };
                                self.loadWards(savedDistrictId, savedWardId);
                            }
                        }
                    }
                });
            },

            onDistrictChange: function(id, name) {
                this.district = id ? { id, name } : null;
                $('#ward').prop('disabled', true).html('<option value="">-- Chọn Phường/Xã --</option>');
                if (id) this.loadWards(id);
                this.updateFullAddress();
            },

            loadWards: function(districtId, savedWardId) {
                const self = this;
                $.ajax({
                    url: '../../../api/get_address_data.php',
                    type: 'GET',
                    data: { action: 'get_wards', district_id: districtId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.wards) {
                            let options = '<option value="">-- Chọn Phường/Xã --</option>';
                            response.wards.forEach(function(w) {
                                const selected = (savedWardId && w.code == savedWardId) ? ' selected' : '';
                                options += `<option value="${w.code}"${selected}>${w.name}</option>`;
                            });
                            $('#ward').html(options).prop('disabled', false);
                            
                            if (savedWardId) {
                                const selectedOption = $('#ward option:selected');
                                self.ward = { code: savedWardId, name: selectedOption.text() };
                                self.updateFullAddress();
                            }
                        }
                    }
                });
            },

            onWardChange: function(code, name) {
                this.ward = code ? { code, name } : null;
                this.updateFullAddress();
            },

            updateFullAddress: function() {
                const detail = $('#detail-address').val().trim();
                const parts = [];
                if (detail) parts.push(detail);
                if (this.ward) parts.push(this.ward.name);
                if (this.district) parts.push(this.district.name);
                if (this.province) parts.push(this.province.name);

                const fullAddress = parts.join(', ');
                if (fullAddress) {
                    $('#full-address-text').text(fullAddress);
                    $('#full-address-display').show();
                    $('#full-address').val(fullAddress);
                    $('#province-name').val(this.province?.name || '');
                    $('#district-name').val(this.district?.name || '');
                    $('#ward-name').val(this.ward?.name || '');
                } else {
                    $('#full-address-display').hide();
                }
            },

            validate: function() {
                const errors = [];
                if (!this.province) errors.push('Vui lòng chọn Tỉnh/Thành phố');
                if (!this.district) errors.push('Vui lòng chọn Quận/Huyện');
                if (!this.ward) errors.push('Vui lòng chọn Phường/Xã');
                if (!$('#detail-address').val().trim()) errors.push('Vui lòng nhập địa chỉ chi tiết');
                if (!$('#receiver-name').val().trim()) errors.push('Vui lòng nhập tên người nhận');
                if (!$('#receiver-phone').val().trim()) errors.push('Vui lòng nhập số điện thoại');
                return { valid: errors.length === 0, errors };
            },

            getAddress: function() {
                return {
                    province_id: this.province?.id,
                    province_name: this.province?.name,
                    district_id: this.district?.id,
                    district_name: this.district?.name,
                    ward_code: this.ward?.code,
                    ward_name: this.ward?.name,
                    detail_address: $('#detail-address').val().trim(),
                    receiver_name: $('#receiver-name').val().trim(),
                    receiver_phone: $('#receiver-phone').val().trim(),
                    full_address: $('#full-address').val()
                };
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            AddressHandler.init();
            window.AddressHandler = AddressHandler;
        });

        // ===== VALIDATION TOAST =====
        function showValidationToast(errors) {
            // Remove old toast if exists
            const oldToast = document.querySelector('.validation-toast');
            if (oldToast) oldToast.remove();

            // Map error messages to field icons
            const fieldIcons = {
                'Tỉnh/Thành phố': 'fa-city',
                'Quận/Huyện': 'fa-map',
                'Phường/Xã': 'fa-map-pin',
                'địa chỉ chi tiết': 'fa-home',
                'tên người nhận': 'fa-user',
                'số điện thoại': 'fa-phone'
            };

            function getIcon(errorText) {
                for (const [key, icon] of Object.entries(fieldIcons)) {
                    if (errorText.includes(key)) return icon;
                }
                return 'fa-exclamation-circle';
            }

            const errorsHtml = errors.map((err, i) => `
                <div class="error-item" style="animation-delay: ${i * 0.08}s">
                    <div class="error-icon"><i class="fas ${getIcon(err)}"></i></div>
                    <span class="error-text">${err}</span>
                </div>
            `).join('');

            const toastHtml = `
                <div class="validation-toast">
                    <div class="toast-box">
                        <div class="toast-header">
                            <div class="icon-circle">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <h6>Thiếu thông tin giao hàng</h6>
                            <button class="close-btn" onclick="closeValidationToast()">&times;</button>
                        </div>
                        <div class="toast-body">
                            ${errorsHtml}
                        </div>
                        <div class="toast-footer">
                            <i class="fas fa-lightbulb"></i>
                            <small>Vui lòng điền đầy đủ thông tin để tiếp tục đặt hàng</small>
                        </div>
                        <div class="progress-bar"></div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', toastHtml);

            // Highlight empty fields
            highlightEmptyFields(errors);

            // Auto close after 5s
            setTimeout(() => closeValidationToast(), 5000);
        }

        function closeValidationToast() {
            const toast = document.querySelector('.validation-toast');
            if (toast) {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }
            // Remove field highlights
            document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
        }

        function highlightEmptyFields(errors) {
            const fieldMap = [
                { keyword: 'tên người nhận', selector: '#receiver-name' },
                { keyword: 'số điện thoại', selector: '#receiver-phone' },
                { keyword: 'Tỉnh/Thành phố', selector: '#province' },
                { keyword: 'Quận/Huyện', selector: '#district' },
                { keyword: 'Phường/Xã', selector: '#ward' },
                { keyword: 'địa chỉ chi tiết', selector: '#detail-address' }
            ];

            // Clear old highlights
            document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));

            errors.forEach(err => {
                for (const field of fieldMap) {
                    if (err.includes(field.keyword)) {
                        const el = document.querySelector(field.selector);
                        if (el) {
                            el.classList.add('field-error');
                            // Remove highlight when user interacts
                            el.addEventListener('input', function handler() {
                                el.classList.remove('field-error');
                                el.removeEventListener('input', handler);
                            });
                            el.addEventListener('change', function handler() {
                                el.classList.remove('field-error');
                                el.removeEventListener('change', handler);
                            });
                        }
                        break;
                    }
                }
            });

            // Scroll to first error field
            const firstError = document.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function saveUserAddress() {
            const addressData = window.AddressHandler.getAddress();
            const validation = window.AddressHandler.validate();
            
            if (!validation.valid) {
                showValidationToast(validation.errors);
                return;
            }
            
            const btn = document.getElementById('saveAddressBtn');
            const status = document.getElementById('saveAddressStatus');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang lưu...';
            
            const formData = new FormData();
            formData.append('action', 'add_address');
            formData.append('province_id', addressData.province_id || '');
            formData.append('district_id', addressData.district_id || '');
            formData.append('ward_code', addressData.ward_code || '');
            formData.append('address_detail', addressData.detail_address || '');
            formData.append('recipient_name', addressData.receiver_name || '');
            formData.append('phone', addressData.receiver_phone || '');
            
            fetch('../../../api/user_addresses.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    status.innerHTML = '<i class="fas fa-check-circle text-success"></i> <span class="text-success">Đã lưu!</span>';
                    status.style.display = 'inline';
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Đã lưu';
                    btn.classList.remove('btn-outline-success');
                    btn.classList.add('btn-success');
                    loadSavedAddresses();
                } else {
                    status.innerHTML = '<span class="text-danger">' + (data.message || 'Lỗi') + '</span>';
                    status.style.display = 'inline';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-1"></i> Lưu địa chỉ này cho lần sau';
                }
            })
            .catch(() => {
                status.innerHTML = '<span class="text-danger">Lỗi kết nối</span>';
                status.style.display = 'inline';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> Lưu địa chỉ này cho lần sau';
            });
        }

        function loadSavedAddresses() {
            fetch('../../../api/user_addresses.php?action=get_addresses')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('saved-addresses-list');
                    if (!data.success || !data.addresses || data.addresses.length === 0) {
                        container.innerHTML = '<div class="col-12 text-muted text-center py-2">Chưa có địa chỉ đã lưu</div>';
                        return;
                    }
                    
                    let html = '';
                    data.addresses.forEach(addr => {
                        const defaultBadge = addr.is_default == 1 ? '<span class="badge-default ms-1">Mặc định</span>' : '';
                        html += `
                        <div class="col-md-6">
                            <div class="saved-address-card" onclick="selectAddress(${addr.id}, ${JSON.stringify(addr).replace(/"/g, '&quot;')})" id="addr-card-${addr.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>${addr.recipient_name}</strong> ${defaultBadge}
                                        <br><small class="text-muted">${addr.phone}</small>
                                        <br><small>${addr.full_address}</small>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="event.stopPropagation(); deleteAddress(${addr.id})" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        ${addr.is_default != 1 ? `<br><button type="button" class="btn btn-sm btn-link text-success p-0 mt-1" onclick="event.stopPropagation(); setDefaultAddress(${addr.id})" title="Đặt mặc định"><i class="fas fa-star"></i></button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                    container.innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('saved-addresses-list').innerHTML = '<div class="col-12 text-danger text-center py-2">Lỗi tải địa chỉ</div>';
                });
        }

        function selectAddress(id, addr) {
            document.querySelectorAll('.saved-address-card').forEach(c => c.classList.remove('selected'));
            document.getElementById('addr-card-' + id)?.classList.add('selected');
            
            document.getElementById('receiver-name').value = addr.recipient_name || '';
            document.getElementById('receiver-phone').value = addr.phone || '';
            document.getElementById('detail-address').value = addr.address_detail || '';
            document.getElementById('province').value = addr.province_id || '';
            
            if (window.AddressHandler) {
                window.AddressHandler.province = { id: addr.province_id, name: addr.province_name || '' };
                window.AddressHandler.loadDistricts(addr.province_id, addr.district_id, addr.ward_code);
            }
            
            window.AddressHandler.updateFullAddress();
        }

        function deleteAddress(id) {
            if (!confirm('Bạn có chắc muốn xóa địa chỉ này?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_address');
            formData.append('id', id);
            fetch('../../../api/user_addresses.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) loadSavedAddresses();
                    else alert(data.message || 'Lỗi xóa');
                });
        }

        function setDefaultAddress(id) {
            const formData = new FormData();
            formData.append('action', 'set_default');
            formData.append('id', id);
            fetch('../../../api/user_addresses.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) loadSavedAddresses();
                    else alert(data.message || 'Lỗi');
                });
        }
    </script>
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

            // Xác định phương thức thanh toán mặc định dựa trên cấu hình
            let selectedPaymentMethod = 'bank-transfer';
            if (momoPaymentMethod) {
                selectedPaymentMethod = 'momo';
                momoPaymentMethod.classList.add('active');
                if (momoDetails) momoDetails.style.display = 'block';
            } else if (bankTransferMethod) {
                selectedPaymentMethod = 'bank-transfer';
                bankTransferMethod.classList.add('active');
                if (bankDetails) bankDetails.style.display = 'block';
            } else if (codPaymentMethod) {
                selectedPaymentMethod = 'cod';
                codPaymentMethod.classList.add('active');
                if (codDetails) codDetails.style.display = 'block';
            }

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
                
                // Cập nhật QR code với số tiền mới
                const qrImg = document.getElementById('qrCodeImg');
                const qrAmount = document.getElementById('qr-amount');
                if (qrImg && finalTotal > 0) {
                    const bank = qrImg.dataset.bank || 'VCB';
                    const account = qrImg.dataset.account || '';
                    const name = qrImg.dataset.name || '';
                    const desc = qrImg.dataset.desc || '';
                    const newUrl = `https://img.vietqr.io/image/${bank}-${account}-print.png?amount=${Math.round(finalTotal)}&addInfo=${desc}&accountName=${name}`;
                    qrImg.src = newUrl;
                }
                if (qrAmount) {
                    qrAmount.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(finalTotal)) + ' ₫';
                }
                
                // Cập nhật số tiền COD
                const codAmount = document.getElementById('cod-amount');
                if (codAmount) {
                    codAmount.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(finalTotal)) + ' đ';
                }
            };
            
            const updateFinalTotal = window.updateFinalTotal;

            function clearAllActive() {
                if (momoPaymentMethod) momoPaymentMethod.classList.remove('active');
                if (bankTransferMethod) bankTransferMethod.classList.remove('active');
                if (codPaymentMethod) codPaymentMethod.classList.remove('active');
                if (momoDetails) momoDetails.style.display = 'none';
                if (bankDetails) bankDetails.style.display = 'none';
                if (codDetails) codDetails.style.display = 'none';
            }

            if (momoPaymentMethod) {
                momoPaymentMethod.addEventListener('click', function() {
                    console.log('🚀 MoMo payment method clicked!');

                    const addressValidation = window.AddressHandler.validate();
                    if (!addressValidation.valid) {
                        showValidationToast(addressValidation.errors);
                        return;
                    }

                    const addressData = window.AddressHandler.getAddress();
                    const shippingAddress = addressData.full_address;

                    clearAllActive();
                    momoPaymentMethod.classList.add('active');
                    if (momoDetails) momoDetails.style.display = 'block';
                    selectedPaymentMethod = 'momo';
                    confirmPaymentBtn.textContent = 'Đang xử lý MoMo...';
                    confirmPaymentBtn.disabled = true;

                    processMoMoPayment(shippingAddress, addressData);
                });
            }

            if (bankTransferMethod) {
                bankTransferMethod.addEventListener('click', function() {
                    clearAllActive();
                    bankTransferMethod.classList.add('active');
                    if (bankDetails) bankDetails.style.display = 'block';
                    selectedPaymentMethod = 'bank-transfer';
                    confirmPaymentBtn.textContent = 'Xác nhận đã thanh toán';
                });
            }

            if (codPaymentMethod) {
                codPaymentMethod.addEventListener('click', function() {
                    clearAllActive();
                    codPaymentMethod.classList.add('active');
                    if (codDetails) codDetails.style.display = 'block';
                    selectedPaymentMethod = 'cod';
                    confirmPaymentBtn.textContent = 'Xác nhận đặt hàng COD';
                });
            }

            confirmPaymentBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('🔥 BUTTON CLICKED! Payment method:', selectedPaymentMethod);

                // Validate địa chỉ
                const addressValidation = window.AddressHandler.validate();
                if (!addressValidation.valid) {
                    showValidationToast(addressValidation.errors);
                    return;
                }

                const addressData = window.AddressHandler.getAddress();
                const shippingAddress = addressData.full_address;

                if (!shippingAddress) {
                    alert('Vui lòng nhập địa chỉ giao hàng!');
                    return;
                }

                if (selectedPaymentMethod === 'momo') {
                    console.log('✅ MoMo payment selected!');
                    processMoMoPayment(shippingAddress, addressData);
                    return;
                }

                if (selectedPaymentMethod === 'cod') {
                    console.log('✅ COD payment selected!');
                    processCODPayment(shippingAddress, addressData);
                    return;
                }

                confirmPaymentBtn.disabled = true;
                processingPayment.style.display = 'block';
                processBankTransferPayment(shippingAddress, addressData);
            });

            function processMoMoPayment(shippingAddress, addressData) {
                console.log('🚀 processMoMoPayment called!');
                console.log('Shipping address:', shippingAddress);
                console.log('Address data:', addressData);
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
                formData.append('order_notes', document.getElementById('orderNotes')?.value || '');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('receiver_name', addressData.receiver_name || '');
                formData.append('receiver_phone', addressData.receiver_phone || '');
                formData.append('province_id', addressData.province_id || '');
                formData.append('province_name', addressData.province_name || '');
                formData.append('district_id', addressData.district_id || '');
                formData.append('district_name', addressData.district_name || '');
                formData.append('ward_code', addressData.ward_code || '');
                formData.append('ward_name', addressData.ward_name || '');
                formData.append('detail_address', addressData.detail_address || '');
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

            function processBankTransferPayment(shippingAddress, addressData) {
                console.log('🏦 processBankTransferPayment called!');
                console.log('Shipping address:', shippingAddress);
                console.log('Address data:', addressData);
                
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
                formData.append('order_notes', document.getElementById('orderNotes')?.value || '');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('receiver_name', addressData.receiver_name || '');
                formData.append('receiver_phone', addressData.receiver_phone || '');
                formData.append('province_id', addressData.province_id || '');
                formData.append('province_name', addressData.province_name || '');
                formData.append('district_id', addressData.district_id || '');
                formData.append('district_name', addressData.district_name || '');
                formData.append('ward_code', addressData.ward_code || '');
                formData.append('ward_name', addressData.ward_name || '');
                formData.append('detail_address', addressData.detail_address || '');
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

            function processCODPayment(shippingAddress, addressData) {
                console.log('🚚 processCODPayment called!');
                console.log('Shipping address:', shippingAddress);
                console.log('Address data:', addressData);

                confirmPaymentBtn.disabled = true;
                processingPayment.style.display = 'block';
                
                const couponCode = document.getElementById('coupon_code_hidden')?.value || '';
                const couponDiscount = window.currentCouponDiscount || 0;
                const shippingFee = window.currentShippingFee || 0;

                const formData = new FormData();
                formData.append('payment_method', 'cod');
                formData.append('order_notes', document.getElementById('orderNotes')?.value || '');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('receiver_name', addressData.receiver_name || '');
                formData.append('receiver_phone', addressData.receiver_phone || '');
                formData.append('province_id', addressData.province_id || '');
                formData.append('province_name', addressData.province_name || '');
                formData.append('district_id', addressData.district_id || '');
                formData.append('district_name', addressData.district_name || '');
                formData.append('ward_code', addressData.ward_code || '');
                formData.append('ward_name', addressData.ward_name || '');
                formData.append('detail_address', addressData.detail_address || '');
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

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Điều khoản & Điều kiện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Điều kiện thanh toán</h6>
                    <p>- Tất cả giá sản phẩm đã bao gồm VAT 10%.</p>
                    <p>- Phí vận chuyển sẽ được tính theo phương thức vận chuyển bạn chọn.</p>
                    
                    <h6>2. Chính sách đổi trả</h6>
                    <p>- Sản phẩm được đổi trả trong vòng 7 ngày kể từ ngày nhận hàng.</p>
                    <p>- Sản phẩm phải còn nguyên vẹn, chưa qua sử dụng.</p>
                    
                    <h6>3. Bảo mật thông tin</h6>
                    <p>- Thông tin cá nhân của bạn được bảo mật tuyệt đối.</p>
                    <p>- Chúng tôi không chia sẻ thông tin cho bên thứ ba.</p>
                    
                    <h6>4. Xác nhận đơn hàng</h6>
                    <p>- Đơn hàng sẽ được xử lý sau khi thanh toán thành công.</p>
                    <p>- Bạn sẽ nhận thông báo qua email về trạng thái đơn hàng.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đã hiểu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Fullscreen Overlay -->
    <div class="qr-fullscreen-overlay" id="qrFullscreen">
        <button class="close-btn" onclick="closeQrFullscreen()">&times;</button>
        <div style="background: white; padding: 30px; border-radius: 15px; text-align: center; max-width: 95vw;">
            <img id="qrFullscreenImg" src="" alt="QR Code" style="width: 600px !important; max-width: 90vw !important; height: auto !important; display: block !important; margin: 0 auto !important;">
            <div style="margin-top: 15px; font-size: 16px; color: #333;">
                <p><i class="fas fa-mobile-alt me-1"></i> Quét mã QR bằng ứng dụng ngân hàng</p>
                <p style="color: #28a745; font-weight: bold;">Số tiền và nội dung đã được điền sẵn</p>
            </div>
        </div>
    </div>

    <script>
        // ===== TERMS CHECKBOX =====
        const agreeTerms = document.getElementById('agreeTerms');
        const confirmPaymentBtnFinal = document.getElementById('confirmPaymentBtn');
        
        if (agreeTerms && confirmPaymentBtnFinal) {
            agreeTerms.addEventListener('change', function() {
                confirmPaymentBtnFinal.disabled = !this.checked;
            });
        }

        // ===== LOCALSTORAGE CACHE FOR PROVINCES =====
        const CACHE_KEY = 'checkout_provinces_cache';
        const CACHE_EXPIRY = 24 * 60 * 60 * 1000; // 24 hours
        
        function getCachedProvinces() {
            try {
                const cached = localStorage.getItem(CACHE_KEY);
                if (cached) {
                    const data = JSON.parse(cached);
                    if (Date.now() - data.timestamp < CACHE_EXPIRY) {
                        return data.provinces;
                    }
                }
            } catch (e) {}
            return null;
        }
        
        function setCachedProvinces(provinces) {
            try {
                localStorage.setItem(CACHE_KEY, JSON.stringify({
                    provinces: provinces,
                    timestamp: Date.now()
                }));
            } catch (e) {}
        }

        function openQrFullscreen() {
            var qrImg = document.getElementById('qrCodeImg');
            if (qrImg) {
                var src = qrImg.getAttribute('src');
                var bigSrc = src.replace(/compact2\.png/g, 'print.png').replace(/compact\.png/g, 'print.png').replace(/qr_only\.png/g, 'print.png');
                var img = document.getElementById('qrFullscreenImg');
                img.removeAttribute('width');
                img.removeAttribute('height');
                img.src = bigSrc;
                document.getElementById('qrFullscreen').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
        function closeQrFullscreen() {
            document.getElementById('qrFullscreen').style.display = 'none';
            document.body.style.overflow = '';
        }
        document.getElementById('qrFullscreen').addEventListener('click', function(e) {
            if (e.target === this) closeQrFullscreen();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeQrFullscreen();
        });
    </script>
</body>

</html>