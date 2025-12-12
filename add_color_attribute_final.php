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
        // Add color attribute with empty blob for hinhanh
        $insertStmt = $db->prepare("INSERT INTO thuoctinh (tenThuocTinh, ghiChu, hinhanh) VALUES (?, ?, ?)");
        $insertStmt->execute(['Màu sắc', 'Màu sắc của sản phẩm', '']); // Empty string for blob
        $colorId = $db->lastInsertId();
        
        echo "✓ Đã thêm thuộc tính màu sắc (ID: $colorId)\n";
    }
    
    // Show current attributes
    echo "\n=== Danh sách thuộc tính hiện có ===\n";
    $allStmt = $db->prepare("SELECT idThuocTinh, tenThuocTinh, ghiChu FROM thuoctinh ORDER BY idThuocTinh");
    $allStmt->execute();
    $all = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all as $attr) {
        $highlight = (strpos(strtolower($attr['tenThuocTinh']), 'màu') !== false) ? ' ← THUỘC TÍNH MÀU SẮC' : '';
        echo "{$attr['idThuocTinh']}: {$attr['tenThuocTinh']}{$highlight}\n";
    }
    
    echo "\n=== Bước tiếp theo ===\n";
    echo "1. Vào trang quản lý sản phẩm trong admin\n";
    echo "2. Chọn sản phẩm và thêm thuộc tính 'Màu sắc'\n";
    echo "3. Nhập giá trị màu như: Đen, Trắng, Xanh dương, v.v.\n";
    echo "4. Bộ lọc màu sắc sẽ tự động xuất hiện trên trang chủ\n";
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
}
