<?php
/**
 * Sync Shipping Data
 * Đồng bộ dữ liệu giữa admin config và checkout display
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔄 Đồng bộ dữ liệu vận chuyển</h1>";
echo "<hr>";

try {
    // 1. Kiểm tra dữ liệu hiện tại
    echo "<h2>📊 Dữ liệu hiện tại:</h2>";
    
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Code</th><th>Tên</th><th>Mô tả</th><th>Thời gian</th><th>Hệ số giá</th><th>Thứ tự</th><th>Active</th>";
    echo "</tr>";
    
    foreach ($methods as $method) {
        echo "<tr>";
        echo "<td><code>{$method['code']}</code></td>";
        echo "<td><strong>{$method['name']}</strong></td>";
        echo "<td>{$method['description']}</td>";
        echo "<td>{$method['delivery_time']}</td>";
        echo "<td>{$method['price_multiplier']}x</td>";
        echo "<td>{$method['sort_order']}</td>";
        echo "<td>" . ($method['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // 2. Cập nhật để đồng bộ
    echo "<h2>🔧 Cập nhật đồng bộ:</h2>";
    
    $updates = [
        [
            'code' => 'standard',
            'name' => 'Giao hàng tiêu chuẩn',
            'description' => 'Giao hàng trong 3-5 ngày làm việc',
            'delivery_time' => '3-5 ngày',
            'price_multiplier' => 1.0,
            'sort_order' => 2
        ],
        [
            'code' => 'express',
            'name' => 'Giao hàng nhanh',
            'description' => 'Giao hàng trong 1-2 ngày làm việc',
            'delivery_time' => '1-2 ngày',
            'price_multiplier' => 1.5,
            'sort_order' => 1
        ],
        [
            'code' => 'pickup',
            'name' => 'Lấy tại cửa hàng',
            'description' => 'Đến lấy hàng tại cửa hàng - Miễn phí',
            'delivery_time' => '0-1 ngày',
            'price_multiplier' => 0.0,
            'sort_order' => 3
        ],
        [
            'code' => 'ghn',
            'name' => 'Giao Hàng Nhanh (GHN)',
            'description' => 'Vận chuyển qua đối tác GHN',
            'delivery_time' => '1-3 ngày',
            'price_multiplier' => 1.2,
            'sort_order' => 4
        ]
    ];
    
    foreach ($updates as $update) {
        $stmt = $db->prepare("
            UPDATE shipping_methods 
            SET name = ?, 
                description = ?, 
                delivery_time = ?, 
                price_multiplier = ?,
                sort_order = ?
            WHERE code = ?
        ");
        
        $result = $stmt->execute([
            $update['name'],
            $update['description'],
            $update['delivery_time'],
            $update['price_multiplier'],
            $update['sort_order'],
            $update['code']
        ]);
        
        if ($result) {
            echo "✅ Cập nhật <code>{$update['code']}</code>: {$update['name']}<br>";
        } else {
            echo "❌ Lỗi cập nhật <code>{$update['code']}</code><br>";
        }
    }
    
    echo "<hr>";
    
    // 3. Kiểm tra shipping_fees
    echo "<h2>💰 Cấu hình phí vận chuyển:</h2>";
    
    $stmt = $db->query("
        SELECT sf.*, sm.name as method_name, sm.code as method_code
        FROM shipping_fees sf
        LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
        ORDER BY sf.priority DESC
    ");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Tên</th><th>Phương thức</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Ưu tiên</th>";
    echo "</tr>";
    
    foreach ($fees as $fee) {
        echo "<tr>";
        echo "<td><strong>{$fee['name']}</strong></td>";
        echo "<td>{$fee['method_name']} ({$fee['method_code']})</td>";
        echo "<td>" . number_format($fee['base_fee'], 0, ',', '.') . "₫</td>";
        echo "<td>" . number_format($fee['fee_per_kg'], 0, ',', '.') . "₫</td>";
        echo "<td>" . ($fee['min_order_free_ship'] ? '≥ ' . number_format($fee['min_order_free_ship'], 0, ',', '.') . '₫' : '-') . "</td>";
        echo "<td><span style='background: #17a2b8; color: white; padding: 3px 8px; border-radius: 4px;'>{$fee['priority']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // 4. Đề xuất cấu hình phí mẫu
    echo "<h2>💡 Đề xuất cấu hình phí:</h2>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
    echo "<h3>Để đồng bộ với checkout, cần cấu hình:</h3>";
    echo "<ul>";
    echo "<li><strong>Giao hàng tiêu chuẩn:</strong> 25,000₫ (phí cơ bản) x 1.0 = 25,000₫</li>";
    echo "<li><strong>Giao hàng nhanh:</strong> 30,000₫ (phí cơ bản) x 1.5 = 45,000₫</li>";
    echo "<li><strong>Lấy tại cửa hàng:</strong> 0₫ (miễn phí)</li>";
    echo "<li><strong>GHN:</strong> Tính theo API GHN</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    
    // 5. Dữ liệu sau khi cập nhật
    echo "<h2>✅ Dữ liệu sau khi đồng bộ:</h2>";
    
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order DESC");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #d4edda;'>";
    echo "<th>Code</th><th>Tên</th><th>Mô tả</th><th>Thời gian</th><th>Hệ số giá</th><th>Thứ tự</th><th>Active</th>";
    echo "</tr>";
    
    foreach ($methods as $method) {
        echo "<tr>";
        echo "<td><code>{$method['code']}</code></td>";
        echo "<td><strong>{$method['name']}</strong></td>";
        echo "<td>{$method['description']}</td>";
        echo "<td>{$method['delivery_time']}</td>";
        echo "<td>{$method['price_multiplier']}x</td>";
        echo "<td>{$method['sort_order']}</td>";
        echo "<td>" . ($method['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Đồng bộ hoàn tất!</h2>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>📋 Checklist đồng bộ:</h3>";
    echo "<ul>";
    echo "<li>✅ Tên phương thức đã nhất quán</li>";
    echo "<li>✅ Thời gian giao đã cập nhật</li>";
    echo "<li>✅ Hệ số giá đã đồng bộ</li>";
    echo "<li>✅ Thứ tự hiển thị đã sắp xếp</li>";
    echo "</ul>";
    echo "<p><strong>Lưu ý:</strong> Cần cấu hình phí cơ bản trong bảng <code>shipping_fees</code> để phí hiển thị đúng trên checkout.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; color: #721c24;'>";
    echo "<h2>❌ Lỗi:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
