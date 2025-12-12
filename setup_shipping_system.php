<?php
/**
 * Script cài đặt hệ thống quản lý vận chuyển
 * Chạy file này để tạo database schema
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Cài đặt Hệ thống Vận chuyển</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { color: #27ae60; background: #d5f4e6; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #27ae60; }
        .error { color: #c0392b; background: #fadbd8; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #c0392b; }
        .info { color: #2980b9; background: #d6eaf8; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #2980b9; }
        .warning { color: #d68910; background: #fcf3cf; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #d68910; }
        .step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .step-title { font-weight: bold; color: #34495e; font-size: 18px; margin-bottom: 10px; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚚 Cài đặt Hệ thống Quản lý Vận chuyển</h1>";

try {
    $db = Database::getInstance()->getConnection();
    $sqlFile = file_get_contents('DB/shipping_system_schema.sql');
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sqlFile)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    echo "<div class='info'><strong>📊 Tổng số câu lệnh SQL:</strong> " . count($statements) . "</div>";
    
    foreach ($statements as $index => $statement) {
        try {
            $db->exec($statement);
            $successCount++;
            
            // Hiển thị tiến trình
            if (($index + 1) % 10 == 0) {
                echo "<div class='step'>✅ Đã thực thi " . ($index + 1) . "/" . count($statements) . " câu lệnh...</div>";
                flush();
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo "<div class='step'>";
    echo "<div class='step-title'>📈 Kết quả cài đặt</div>";
    echo "<table>";
    echo "<tr><th>Thành công</th><td class='success'><strong>$successCount</strong> câu lệnh</td></tr>";
    echo "<tr><th>Lỗi</th><td class='" . ($errorCount > 0 ? 'error' : 'success') . "'><strong>$errorCount</strong> câu lệnh</td></tr>";
    echo "</table>";
    echo "</div>";
    
    if ($errorCount > 0) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ Một số lỗi đã xảy ra (có thể bỏ qua nếu là lỗi 'already exists'):</strong>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li><strong>SQL:</strong> " . htmlspecialchars($error['statement']) . "<br>";
            echo "<strong>Lỗi:</strong> " . htmlspecialchars($error['error']) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Kiểm tra các bảng đã tạo
    echo "<div class='step'>";
    echo "<div class='step-title'>📋 Kiểm tra các bảng đã tạo</div>";
    
    $tables = [
        'provinces' => 'Tỉnh/Thành phố',
        'districts' => 'Quận/Huyện',
        'wards' => 'Phường/Xã',
        'shipping_zones' => 'Khu vực giao hàng',
        'shipping_methods' => 'Phương thức vận chuyển',
        'shipping_fees' => 'Cấu hình phí vận chuyển',
        'shipment_tracking' => 'Lịch sử vận chuyển'
    ];
    
    echo "<table>";
    echo "<tr><th>Bảng</th><th>Mô tả</th><th>Số bản ghi</th><th>Trạng thái</th></tr>";
    
    foreach ($tables as $table => $description) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'];
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$description</td>";
            echo "<td>$count</td>";
            echo "<td class='success'>✅ OK</td>";
            echo "</tr>";
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$description</td>";
            echo "<td>-</td>";
            echo "<td class='error'>❌ Lỗi</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // Kiểm tra dữ liệu mẫu
    echo "<div class='step'>";
    echo "<div class='step-title'>📦 Dữ liệu mẫu</div>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM provinces");
    $provinceCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_methods");
    $methodCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM shipping_fees");
    $feeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<ul>";
    echo "<li>✅ <strong>$provinceCount</strong> tỉnh/thành phố Việt Nam</li>";
    echo "<li>✅ <strong>$methodCount</strong> phương thức vận chuyển</li>";
    echo "<li>✅ <strong>$feeCount</strong> cấu hình phí mẫu</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($successCount > 0) {
        echo "<div class='success'>";
        echo "<h2>🎉 Cài đặt thành công!</h2>";
        echo "<p>Hệ thống quản lý vận chuyển đã được cài đặt hoàn tất.</p>";
        echo "<p><strong>Các tính năng đã sẵn sàng:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Quản lý khu vực giao hàng (63 tỉnh/thành Việt Nam)</li>";
        echo "<li>✅ Cấu hình phí vận chuyển linh hoạt</li>";
        echo "<li>✅ Phương thức vận chuyển (Tiêu chuẩn, Nhanh, Tiết kiệm)</li>";
        echo "<li>✅ Theo dõi lịch sử vận chuyển</li>";
        echo "<li>✅ Hoàn toàn MIỄN PHÍ, có thể mở rộng</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>📚 Bước tiếp theo:</h3>";
        echo "<ol>";
        echo "<li>Truy cập <strong>Admin > Quản lý Vận chuyển</strong> để cấu hình</li>";
        echo "<li>Thêm quận/huyện cho các tỉnh/thành</li>";
        echo "<li>Cấu hình phí vận chuyển theo khu vực</li>";
        echo "<li>Kiểm tra tính năng tại trang checkout</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin-top: 30px;'>";
        echo "<a href='lequocanh/administrator/index.php' class='btn'>🏠 Về trang Admin</a>";
        echo "<a href='check_shipping_system.php' class='btn'>🔍 Kiểm tra hệ thống</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Lỗi cài đặt</h2>";
    echo "<p><strong>Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>
