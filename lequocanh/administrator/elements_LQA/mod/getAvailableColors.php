<?php
/**
 * API lấy danh sách màu sắc có sẵn từ database
 * Dùng để tạo bộ lọc màu sắc động
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Lấy ID thuộc tính màu sắc
    $colorAttrStmt = $db->prepare("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
    $colorAttrStmt->execute();
    $colorAttr = $colorAttrStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colorAttr) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy thuộc tính màu sắc'
        ]);
        exit;
    }
    
    $colorAttributeId = $colorAttr['idThuocTinh'];
    
    // Lấy danh sách màu sắc có sản phẩm
    $colorsStmt = $db->prepare("
        SELECT 
            LOWER(TRIM(tenThuocTinhHH)) as color_value,
            MIN(tenThuocTinhHH) as color_display,
            COUNT(DISTINCT idhanghoa) as product_count
        FROM thuoctinhhh
        WHERE idThuocTinh = ?
        GROUP BY LOWER(TRIM(tenThuocTinhHH))
        HAVING product_count > 0
        ORDER BY product_count DESC
    ");
    $colorsStmt->execute([$colorAttributeId]);
    $colors = $colorsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mapping màu sắc sang tiếng Anh và CSS class
    $colorMapping = [
        'đỏ' => ['en' => 'red', 'css' => 'color-red'],
        'xanh dương' => ['en' => 'blue', 'css' => 'color-blue'],
        'xanh lá' => ['en' => 'green', 'css' => 'color-green'],
        'vàng' => ['en' => 'yellow', 'css' => 'color-yellow'],
        'cam' => ['en' => 'orange', 'css' => 'color-orange'],
        'tím' => ['en' => 'purple', 'css' => 'color-purple'],
        'hồng' => ['en' => 'pink', 'css' => 'color-pink'],
        'đen' => ['en' => 'black', 'css' => 'color-black'],
        'trắng' => ['en' => 'white', 'css' => 'color-white'],
        'xám' => ['en' => 'gray', 'css' => 'color-gray'],
        'nâu' => ['en' => 'brown', 'css' => 'color-brown'],
        'bạc' => ['en' => 'silver', 'css' => 'color-silver']
    ];
    
    // Xử lý dữ liệu màu sắc
    $result = [];
    foreach ($colors as $color) {
        $colorValue = $color['color_value'];
        $colorDisplay = $color['color_display'];
        
        // Tìm mapping
        $mapping = null;
        foreach ($colorMapping as $vi => $data) {
            if ($colorValue == $vi || $colorValue == $data['en']) {
                $mapping = $data;
                break;
            }
        }
        
        // Nếu không tìm thấy mapping, tạo mặc định
        if (!$mapping) {
            $mapping = [
                'en' => $colorValue,
                'css' => 'color-' . $colorValue
            ];
        }
        
        $result[] = [
            'value' => $colorDisplay, // Giá trị gửi lên server
            'display' => $colorDisplay, // Tên hiển thị
            'en' => $mapping['en'], // Tên tiếng Anh
            'css_class' => $mapping['css'], // CSS class
            'count' => (int)$color['product_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'color_attribute_id' => $colorAttributeId,
        'colors' => $result,
        'total' => count($result)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
