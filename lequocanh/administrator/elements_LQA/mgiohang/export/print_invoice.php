<?php

require_once __DIR__ . '/OrderExporter.php';

session_start();
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    http_response_code(403);
    die('Unauthorized - Please login first');
}

$exporter = new OrderExporter();
$orderId = intval($_GET['order_id'] ?? 0);

if (!$orderId) {
    die('Invalid order ID');
}

$order = $exporter->getOrderDetails($orderId);

if (!$order) {
    die('Order not found');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn - <?= htmlspecialchars($order['ma_don_hang_text']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .invoice-header { text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 20px; margin-bottom: 30px; }
        .company-name { font-size: 28px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .company-info { font-size: 13px; color: #7f8c8d; line-height: 1.6; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #3498db; margin: 20px 0; text-align: center; }
        .order-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-section { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .info-section h3 { font-size: 14px; color: #3498db; margin-bottom: 10px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .info-row { display: flex; margin-bottom: 8px; font-size: 13px; }
        .info-label { font-weight: 600; min-width: 120px; color: #555; }
        .info-value { color: #333; }
        .products-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .products-table th { background: #3498db; color: white; padding: 12px; text-align: left; font-size: 13px; }
        .products-table td { padding: 10px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
        .products-table tr:hover { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; text-align: right; }
        .summary-row { display: flex; justify-content: flex-end; margin-bottom: 10px; font-size: 14px; }
        .summary-label { min-width: 150px; text-align: right; padding-right: 20px; font-weight: 500; }
        .summary-value { min-width: 120px; text-align: right; }
        .total-row { font-size: 18px; font-weight: bold; color: #dc3545; border-top: 2px solid #dee2e6; padding-top: 10px; margin-top: 10px; }
        .payment-info { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0; color: #7f8c8d; font-size: 12px; }
        .print-button { background: #3498db; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; margin: 20px auto; display: block; }
        .print-button:hover { background: #2980b9; }
        
        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; padding: 20px; }
            .print-button { display: none; }
            .products-table tr:hover { background: transparent; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-name">LEQUOCANH SHOP</div>
            <div class="company-info">
                Địa chỉ: 123 Đường ABC, Quận XYZ, TP. Hồ Chí Minh<br>
                Điện thoại: 0123 456 789 | Email: info@lequocanh.com<br>
                Website: www.lequocanh.com
            </div>
        </div>
        
        <div class="invoice-title">HÓA ĐƠN BÁN HÀNG</div>
        
        <!-- Order Info -->
        <div class="order-info">
            <div class="info-section">
                <h3>Thông tin đơn hàng</h3>
                <div class="info-row">
                    <span class="info-label">Mã đơn hàng:</span>
                    <span class="info-value"><strong><?= htmlspecialchars($order['ma_don_hang_text']) ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày đặt:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($order['ngay_tao'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái:</span>
                    <span class="info-value"><?= htmlspecialchars($order['trang_thai']) ?></span>
                </div>
            </div>
            
            <div class="info-section">
                <h3>Thông tin khách hàng</h3>
                <div class="info-row">
                    <span class="info-label">Họ tên:</span>
                    <span class="info-value"><?= htmlspecialchars($order['ten_khach_hang'] ?? 'N/A') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Điện thoại:</span>
                    <span class="info-value"><?= htmlspecialchars($order['dien_thoai'] ?? 'N/A') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($order['email'] ?? 'N/A') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Địa chỉ:</span>
                    <span class="info-value"><?= htmlspecialchars($order['dia_chi_giao_hang'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
        
        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 50px;">STT</th>
                    <th>Sản phẩm</th>
                    <th style="width: 120px;" class="text-right">Đơn giá</th>
                    <th style="width: 80px;" class="text-center">Số lượng</th>
                    <th style="width: 120px;" class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                foreach ($order['items'] as $item): 
                    $subtotal = $item['gia'] * $item['so_luong'];
                ?>
                <tr>
                    <td class="text-center"><?= $stt++ ?></td>
                    <td><?= htmlspecialchars($item['tenhanghoa']) ?></td>
                    <td class="text-right"><?= number_format($item['gia']) ?> đ</td>
                    <td class="text-center"><?= $item['so_luong'] ?></td>
                    <td class="text-right"><?= number_format($subtotal) ?> đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span class="summary-label">Tạm tính:</span>
                <span class="summary-value"><?= number_format($order['tong_tien'] - ($order['phi_van_chuyen'] ?? 0)) ?> đ</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Phí vận chuyển:</span>
                <span class="summary-value"><?= number_format($order['phi_van_chuyen'] ?? 0) ?> đ</span>
            </div>
            <div class="summary-row total-row">
                <span class="summary-label">TỔNG CỘNG:</span>
                <span class="summary-value"><?= number_format($order['tong_tien']) ?> đ</span>
            </div>
        </div>
        
        <!-- Payment Info -->
        <div class="payment-info">
            <strong>Phương thức thanh toán:</strong> <?= strtoupper(htmlspecialchars($order['phuong_thuc_thanh_toan'])) ?><br>
            <strong>Trạng thái thanh toán:</strong> <?= htmlspecialchars($order['trang_thai_thanh_toan']) ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><em>Cảm ơn quý khách đã mua hàng tại LeQuocAnh Shop!</em></p>
            <p>Mọi thắc mắc xin vui lòng liên hệ: 0123 456 789</p>
        </div>
        
        <button class="print-button" onclick="window.print()">
            🖨️ In hóa đơn
        </button>
    </div>
    
    <script>

    </script>
</body>
</html>
