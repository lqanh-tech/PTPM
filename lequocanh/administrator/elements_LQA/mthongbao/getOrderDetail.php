<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();

// Xác định đường dẫn đến file database.php
$paths = [
    '../mod/database.php',
    './elements_LQA/mod/database.php',
    './administrator/elements_LQA/mod/database.php'
];

$loaded = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không thể tải file database.php']);
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

// Kiểm tra tham số đầu vào
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['USER'];

// Kết nối database
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Lấy thông tin đơn hàng - sử dụng bảng don_hang
    $orderSql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId, $userId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này']);
        exit();
    }
    
    // Đánh dấu đơn hàng đã đọc - sử dụng bảng don_hang
    $status = $order['trang_thai']; // Đổi tên field
    $field = '';
    
    switch ($status) {
        case 'pending':
            $field = 'pending_read';
            break;
        case 'approved':
            $field = 'approved_read';
            break;
        case 'cancelled':
            $field = 'cancelled_read';
            break;
    }
    
    if (!empty($field)) {
        // Kiểm tra xem cột có tồn tại không
        $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE '$field'";
        $checkColumnStmt = $conn->prepare($checkColumnSql);
        $checkColumnStmt->execute();
        
        if ($checkColumnStmt->rowCount() > 0) {
            $updateReadSql = "UPDATE don_hang SET $field = 1 WHERE id = ? AND ma_nguoi_dung = ?";
            $updateReadStmt = $conn->prepare($updateReadSql);
            $updateReadStmt->execute([$orderId, $userId]);
        }
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng - sử dụng bảng chi_tiet_don_hang
    $orderItemsSql = "SELECT oi.*, h.tenhanghoa, h.hinhanh 
                     FROM chi_tiet_don_hang oi
                     JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                     WHERE oi.ma_don_hang = ?";
    $orderItemsStmt = $conn->prepare($orderItemsSql);
    $orderItemsStmt->execute([$orderId]);
    $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy thông tin thuế và phí vận chuyển
    $taxAmount = isset($order['thue']) ? floatval($order['thue']) : 0;
    $shippingFee = isset($order['phi_van_chuyen']) ? floatval($order['phi_van_chuyen']) : 0;
    $paymentStatus = isset($order['trang_thai_thanh_toan']) ? $order['trang_thai_thanh_toan'] : 'pending';
    $shippingMethod = isset($order['shipping_method']) ? $order['shipping_method'] : '';
    $shippingMethodName = isset($order['shipping_method_name']) ? $order['shipping_method_name'] : '';
    $estimatedDelivery = isset($order['estimated_delivery']) ? $order['estimated_delivery'] : '';
    
    // Tính tổng tiền hàng (subtotal) = tổng tiền - thuế - phí vận chuyển
    $subtotal = floatval($order['tong_tien']) - $taxAmount - $shippingFee;
    if ($subtotal < 0) $subtotal = floatval($order['tong_tien']); // Fallback nếu dữ liệu cũ
    
    // Format phương thức thanh toán
    $paymentMethodText = '';
    switch ($order['phuong_thuc_thanh_toan']) {
        case 'bank_transfer':
            $paymentMethodText = 'Chuyển khoản ngân hàng';
            break;
        case 'momo':
            $paymentMethodText = 'Ví MoMo';
            break;
        case 'cod':
            $paymentMethodText = 'Thanh toán khi nhận hàng (COD)';
            break;
        default:
            $paymentMethodText = $order['phuong_thuc_thanh_toan'];
    }
    
    // Format trạng thái thanh toán
    $paymentStatusText = '';
    switch ($paymentStatus) {
        case 'paid':
            $paymentStatusText = 'Đã thanh toán';
            break;
        case 'pending':
            $paymentStatusText = 'Chờ thanh toán';
            break;
        case 'failed':
            $paymentStatusText = 'Thanh toán thất bại';
            break;
        default:
            $paymentStatusText = $paymentStatus;
    }
    
    // Format phương thức vận chuyển
    $shippingMethodText = '';
    if (!empty($shippingMethodName)) {
        $shippingMethodText = $shippingMethodName;
    } else if (!empty($shippingMethod)) {
        switch ($shippingMethod) {
            case 'standard':
                $shippingMethodText = 'Giao hàng tiêu chuẩn';
                break;
            case 'express':
                $shippingMethodText = 'Giao hàng nhanh';
                break;
            case 'ghn':
                $shippingMethodText = 'Giao hàng nhanh (GHN)';
                break;
            case 'pickup':
                $shippingMethodText = 'Nhận tại cửa hàng';
                break;
            default:
                $shippingMethodText = $shippingMethod;
        }
    } else {
        $shippingMethodText = 'Không xác định';
    }
    
    // Định dạng lại thông tin đơn hàng - sử dụng field tiếng Việt
    $formattedOrder = [
        'id' => $order['id'],
        'order_code' => $order['ma_don_hang_text'],
        'total_amount' => $order['tong_tien'],
        'subtotal' => $subtotal,
        'tax_amount' => $taxAmount,
        'shipping_fee' => $shippingFee,
        'shipping_method' => $shippingMethod,
        'shipping_method_name' => $shippingMethodText,
        'estimated_delivery' => $estimatedDelivery,
        'status' => $order['trang_thai'],
        'status_text' => getStatusText($order['trang_thai']),
        'status_class' => getStatusClass($order['trang_thai']),
        'payment_method' => $paymentMethodText,
        'payment_status' => $paymentStatus,
        'payment_status_text' => $paymentStatusText,
        'created_at' => date('d/m/Y H:i', strtotime($order['ngay_tao'])),
        'updated_at' => isset($order['ngay_cap_nhat']) ? date('d/m/Y H:i', strtotime($order['ngay_cap_nhat'])) : '',
        'shipping_address' => $order['dia_chi_giao_hang'] ?? '',
        'items' => []
    ];
    
    // Định dạng lại thông tin sản phẩm - sử dụng field tiếng Việt
    foreach ($orderItems as $item) {
        // Xử lý đường dẫn hình ảnh - sử dụng displayImage.php
        $imageId = $item['hinhanh'];
        $imagePath = '';
        if (!empty($imageId) && $imageId > 0) {
            $imagePath = './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $imageId;
        } else {
            $imagePath = './administrator/elements_LQA/img_LQA/no-image.png';
        }
        
        $formattedOrder['items'][] = [
            'id' => $item['id'],
            'product_id' => $item['ma_san_pham'],
            'product_name' => $item['tenhanghoa'],
            'product_image' => $imagePath,
            'product_image_id' => $imageId,
            'quantity' => $item['so_luong'],
            'price' => $item['gia'],
            'total' => $item['gia'] * $item['so_luong']
        ];
    }
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'order' => $formattedOrder
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thông tin đơn hàng: ' . $e->getMessage()]);
}

// Hàm lấy text trạng thái
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Đang chờ xử lý';
        case 'approved':
            return 'Đã duyệt';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}

// Hàm lấy class CSS cho trạng thái
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
