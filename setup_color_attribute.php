<?php
/**
 * Script thiết lập thuộc tính Màu sắc cho hệ thống
 * Tạo thuộc tính "Màu sắc" nếu chưa có và cập nhật ID trong code
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Thiết lập thuộc tính Màu sắc</h2>";
    
    // 1. Kiểm tra xem thuộc tính "Màu sắc" đã tồn tại chưa
    $checkStmt = $db->prepare("SELECT idThuocTinh, tenThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%'");
    $checkStmt->execute();
    $existingColors = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($existingColors)) {
        echo "<h3>✓ Đã tìm thấy thuộc tính màu sắc:</h3>";
        echo "<ul>";
        foreach ($existingColors as $color) {
            echo "<li>ID: {$color['idThuocTinh']} - Tên: {$color['tenThuocTinh']}</li>";
        }
        echo "</ul>";
        
        $colorAttributeId = $existingColors[0]['idThuocTinh'];
    } else {
        // Tạo thuộc tính mới
        echo "<h3>Tạo thuộc tính Màu sắc mới...</h3>";
        $insertStmt = $db->prepare("INSERT INTO thuoctinh (tenThuocTinh, ghiChu) VALUES (?, ?)");
        $insertStmt->execute(['Màu sắc', 'Thuộc tính màu sắc sản phẩm']);
        $colorAttributeId = $db->lastInsertId();
        echo "<p>✓ Đã tạo thuộc tính Màu sắc với ID: $colorAttributeId</p>";
    }
    
    // 2. Lấy danh sách tất cả thuộc tính
    echo "<h3>Danh sách tất cả thuộc tính:</h3>";
    $allAttrs = $db->query("SELECT * FROM thuoctinh ORDER BY idThuocTinh")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Tên thuộc tính</th><th>Ghi chú</th></tr>";
    foreach ($allAttrs as $attr) {
        $highlight = ($attr['idThuocTinh'] == $colorAttributeId) ? " style='background-color: #ffff99;'" : "";
        echo "<tr$highlight>";
        echo "<td>{$attr['idThuocTinh']}</td>";
        echo "<td>{$attr['tenThuocTinh']}</td>";
        echo "<td>" . ($attr['ghiChu'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Kiểm tra các màu sắc đã được gán cho sản phẩm
    echo "<h3>Các màu sắc đã được gán cho sản phẩm:</h3>";
    $colorProducts = $db->prepare("
        SELECT h.idhanghoa, h.tenhanghoa, tt.tenThuocTinhHH
        FROM thuoctinhhh tt
        JOIN hanghoa h ON tt.idhanghoa = h.idhanghoa
        WHERE tt.idThuocTinh = ?
        ORDER BY h.tenhanghoa
        LIMIT 20
    ");
    $colorProducts->execute([$colorAttributeId]);
    $products = $colorProducts->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID Hàng hóa</th><th>Tên hàng hóa</th><th>Màu sắc</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['idhanghoa']}</td>";
            echo "<td>{$product['tenhanghoa']}</td>";
            echo "<td>{$product['tenThuocTinhHH']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>Chưa có sản phẩm nào được gán màu sắc.</em></p>";
    }
    
    // 4. Lấy danh sách màu sắc unique
    echo "<h3>Danh sách màu sắc đang được sử dụng:</h3>";
    $uniqueColors = $db->prepare("
        SELECT DISTINCT tenThuocTinhHH as color, COUNT(*) as count
        FROM thuoctinhhh
        WHERE idThuocTinh = ?
        GROUP BY tenThuocTinhHH
        ORDER BY count DESC
    ");
    $uniqueColors->execute([$colorAttributeId]);
    $colors = $uniqueColors->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($colors)) {
        echo "<ul>";
        foreach ($colors as $color) {
            echo "<li><strong>{$color['color']}</strong> ({$color['count']} sản phẩm)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><em>Chưa có màu sắc nào được sử dụng.</em></p>";
    }
    
    // 5. Hướng dẫn cập nhật code
    echo "<hr>";
    echo "<h3>📝 Hướng dẫn tiếp theo:</h3>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-left: 4px solid #007bff;'>";
    echo "<p><strong>ID thuộc tính Màu sắc của bạn là: <span style='color: red; font-size: 20px;'>$colorAttributeId</span></strong></p>";
    echo "<p>Bạn cần cập nhật ID này trong file:</p>";
    echo "<ul>";
    echo "<li><code>lequocanh/administrator/elements_LQA/mod/hanghoaCls.php</code> (dòng ~1325)</li>";
    echo "<li>Thay đổi: <code>tt.idThuocTinh = 7</code> thành <code>tt.idThuocTinh = $colorAttributeId</code></li>";
    echo "</ul>";
    echo "<p><strong>Các màu sắc chuẩn để sử dụng:</strong></p>";
    echo "<ul style='columns: 3;'>";
    $standardColors = ['Đỏ', 'Xanh dương', 'Xanh lá', 'Vàng', 'Cam', 'Tím', 'Hồng', 'Đen', 'Trắng', 'Xám', 'Nâu', 'Bạc'];
    foreach ($standardColors as $color) {
        echo "<li>$color</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>✅ Hoàn tất kiểm tra!</h3>";
    echo "<p><a href='fix_color_filter_system.php'>→ Tiếp theo: Sửa hệ thống bộ lọc màu sắc</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>❌ Lỗi:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
