<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $colorAttrStmt = $db->prepare("SELECT idThuocTinh, tenThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%' OR tenThuocTinh LIKE '%color%' LIMIT 1");
    $colorAttrStmt->execute();
    $colorAttr = $colorAttrStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colorAttr) {
        echo '<p class="text-muted small">Không tìm thấy thuộc tính màu sắc</p>';
        return;
    }
    
    $colorAttributeId = $colorAttr['idThuocTinh'];
    
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
    
    if (count($colors) == 0) {
        echo '<p class="text-muted small">Chưa có sản phẩm nào có màu sắc. Vui lòng thêm màu sắc cho sản phẩm trong trang quản lý.</p>';
        return;
    }
    
    $colorMapping = [
        'đỏ' => ['en' => 'red', 'css' => 'color-red', 'hex' => '#dc3545'],
        'xanh dương' => ['en' => 'blue', 'css' => 'color-blue', 'hex' => '#007bff'],
        'xanh lá' => ['en' => 'green', 'css' => 'color-green', 'hex' => '#28a745'],
        'vàng' => ['en' => 'yellow', 'css' => 'color-yellow', 'hex' => '#ffc107'],
        'cam' => ['en' => 'orange', 'css' => 'color-orange', 'hex' => '#fd7e14'],
        'tím' => ['en' => 'purple', 'css' => 'color-purple', 'hex' => '#6f42c1'],
        'hồng' => ['en' => 'pink', 'css' => 'color-pink', 'hex' => '#e83e8c'],
        'đen' => ['en' => 'black', 'css' => 'color-black', 'hex' => '#212529'],
        'trắng' => ['en' => 'white', 'css' => 'color-white', 'hex' => '#ffffff'],
        'xám' => ['en' => 'gray', 'css' => 'color-gray', 'hex' => '#6c757d'],
        'nâu' => ['en' => 'brown', 'css' => 'color-brown', 'hex' => '#8b4513'],
        'bạc' => ['en' => 'silver', 'css' => 'color-silver', 'hex' => '#c0c0c0']
    ];
    
    foreach ($colors as $color) {
        $colorValue = $color['color_value'];
        $colorDisplay = $color['color_display'];
        $productCount = $color['product_count'];
        
        $mapping = null;
        foreach ($colorMapping as $vi => $data) {
            if ($colorValue == $vi || $colorValue == $data['en']) {
                $mapping = $data;
                break;
            }
        }
        
        if (!$mapping) {
            $mapping = [
                'en' => $colorValue,
                'css' => 'color-' . $colorValue,
                'hex' => '#cccccc'
            ];
        }
        
        ?>
        <label class="color-option" title="<?php echo htmlspecialchars($colorDisplay); ?> (<?php echo $productCount; ?> sản phẩm)">
            <input type="checkbox" value="<?php echo htmlspecialchars($mapping['en']); ?>">
            <div class="color-swatch <?php echo htmlspecialchars($mapping['css']); ?>"></div>
            <i class="fas fa-check checkmark"></i>
        </label>
        <?php
    }
    
} catch (Exception $e) {
    echo '<p class="text-danger small">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>