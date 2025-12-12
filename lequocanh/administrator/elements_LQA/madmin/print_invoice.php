<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    // Điều hướng về trang đăng nhập (nằm ở thư mục administrator)
    header('Location: ../../userLogin.php');
    exit();
}

// Tắt hiển thị lỗi
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Sử dụng __DIR__ để định vị đúng đường dẫn file
require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/hanghoaCls.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    die('<div class="alert alert-danger">Không thể kết nối đến cơ sở dữ liệu.</div>');
}

// Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('<div class="alert alert-danger">Không tìm thấy đơn hàng.</div>');
}

$orderId = (int)$_GET['id'];

// Lấy thông tin đơn hàng
// Nếu là user thường, chỉ xem được đơn của mình
if (isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    $orderSql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId, $_SESSION['USER']]);
} else {
    $orderSql = "SELECT * FROM don_hang WHERE id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId]);
}

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<div class="alert alert-danger">Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.</div>');
}

// Lấy thông tin chi tiết đơn hàng
$orderItemsSql = "SELECT oi.*, h.tenhanghoa 
                 FROM chi_tiet_don_hang oi
                 JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                 WHERE oi.ma_don_hang = ?";
$orderItemsStmt = $conn->prepare($orderItemsSql);
$orderItemsStmt->execute([$orderId]);
$orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin khách hàng nếu có
$customerName = "Khách vãng lai";
$customerPhone = "";
$customerEmail = "";

if (isset($order['ma_nguoi_dung']) && !empty($order['ma_nguoi_dung'])) {
    $userSql = "SELECT * FROM user WHERE username = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$order['ma_nguoi_dung']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $customerName = $user['hoten'] ?? $order['ma_nguoi_dung'];
        $customerPhone = $user['dienthoai'] ?? "";
        $customerEmail = $user['email'] ?? "";
    }
}

// Lấy thông tin cửa hàng (Hardcoded hoặc lấy từ DB cấu hình nếu có)
$shopName = "Cửa hàng điện thoại LQA";
$shopAddress = "123 Đường ABC, Quận Ninh Kiều, TP. Cần Thơ";
$shopPhone = "0123 456 789";
$shopEmail = "contact@lqa-store.com";

// Định dạng ngày tháng
$orderDate = date('d/m/Y H:i', strtotime($order['ngay_tao']));

// Tính toán các khoản tiền
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['gia'] * $item['so_luong'];
}

$taxAmount = isset($order['thue']) ? floatval($order['thue']) : 0;
$shippingFee = isset($order['phi_van_chuyen']) ? floatval($order['phi_van_chuyen']) : 0;
$shippingMethodName = isset($order['shipping_method_name']) ? $order['shipping_method_name'] : '';
$couponCode = isset($order['coupon_code']) ? $order['coupon_code'] : null;
$couponDiscount = isset($order['coupon_discount']) ? floatval($order['coupon_discount']) : 0;

// Định dạng trạng thái đơn hàng
$orderStatus = "";
switch ($order['trang_thai']) {
    case 'pending':
        $orderStatus = "Chờ xác nhận";
        break;
    case 'approved':
        $orderStatus = "Đang giao hàng";
        break;
    case 'delivered':
        $orderStatus = "Đã giao hàng";
        break;
    case 'completed':
        $orderStatus = "Hoàn tất";
        break;
    case 'cancelled':
        $orderStatus = "Đã hủy";
        break;
    default:
        $orderStatus = "Không xác định";
}

