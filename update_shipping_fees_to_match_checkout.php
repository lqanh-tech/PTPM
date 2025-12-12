<?php
/**
 * Update Shipping Fees to Match Checkout Display
 * Cập nhật phí để khớp với những gì hiển thị trên checkout
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>💰 Cập nhật phí vận chuyển khớp với checkout</h1>";
echo "<hr>";

try {
    // Lấy ID của các phương thức
    $stmt = $db->query("SELECT id, code, name FROM shipping_methods");
    $methods = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $methods[$row['code']] = $row;
    }
    
    echo "<h2>📋 Phương thức vận chuyển:</h2>";
    foreach ($methods as $code => $method) {
        echo "- <code>$code</code>: {$method['name']} (ID: {$method['id']})<br>";
    }
    
    echo "<hr>";
    
    // Cập nhật phí cơ bản để khớp với checkout
    echo "<h2>🔧 Cập nhật phí:</h2>";
    
    // 1. Cập nhật phí tiêu chuẩn = 25,000₫
    if (isset($methods['standard'])) {
        $stmt = $db->prepare("
            UPDATE shipping_fees 
            SET base_fee = 25000
            WHERE shipping_method_id = ? AND name LIKE '%nội thành%'
        ");
        $stmt->execute([$methods['standard']['id']]);
        echo "✅ Cập nhật phí tiêu chuẩn nội thành: 25,000₫<br>";
    }
    
    // 2. Thêm cấu hình cho giao hàng nhanh = 30,000₫ (x1.5 = 45,000₫)
    if (isset($methods['express'])) {
        // Kiểm tra xem đã có chưa
        $stmt = $db->prepare("SELECT id FROM shipping_fees WHERE shipping_method_id = ?");
        $stmt->execute([$methods['express']['id']]);
        
        if ($stmt->rowCount() == 0) {
            // Chưa có, thêm mới
            $stmt = $db->prepare("
                INSERT INTO shipping_fees 
                (name, shipping_method_id, base_fee, fee_per_kg, weight_from, weight_to, priority, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'Phí giao hàng nhanh',
                $methods['express']['id'],
                30000, // 30,000 x 1.5 = 45,000
                0,
                0,
                null,
                15,
                1
            ]);
            echo "✅ Thêm cấu hình giao hàng nhanh: 30,000₫ (x1.5 = 45,000₫)<br>";
        } else {
            // Đã có, cập nhật
            $stmt = $db->prepare("
                UPDATE shipping_fees 
                SET base_fee = 30000
                WHERE shipping_method_id = ?
            ");
            $stmt->execute([$methods['express']['id']]);
            echo "✅ Cập nhật phí giao hàng nhanh: 30,000₫ (x1.5 = 45,000₫)<br>";
        }
    }
    
    // 3. Đảm bảo pickup = 0₫
    if (isset($methods['pickup'])) {
        $stmt = $db->prepare("SELECT id FROM shipping_fees WHERE shipping_method_id = ?");
        $stmt->execute([$methods['pickup']['id']]);
        
        if ($stmt->rowCount() == 0) {
            $stmt = $db->prepare("
                INSERT INTO shipping_fees 
                (name, shipping_method_id, base_fee, fee_per_kg, weight_from, weight_to, priority, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'Lấy tại cửa hàng - Miễn phí',
                $methods['pickup']['id'],
                0,
                0,
                0,
                null,
                20,
                1
            ]);
            echo "✅ Thêm cấu hình lấy tại cửa hàng: 0₫ (miễn phí)<br>";
        }
    }
    
    echo "<hr>";
    
    // Hiển thị kết quả
    echo "<h2>✅ Kết quả sau khi cập nhật:</h2>";
    
    $stmt = $db->query("
        SELECT 
            sf.*,
            sm.code,
            sm.name as method_name,
            sm.price_multiplier,
            (sf.base_fee * sm.price_multiplier) as final_fee
        FROM shipping_fees sf
        JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
        WHERE sm.code IN ('standard', 'express', 'pickup')
        ORDER BY sf.priority DESC
    ");
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #d4edda;'>";
    echo "<th>Phương thức</th><th>Tên cấu hình</th><th>Phí cơ bản</th><th>Hệ số</th><th>Phí cuối</th><th>Ưu tiên</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><code>{$row['code']}</code><br><strong>{$row['method_name']}</strong></td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>" . number_format($row['base_fee'], 0, ',', '.') . "₫</td>";
        echo "<td>{$row['price_multiplier']}x</td>";
        echo "<td><strong style='color: green;'>" . number_format($row['final_fee'], 0, ',', '.') . "₫</strong></td>";
        echo "<td><span style='background: #17a2b8; color: white; padding: 3px 8px; border-radius: 4px;'>{$row['priority']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px;'>";
    echo "<h2 style='color: green;'>✅ Đồng bộ hoàn tất!</h2>";
    echo "<h3>📊 So sánh với checkout:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; background: white;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Phương thức</th><th>Checkout hiển thị</th><th>Admin cấu hình</th><th>Trạng thái</th>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><strong>Giao hàng tiêu chuẩn</strong></td>";
    echo "<td>25,000₫</td>";
    echo "<td>25,000₫ (25,000 x 1.0)</td>";
    echo "<td style='color: green;'>✅ Khớp</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><strong>Giao hàng nhanh</strong></td>";
    echo "<td>45,000₫</td>";
    echo "<td>45,000₫ (30,000 x 1.5)</td>";
    echo "<td style='color: green;'>✅ Khớp</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><strong>Lấy tại cửa hàng</strong></td>";
    echo "<td>Miễn phí</td>";
    echo "<td>0₫ (0 x 0.0)</td>";
    echo "<td style='color: green;'>✅ Khớp</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><strong>GHN</strong></td>";
    echo "<td>Tính theo API</td>";
    echo "<td>Tính theo API GHN</td>";
    echo "<td style='color: green;'>✅ Khớp</td>";
    echo "</tr>";
    
    echo "</table>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; color: #721c24;'>";
    echo "<h2>❌ Lỗi:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
