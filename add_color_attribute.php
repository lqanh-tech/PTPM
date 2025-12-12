<?php
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if color attribute exists
    $checkStmt = $db->prepare("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%'");
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "✓ Thuộc tính màu sắc đã tồn tại (ID: {$existing['idThuocTinh']})\n";
    } else {
        // Add color attribute
        $insertStmt = $db->prepare("INSERT INTO thuoctinh (tenThuocTinh, ghiChu, hinhanh) VALUES (?, ?, ?)");
        $insertStmt->execute(['Màu sắc', 'Màu sắc của sản phẩm', 0]);
        $colorId = $db->lastInsertId();
        
        echo "✓ Đã thêm thuộc tính màu sắc (ID: $colorId)\n";
        
        // Add some common colors
        $colors = [
            'Đen', 'Trắng', 'Xám', 'Bạc', 'Vàng', 'Cam', 'Đỏ', 
            'Hồng', 'Tím', 'Xanh dương', 'Xanh lá', 'Nâu'
        ];
        
        echo "\n✓ Sẵn sàng để thêm màu sắc cho sản phẩm:\n";
        foreach ($colors as $color) {
            echo "  - $color\n";
        }
    }
    
    // Show current attributes
    echo "\n=== Danh sách thuộc tính hiện có ===\n";
    $allStmt = $db->prepare("SELECT idThuocTinh, tenThuocTinh FROM thuoctinh ORDER BY idThuocTinh");
    $allStmt->execute();
    $all = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all as $attr) {
        echo "{$attr['idThuocTinh']}: {$attr['tenThuocTinh']}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
}