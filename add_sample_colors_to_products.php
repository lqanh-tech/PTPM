<?php
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get color attribute ID
    $colorAttrStmt = $db->prepare("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%'");
    $colorAttrStmt->execute();
    $colorAttr = $colorAttrStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colorAttr) {
        die("✗ Không tìm thấy thuộc tính màu sắc\n");
    }
    
    $colorAttrId = $colorAttr['idThuocTinh'];
    echo "✓ Tìm thấy thuộc tính màu sắc (ID: $colorAttrId)\n\n";
    
    // Get some products
    $productsStmt = $db->prepare("SELECT idhanghoa, tenhanghoa FROM hanghoa ORDER BY idhanghoa LIMIT 20");
    $productsStmt->execute();
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        die("✗ Không có sản phẩm nào trong database\n");
    }
    
    echo "Tìm thấy " . count($products) . " sản phẩm\n\n";
    
    // Array of colors to assign
    $colors = ['Đen', 'Trắng', 'Xanh dương', 'Xám', 'Hồng', 'Đỏ'];
    
    $added = 0;
    foreach ($products as $index => $product) {
        // Check if product already has color
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM thuoctinhhh WHERE idhanghoa = ? AND idThuocTinh = ?");
        $checkStmt->execute([$product['idhanghoa'], $colorAttrId]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists > 0) {
            echo "  Sản phẩm #{$product['idhanghoa']} đã có màu sắc\n";
            continue;
        }
        
        // Assign a color (rotate through the colors array)
        $color = $colors[$index % count($colors)];
        
        $insertStmt = $db->prepare("INSERT INTO thuoctinhhh (idhanghoa, idThuocTinh, tenThuocTinhHH) VALUES (?, ?, ?)");
        $insertStmt->execute([$product['idhanghoa'], $colorAttrId, $color]);
        
        echo "✓ Thêm màu '$color' cho sản phẩm #{$product['idhanghoa']}: {$product['tenhanghoa']}\n";
        $added++;
    }
    
    echo "\n=== Kết quả ===\n";
    echo "✓ Đã thêm màu sắc cho $added sản phẩm\n\n";
    
    // Show color distribution
    $statsStmt = $db->prepare("
        SELECT 
            tenThuocTinhHH as color, 
            COUNT(*) as count 
        FROM thuoctinhhh 
        WHERE idThuocTinh = ? 
        GROUP BY tenThuocTinhHH 
        ORDER BY count DESC
    ");
    $statsStmt->execute([$colorAttrId]);
    $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Phân bố màu sắc ===\n";
    foreach ($stats as $stat) {
        echo "{$stat['color']}: {$stat['count']} sản phẩm\n";
    }
    
    echo "\n✓ Bộ lọc màu sắc đã sẵn sàng!\n";
    echo "Truy cập trang chủ để xem bộ lọc màu hoạt động.\n";
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
}
