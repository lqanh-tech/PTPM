<?php
/**
 * Script sửa lỗi tổng tiền đơn hàng
 * Vấn đề: Một số đơn hàng có tổng tiền bị tính sai (VAT cộng 2 lần hoặc coupon không được trừ)
 * 
 * Công thức đúng: Tổng = Subtotal + VAT + Shipping - Coupon
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Sửa lỗi tổng tiền đơn hàng</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:10px;text-align:left;} .error{color:red;} .success{color:green;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h1>🔧 Sửa lỗi tổng tiền đơn hàng</h1>";

// Lấy tất cả đơn hàng có thông tin thuế và phí
$sql = "SELECT dh.*, 
        (SELECT SUM(cdh.gia * cdh.so_luong) FROM chi_tiet_don_hang cdh WHERE cdh.ma_don_hang = dh.id) as calculated_subtotal
        FROM don_hang dh 
        WHERE dh.thue > 0 OR dh.phi_van_chuyen > 0 OR dh.coupon_discount > 0
        ORDER BY dh.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>📋 Danh sách đơn hàng cần kiểm tra: " . count($orders) . " đơn</h2>";

$fixedCount = 0;
$errorOrders = [];

echo "<table>";
echo "<tr><th>ID</th><th>Mã đơn</th><th>Subtotal (tính)</th><th>VAT</th><th>Ship</th><th>Coupon</th><th>Tổng đúng</th><th>Tổng DB</th><th>Chênh lệch</th><th>Trạng thái</th></tr>";

foreach ($orders as $order) {
    $subtotal = floatval($order['calculated_subtotal'] ?? 0);
    $vat = floatval($order['thue'] ?? 0);
    $shipping = floatval($order['phi_van_chuyen'] ?? 0);
    $coupon = floatval($order['coupon_discount'] ?? 0);
    $dbTotal = floatval($order['tong_tien']);
    
    // Công thức đúng
    $correctTotal = $subtotal + $vat + $shipping - $coupon;
    $difference = $dbTotal - $correctTotal;
    
    $status = '';
    $statusClass = '';
    
    if (abs($difference) < 1) {
        $status = '✓ Đúng';
        $statusClass = 'success';
    } else {
        $statusClass = 'error';
        
        // Phân tích lỗi
        if (abs($difference - $vat) < 1) {
            $status = '❌ VAT cộng 2 lần';
        } elseif (abs($difference + $coupon) < 1) {
            $status = '❌ Coupon không trừ';
        } elseif (abs($difference - $shipping) < 1) {
            $status = '❌ Ship cộng 2 lần';
        } else {
            $status = '❌ Sai khác';
        }
        
        $errorOrders[] = [
            'id' => $order['id'],
            'correct_total' => $correctTotal,
            'db_total' => $dbTotal,
            'difference' => $difference
        ];
    }
    
    echo "<tr>";
    echo "<td>{$order['id']}</td>";
    echo "<td>{$order['ma_don_hang_text']}</td>";
    echo "<td>" . number_format($subtotal, 0, ',', '.') . "</td>";
    echo "<td>" . number_format($vat, 0, ',', '.') . "</td>";
    echo "<td>" . number_format($shipping, 0, ',', '.') . "</td>";
    echo "<td>" . number_format($coupon, 0, ',', '.') . "</td>";
    echo "<td class='success'>" . number_format($correctTotal, 0, ',', '.') . "</td>";
    echo "<td class='" . ($statusClass == 'error' ? 'error' : '') . "'>" . number_format($dbTotal, 0, ',', '.') . "</td>";
    echo "<td class='" . ($statusClass == 'error' ? 'error' : '') . "'>" . number_format($difference, 0, ',', '.') . "</td>";
    echo "<td class='$statusClass'>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Sửa các đơn hàng sai
if (!empty($errorOrders)) {
    echo "<h2>🔧 Sửa " . count($errorOrders) . " đơn hàng có tổng tiền sai</h2>";
    
    if (isset($_GET['fix']) && $_GET['fix'] == '1') {
        echo "<div style='background:#e8f5e9;padding:15px;border-radius:5px;margin:10px 0;'>";
        
        foreach ($errorOrders as $errorOrder) {
            $updateSql = "UPDATE don_hang SET tong_tien = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $result = $updateStmt->execute([$errorOrder['correct_total'], $errorOrder['id']]);
            
            if ($result) {
                $fixedCount++;
                echo "<p class='success'>✓ Đã sửa đơn hàng #{$errorOrder['id']}: " . 
                     number_format($errorOrder['db_total'], 0, ',', '.') . " → " . 
                     number_format($errorOrder['correct_total'], 0, ',', '.') . "</p>";
            } else {
                echo "<p class='error'>✗ Lỗi khi sửa đơn hàng #{$errorOrder['id']}</p>";
            }
        }
        
        echo "</div>";
        echo "<p><strong>Đã sửa $fixedCount đơn hàng!</strong></p>";
    } else {
        echo "<div style='background:#fff3e0;padding:15px;border-radius:5px;margin:10px 0;'>";
        echo "<p class='warning'>⚠️ Có " . count($errorOrders) . " đơn hàng cần sửa tổng tiền.</p>";
        echo "<p>Các đơn hàng sẽ được sửa:</p>";
        echo "<ul>";
        foreach ($errorOrders as $errorOrder) {
            echo "<li>Đơn #{$errorOrder['id']}: " . 
                 number_format($errorOrder['db_total'], 0, ',', '.') . " → " . 
                 number_format($errorOrder['correct_total'], 0, ',', '.') . 
                 " (chênh lệch: " . number_format($errorOrder['difference'], 0, ',', '.') . ")</li>";
        }
        echo "</ul>";
        echo "<a href='?fix=1' style='display:inline-block;background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-top:10px;'>🔧 Sửa tất cả đơn hàng</a>";
        echo "</div>";
    }
} else {
    echo "<div style='background:#e8f5e9;padding:15px;border-radius:5px;margin:10px 0;'>";
    echo "<p class='success'>✓ Tất cả đơn hàng đều có tổng tiền đúng!</p>";
    echo "</div>";
}

echo "</body></html>";