// Định dạng phương thức thanh toán
$paymentMethod = "";
switch ($order['phuong_thuc_thanh_toan']) {
    case 'bank_transfer':
        $paymentMethod = "Chuyển khoản ngân hàng";
        break;
    case 'cod':
        $paymentMethod = "Thanh toán khi nhận hàng (COD)";
        break;
    case 'momo':
        $paymentMethod = "Ví MoMo";
        break;
    default:
        $paymentMethod = $order['phuong_thuc_thanh_toan'];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #<?php echo $order['id']; ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .invoice-header h1 {
            color: #000;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 24px;
        }

        .invoice-title {
            text-align: center;
            margin: 20px 0;
        }

        .invoice-title h2 {
            font-size: 28px;
            margin: 0;
            text-transform: uppercase;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .invoice-info-col {
            flex: 1;
        }

        .invoice-info-col h3 {
            margin-top: 0;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .invoice-items th,
        .invoice-items td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .invoice-items th {
            background-color: #f9f9f9;
            font-weight: bold;
            border-top: 1px solid #ddd;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .invoice-summary {
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }

        .invoice-summary-table {
            width: 50%;
            border-collapse: collapse;
        }

        .invoice-summary-table td {
            padding: 5px 10px;
            text-align: right;
        }

        .invoice-summary-table tr.total td {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }

        .invoice-footer {
            margin-top: 50px;
            text-align: center;
            font-style: italic;
            color: #777;
        }

        .print-button {
            text-align: center;
            margin-top: 30px;
        }

        .print-button button {
            padding: 10px 25px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .print-button button:hover {
            background-color: #0056b3;
        }

        .print-button button.close-btn {
            background-color: #6c757d;
            margin-left: 10px;
        }

        .print-button button.close-btn:hover {
            background-color: #5a6268;
        }

        @media print {
            .print-button {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
                background-color: #fff;
            }

            .invoice-container {
                box-shadow: none;
                border: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><?php echo $shopName; ?></h1>
            <p><?php echo $shopAddress; ?></p>
            <p>Điện thoại: <?php echo $shopPhone; ?> | Email: <?php echo $shopEmail; ?></p>
        </div>

        <div class="invoice-title">
            <h2>HÓA ĐƠN BÁN HÀNG</h2>
            <p>Mã đơn hàng: <strong><?php echo $order['ma_don_hang_text']; ?></strong></p>
            <p>Ngày đặt: <?php echo $orderDate; ?></p>
        </div>

        <div class="invoice-info">
            <div class="invoice-info-col">
                <h3>Thông tin khách hàng</h3>
                <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($customerName); ?></p>
                <?php if (!empty($customerPhone)): ?>
                    <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($customerPhone); ?></p>
                <?php endif; ?>
                <?php if (!empty($customerEmail)): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($customerEmail); ?></p>
                <?php endif; ?>
                <?php if (isset($order['dia_chi_giao_hang']) && !empty($order['dia_chi_giao_hang'])): ?>
                    <p><strong>Địa chỉ giao hàng:</strong><br><?php echo nl2br(htmlspecialchars($order['dia_chi_giao_hang'])); ?></p>
                <?php elseif (isset($order['shipping_address']) && !empty($order['shipping_address'])): ?>
                    <p><strong>Địa chỉ giao hàng:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="invoice-info-col" style="text-align: right;">
                <h3>Thông tin thanh toán</h3>
                <p><strong>Phương thức:</strong> <?php echo $paymentMethod; ?></p>
                <p><strong>Trạng thái:</strong> <?php echo $orderStatus; ?></p>
                <?php if (!empty($shippingMethodName)): ?>
                    <p><strong>Vận chuyển:</strong> <?php echo htmlspecialchars($shippingMethodName); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table class="invoice-items">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">STT</th>
                    <th>Sản phẩm</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-center">SL</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $index => $item): ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['tenhanghoa']); ?></td>
                        <td class="text-right"><?php echo number_format($item['gia'], 0, ',', '.'); ?> ₫</td>
                        <td class="text-center"><?php echo $item['so_luong']; ?></td>
                        <td class="text-right"><?php echo number_format($item['gia'] * $item['so_luong'], 0, ',', '.'); ?> ₫</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="invoice-summary">
            <table class="invoice-summary-table">
                <tr>
                    <td>Tạm tính:</td>
                    <td><?php echo number_format($subtotal, 0, ',', '.'); ?> ₫</td>
                </tr>
                <?php if ($taxAmount > 0): ?>
                <tr>
                    <td>Thuế VAT (10%):</td>
                    <td><?php echo number_format($taxAmount, 0, ',', '.'); ?> ₫</td>
                </tr>
                <?php endif; ?>
                <?php if ($shippingFee > 0): ?>
                <tr>
                    <td>Phí vận chuyển:</td>
                    <td><?php echo number_format($shippingFee, 0, ',', '.'); ?> ₫</td>
                </tr>
                <?php endif; ?>
                <?php if ($couponCode && $couponDiscount > 0): ?>
                <tr style="color: #28a745;">
                    <td>Mã giảm giá (<?php echo htmlspecialchars($couponCode); ?>):</td>
                    <td>-<?php echo number_format($couponDiscount, 0, ',', '.'); ?> ₫</td>
                </tr>
                <?php endif; ?>
                <tr class="total">
                    <td>Tổng cộng:</td>
                    <td><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫</td>
                </tr>
            </table>
        </div>

        <div class="invoice-footer">
            <p>Cảm ơn quý khách đã mua hàng!</p>
            <p>Hóa đơn này được tạo tự động từ hệ thống.</p>
        </div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">In hóa đơn</button>
        <button class="close-btn" onclick="window.close()">Đóng</button>
    </div>
</body>

</html>