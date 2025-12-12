<?php
/**
 * Script sửa hệ thống bộ lọc màu sắc
 * Tự động cập nhật ID thuộc tính màu sắc và tạo bộ lọc động
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Sửa hệ thống bộ lọc màu sắc</h2>";
    
    // 1. Tìm ID thuộc tính màu sắc
    $checkStmt = $db->prepare("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
    $checkStmt->execute();
    $colorAttr = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colorAttr) {
        echo "<p style='color: red;'>❌ Không tìm thấy thuộc tính màu sắc. Vui lòng chạy setup_color_attribute.php trước.</p>";
        exit;
    }
    
    $colorAttributeId = $colorAttr['idThuocTinh'];
    echo "<p>✓ Tìm thấy thuộc tính màu sắc với ID: <strong>$colorAttributeId</strong></p>";
    
    // 2. Đọc file hanghoaCls.php
    $hanghoaFile = 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';
    $content = file_get_contents($hanghoaFile);
    
    // 3. Tìm và thay thế ID cứng
    $pattern = '/tt\.idThuocTinh\s*=\s*\d+\s*AND.*color/i';
    if (preg_match($pattern, $content, $matches)) {
        echo "<p>✓ Tìm thấy code bộ lọc màu sắc cũ</p>";
        
        // Thay thế ID cứng bằng ID động
        $newContent = preg_replace(
            '/(\/\/\s*Color filter:.*\n.*\n\s*if\s*\(!empty\(\$filters\[\'colors\'\]\)\)\s*\{.*?\n.*?\n.*?\n.*?\n.*?\n.*?\n.*?\n.*?\n\s*\/\/\s*Use hardcoded ID\s*)\d+(\s*for color attribute)/s',
            '$1' . $colorAttributeId . '$2',
            $content
        );
        
        // Backup file cũ
        $backupFile = $hanghoaFile . '.backup_' . date('YmdHis');
        copy($hanghoaFile, $backupFile);
        echo "<p>✓ Đã backup file cũ: $backupFile</p>";
        
        // Ghi file mới
        file_put_contents($hanghoaFile, $newContent);
        echo "<p>✓ Đã cập nhật ID thuộc tính màu sắc trong hanghoaCls.php</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Không tìm thấy pattern cũ, có thể đã được cập nhật</p>";
    }
    
    // 4. Lấy danh sách màu sắc thực tế từ database
    echo "<h3>Danh sách màu sắc có trong hệ thống:</h3>";
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
    
    if (!empty($availableColors)) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Màu sắc</th><th>Số sản phẩm</th><th>Giá trị filter</th></tr>";
        foreach ($availableColors as $color) {
            echo "<tr>";
            echo "<td>{$color['color_display']}</td>";
            echo "<td>{$color['product_count']}</td>";
            echo "<td><code>{$color['color_value']}</code></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>Chưa có màu sắc nào trong hệ thống.</em></p>";
    }
    
    // 5. Tạo mapping màu sắc tiếng Việt - tiếng Anh
    $colorMapping = [
        'đỏ' => 'red',
        'xanh dương' => 'blue',
        'xanh lá' => 'green',
        'vàng' => 'yellow',
        'cam' => 'orange',
        'tím' => 'purple',
        'hồng' => 'pink',
        'đen' => 'black',
        'trắng' => 'white',
        'xám' => 'gray',
        'nâu' => 'brown',
        'bạc' => 'silver'
    ];
    
    echo "<h3>Mapping màu sắc (Tiếng Việt → Tiếng Anh):</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Tiếng Việt</th><th>Tiếng Anh (Filter value)</th><th>Có trong DB?</th></tr>";
    foreach ($colorMapping as $vi => $en) {
        $inDb = false;
        foreach ($availableColors as $color) {
            if (mb_strtolower($color['color_display']) == $vi || mb_strtolower($color['color_display']) == $en) {
                $inDb = true;
                break;
            }
        }
        $status = $inDb ? "✓" : "✗";
        $style = $inDb ? "color: green;" : "color: red;";
        echo "<tr>";
        echo "<td>$vi</td>";
        echo "<td>$en</td>";
        echo "<td style='$style'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Tạo code JavaScript động cho bộ lọc
    echo "<h3>Code JavaScript cho bộ lọc động:</h3>";
    echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
    echo "// Mapping màu sắc Tiếng Việt - Tiếng Anh\n";
    echo "const colorMapping = " . json_encode($colorMapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n\n";
    echo "// Màu sắc có sẵn trong database\n";
    echo "const availableColors = " . json_encode(array_column($availableColors, 'color_value'), JSON_PRETTY_PRINT) . ";\n\n";
    echo "// Hàm chuyển đổi màu từ filter value sang database value\n";
    echo "function getColorValue(filterValue) {\n";
    echo "    // Tìm trong mapping\n";
    echo "    for (const [vi, en] of Object.entries(colorMapping)) {\n";
    echo "        if (en === filterValue) return vi;\n";
    echo "    }\n";
    echo "    return filterValue;\n";
    echo "}\n";
    echo "</textarea>";
    
    echo "<hr>";
    echo "<h3>✅ Hoàn tất!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<p><strong>Các bước tiếp theo:</strong></p>";
    echo "<ol>";
    echo "<li>Kiểm tra file <code>hanghoaCls.php</code> đã được cập nhật ID = $colorAttributeId</li>";
    echo "<li>Thêm màu sắc cho các sản phẩm qua trang quản lý thuộc tính hàng hóa</li>";
    echo "<li>Sử dụng tên màu chuẩn: " . implode(', ', array_keys($colorMapping)) . "</li>";
    echo "<li>Test bộ lọc màu sắc ở trang frontend</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='test_color_filter.php'>→ Test bộ lọc màu sắc</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>❌ Lỗi:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
