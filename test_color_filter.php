<?php
/**
 * Test bộ lọc màu sắc
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

try {
    $db = Database::getInstance()->getConnection();
    $hanghoa = new hanghoa();
    
    echo "<h2>Test bộ lọc màu sắc</h2>";
    
    // 1. Lấy ID thuộc tính màu sắc
    $checkStmt = $db->prepare("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
    $checkStmt->execute();
    $colorAttr = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $colorAttributeId = $colorAttr['idThuocTinh'] ?? null;
    
    echo "<p>ID thuộc tính màu sắc: <strong>$colorAttributeId</strong></p>";
    
    // 2. Lấy danh sách màu sắc có sẵn
    $colorsStmt = $db->prepare("
        SELECT DISTINCT LOWER(TRIM(tenThuocTinhHH)) as color_value, 
               tenThuocTinhHH as color_display,
               COUNT(*) as product_count
        FROM thuoctinhhh
        WHERE idThuocTinh = ?
        GROUP BY LOWER(TRIM(tenThuocTinhHH))
        ORDER BY product_count DESC
    ");
    $colorsStmt->execute([$colorAttributeId]);
    $availableColors = $colorsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Màu sắc có sẵn:</h3>";
    echo "<ul>";
    foreach ($availableColors as $color) {
        echo "<li><strong>{$color['color_display']}</strong> ({$color['product_count']} sản phẩm)</li>";
    }
    echo "</ul>";
    
    // 3. Test filter với từng màu
    echo "<h3>Test filter với từng màu:</h3>";
    
    foreach ($availableColors as $color) {
        $colorValue = $color['color_value'];
        $colorDisplay = $color['color_display'];
        
        echo "<h4>Test màu: $colorDisplay</h4>";
        
        // Test với giá trị tiếng Việt
        $filters = [
            'colors' => [$colorDisplay],
            'min_price' => 0,
            'max_price' => 100000000
        ];
        
        $products = $hanghoa->filterProducts($filters);
        
        echo "<p>Số sản phẩm tìm thấy: <strong>" . count($products) . "</strong></p>";
        
        if (!empty($products)) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>ID</th><th>Tên sản phẩm</th><th>Giá</th></tr>";
            $count = 0;
            foreach ($products as $product) {
                if ($count >= 5) break; // Chỉ hiển thị 5 sản phẩm đầu
                echo "<tr>";
                echo "<td>{$product->idhanghoa}</td>";
                echo "<td>{$product->tenhanghoa}</td>";
                echo "<td>" . number_format($product->gia_hien_thi) . " VNĐ</td>";
                echo "</tr>";
                $count++;
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ Không tìm thấy sản phẩm nào</p>";
        }
    }
    
    // 4. Test filter với nhiều màu
    if (count($availableColors) >= 2) {
        echo "<h3>Test filter với nhiều màu:</h3>";
        $testColors = array_slice(array_column($availableColors, 'color_display'), 0, 2);
        
        echo "<p>Test với màu: <strong>" . implode(', ', $testColors) . "</strong></p>";
        
        $filters = [
            'colors' => $testColors,
            'min_price' => 0,
            'max_price' => 100000000
        ];
        
        $products = $hanghoa->filterProducts($filters);
        echo "<p>Số sản phẩm tìm thấy: <strong>" . count($products) . "</strong></p>";
        
        if (!empty($products)) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Tên sản phẩm</th><th>Giá</th></tr>";
            $count = 0;
            foreach ($products as $product) {
                if ($count >= 10) break;
                echo "<tr>";
                echo "<td>{$product->idhanghoa}</td>";
                echo "<td>{$product->tenhanghoa}</td>";
                echo "<td>" . number_format($product->gia_hien_thi) . " VNĐ</td>";
                echo "</tr>";
                $count++;
            }
            echo "</table>";
        }
    }
    
    // 5. Kiểm tra query SQL
    echo "<h3>Debug SQL Query:</h3>";
    echo "<p>Để xem query SQL chi tiết, kiểm tra error_log của PHP</p>";
    
    echo "<hr>";
    echo "<h3>✅ Hoàn tất test!</h3>";
    echo "<p><a href='lequocanh/administrator/index.php?req=thuoctinhhhview'>→ Quản lý thuộc tính hàng hóa</a></p>";
    echo "<p><a href='lequocanh/index.php'>→ Xem trang frontend</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>❌ Lỗi:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
