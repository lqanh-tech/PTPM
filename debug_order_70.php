<?php
/**
 * Debug đơn hàng #70 để tìm lỗi tính tổng tiền
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>🔍 Debug Đơn hàng #70</h2>";

// Lấy thông tin đơn hàng
$sql = "SELECT * FROM don_hang WHERE id = 70";
$stmt = $conn->prepare($sql);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p style='color:red'>Không tìm thấy đơn hàng #70</p>";
    
    // Lấy đơn hàng gần nhất
    $sql = "SELECT * FROM don_hang ORDER BY id DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>5 đơn hàng gần nhất:</h3>";
    echo "<pre>" . print_r($orders, true) . "</pre>";
    exit;
}

echo "<h3>📋 Thông tin đơn hàng từ Database:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><td><strong>ID</strong></td><td>{$order['id']}</td></tr>";
echo "<tr><td><strong>Mã đơn hàng</strong></td><td>{$order['ma_don_hang_text']}</td></tr>";
echo "<tr><td><strong>Tổng tiền (DB)</strong></td><td style='color:red;font-weight:bold'>" . number_format($order['tong_tien'], 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td><strong>Thuế VAT</strong></td><td>" . number_format($order['thue'] ?? 0, 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td><strong>Phí vận chuyển</strong></td><td>" . number_format($order['phi_van_chuyen'] ?? 0, 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td><strong>Coupon Code</strong></td><td>" . ($order['coupon_code'] ?? 'Không có') . "</td></tr>";
echo "<tr><td><strong>Coupon Discount</strong></td><td>" . number_format($order['coupon_discount'] ?? 0, 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td><strong>Phương thức TT</strong></td><td>{$order['phuong_thuc_thanh_toan']}</td></tr>";
echo "<tr><td><strong>Ngày tạo</strong></td><td>{$order['ngay_tao']}</td></tr>";
echo "</table>";

// Lấy chi tiết sản phẩm
$itemsSql = "SELECT oi.*, h.tenhanghoa 
             FROM chi_tiet_don_hang oi
             JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
             WHERE oi.ma_don_hang = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->execute([70]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>📦 Chi tiết sản phẩm:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr>";

$subtotal = 0;
foreach ($items as $item) {
    $itemTotal = $item['gia'] * $item['so_luong'];
    $subtotal += $itemTotal;
    echo "<tr>";
    echo "<td>{$item['tenhanghoa']}</td>";
    echo "<td>" . number_format($item['gia'], 0, ',', '.') . " ₫</td>";
    echo "<td>{$item['so_luong']}</td>";
    echo "<td>" . number_format($itemTotal, 0, ',', '.') . " ₫</td>";
    echo "</tr>";
}
echo "</table>";

// Tính toán đúng
$vatAmount = $order['thue'] ?? 0;
$shippingFee = $order['phi_van_chuyen'] ?? 0;
$couponDiscount = $order['coupon_discount'] ?? 0;

// Công thức đúng: Subtotal + VAT + Shipping - Coupon
$correctTotal = $subtotal + $vatAmount + $shippingFee - $couponDiscount;

echo "<h3>🧮 Tính toán:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><td>Tạm tính (Subtotal)</td><td>" . number_format($subtotal, 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td>Thuế VAT (10%)</td><td>" . number_format($vatAmount, 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td>Phí vận chuyển</td><td>" . number_format($shippingFee, 0, ',', '.') . " ₫</td></tr>";
echo "<tr><td>Giảm giá Coupon</td><td>-" . number_format($couponDiscount, 0, ',', '.') . " ₫</td></tr>";
echo "<tr style='background:#f0f0f0'><td><strong>Tổng đúng (Subtotal + VAT + Ship - Coupon)</strong></td><td style='color:green;font-weight:bold'>" . number_format($correctTotal, 0, ',', '.') . " ₫</td></tr>";
echo "<tr style='background:#ffe0e0'><td><strong>Tổng trong DB</strong></td><td style='color:red;font-weight:bold'>" . number_format($order['tong_tien'], 0, ',', '.') . " ₫</td></tr>";
echo "</table>";

$difference = $order['tong_tien'] - $correctTotal;
echo "<h3>⚠️ Phân tích lỗi:</h3>";
echo "<p><strong>Chênh lệch:</strong> " . number_format($difference, 0, ',', '.') . " ₫</p>";

if ($difference == $vatAmount) {
    echo "<p style='color:red;font-weight:bold'>🔴 LỖI: VAT bị cộng 2 lần!</p>";
    echo "<p>Nguyên nhân: Tổng tiền = (Subtotal + VAT) + VAT + Ship - Coupon thay vì Subtotal + VAT + Ship - Coupon</p>";
} elseif ($difference == $shippingFee) {
    echo "<p style='color:red;font-weight:bold'>🔴 LỖI: Phí ship bị cộng 2 lần!</p>";
} elseif ($difference == -$couponDiscount) {
    echo "<p style='color:red;font-weight:bold'>🔴 LỖI: Coupon không được trừ!</p>";
} else {
    echo "<p>Cần kiểm tra thêm logic tính toán...</p>";
}

// Kiểm tra cột coupon trong bảng don_hang
echo "<h3>📊 Kiểm tra cấu trúc bảng don_hang:</h3>";
$checkCouponCol = "SHOW COLUMNS FROM don_hang LIKE 'coupon%'";
$stmt = $conn->prepare($checkCouponCol);
$stmt->execute();
$couponCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($couponCols, true) . "</pre>";
