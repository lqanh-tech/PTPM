<?php
/**
 * Sửa dữ liệu đơn hàng cũ - Tính toán lại thuế và phí vận chuyển
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<h1>Sửa dữ liệu đơn hàng cũ</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Lấy tất cả đơn hàng không có dữ liệu thuế/phí vận chuyển
    $sql = "SELECT dh.id, dh.ma_don_hang_text, dh.tong_tien, dh.thue, dh.phi_van_chuyen
            FROM don_hang dh
            WHERE (dh.thue IS NULL OR dh.thue = 0) 
               OR (dh.phi_van_chuyen IS NULL OR dh.phi_van_chuyen = 0)
            ORDER BY dh.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Tìm thấy " . count($orders) . " đơn hàng cần cập nhật</h2>";
    
    if (empty($orders)) {
        echo "<p class='success'>✅ Tất cả đơn hàng đã có đầy đủ thông tin thuế và phí vận chuyển!</p>";
        exit;
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Mã đơn hàng</th><th>Tổng tiền</th><th>Tạm tính</th><th>Thuế cũ</th><th>Phí VC cũ</th><th>Thuế mới</th><th>Phí VC mới</th><th>Trạng thái</th></tr>";
    
    $conn->beginTransaction();
    
    foreach ($orders as $order) {
        // Lấy chi tiết sản phẩm
        $itemsSql = "SELECT gia, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính tạm tính (subtotal)
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['gia'] * $item['so_luong'];
        }
        
        // Tính thuế VAT 10%
        $newTax = $subtotal * 0.10;
        
        // Tính phí vận chuyển = Tổng tiền - Tạm tính - Thuế
        $newShippingFee = $order['tong_tien'] - $subtotal - $newTax;
        
        // Đảm bảo phí vận chuyển không âm
        if ($newShippingFee < 0) {
            $newShippingFee = 0;
            // Điều chỉnh lại: Nếu tổng tiền < subtotal + thuế, có thể đơn hàng cũ không tính thuế
            $newTax = $order['tong_tien'] - $subtotal;
            if ($newTax < 0) $newTax = 0;
        }
        
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['ma_don_hang_text']}</td>";
        echo "<td>" . number_format($order['tong_tien'], 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($subtotal, 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($order['thue'] ?? 0, 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($order['phi_van_chuyen'] ?? 0, 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($newTax, 0, ',', '.') . " đ</td>";
        echo "<td>" . number_format($newShippingFee, 0, ',', '.') . " đ</td>";
        
        // Cập nhật database
        try {
            $updateSql = "UPDATE don_hang SET thue = ?, phi_van_chuyen = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$newTax, $newShippingFee, $order['id']]);
            echo "<td class='success'>✅ Đã cập nhật</td>";
        } catch (Exception $e) {
            echo "<td class='error'>❌ Lỗi: " . $e->getMessage() . "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    $conn->commit();
    
    echo "<h2 class='success'>✅ Hoàn tất cập nhật!</h2>";
    echo "<p><a href='lequocanh/administrator/index.php?req=don_hang'>← Quay lại quản lý đơn hàng</a></p>";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "<p class='error'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
