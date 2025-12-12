<?php
require_once __DIR__ . '/lequocanh/administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // First, check table structure
    $columnsStmt = $db->query("SHOW COLUMNS FROM thuoctinh");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Cấu trúc bảng thuoctinh ===\n";
    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} {$col['Null']} {$col['Default']}\n";
    }
    echo "\n";
    
    // Check if color attribute exists
    $checkStmt = $db->prepare("SELECT idThuocTinh FROM thuoctinh WHERE tenThuocTinh LIKE '%màu%'");
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "✓ Thuộc tính màu sắc đã tồn tại (ID: {$existing['idThuocTinh']})\n";
    } else {
        // Try to add color attribute with NULL for hinhanh
        try {
            $insertStmt = $db->prepare("INSERT INTO thuoctinh (tenThuocTinh, ghiChu, hinhanh) VALUES (?, ?, NULL)");
            $insertStmt->execute(['Màu sắc', 'Màu sắc của sản phẩm']);
            $colorId = $db->lastInsertId();
            
            echo "✓ Đã thêm thuộc tính màu sắc (ID: $colorId)\n";
        } catch (Exception $e) {
            echo "Thử phương án 2...\n";
            // Try without hinhanh
            $insertStmt = $db->prepare("INSERT INTO thuoctinh (tenThuocTinh, ghiChu) VALUES (?, ?)");
            $insertStmt->execute(['Màu sắc', 'Màu sắc của sản phẩm']);
            $colorId = $db->lastInsertId();
            
            echo "✓ Đã thêm thuộc tính màu sắc (ID: $colorId)\n";
        }
    }
    
    // Show current attributes
    echo "\n=== Danh sách thuộc tính hiện có ===\n";
    $allStmt = $db->prepare("SELECT * FROM thuoctinh ORDER BY idThuocTinh");
    $allStmt->execute();
    $all = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all as $attr) {
        echo "{$attr['idThuocTinh']}: {$attr['tenThuocTinh']}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
