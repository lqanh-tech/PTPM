<?php
/**
 * Script tự động tách các record màu sắc có nhiều màu thành nhiều records riêng
 */

require_once './lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== SỬA LỖI RECORD MÀU SẮC ===\n\n";

// Tìm các record có dấu phẩy (nhiều màu)
$stmt = $db->query("
    SELECT 
        idThuocTinhHH,
        idhanghoa,
        idThuocTinh,
        tenThuocTinhHH,
        ghiChu
    FROM thuoctinhhh
    WHERE idThuocTinh = 26 
    AND tenThuocTinhHH LIKE '%,%'
");

$multiColorRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($multiColorRecords)) {
    echo "✅ Không có record nào cần sửa!\n";
    exit;
}

echo "Tìm thấy " . count($multiColorRecords) . " record cần sửa:\n\n";

$db->beginTransaction();

try {
    foreach ($multiColorRecords as $record) {
        echo "Record ID: {$record['idThuocTinhHH']}\n";
        echo "Sản phẩm ID: {$record['idhanghoa']}\n";
        echo "Màu cũ: {$record['tenThuocTinhHH']}\n";
        
        // Tách màu
        $colors = array_map('trim', explode(',', $record['tenThuocTinhHH']));
        echo "Tách thành: " . implode(' | ', $colors) . "\n";
        
        // Xóa record cũ
        $deleteStmt = $db->prepare("DELETE FROM thuoctinhhh WHERE idThuocTinhHH = ?");
        $deleteStmt->execute([$record['idThuocTinhHH']]);
        echo "✅ Đã xóa record cũ\n";
        
        // Tạo record mới cho mỗi màu
        $insertStmt = $db->prepare("
            INSERT INTO thuoctinhhh (idhanghoa, idThuocTinh, tenThuocTinhHH, ghiChu)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($colors as $color) {
            // Chuẩn hóa tên màu (viết hoa chữ cái đầu)
            $color = ucfirst(strtolower($color));
            
            // Kiểm tra xem màu này đã tồn tại chưa
            $checkStmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM thuoctinhhh 
                WHERE idhanghoa = ? 
                AND idThuocTinh = ? 
                AND tenThuocTinhHH = ?
            ");
            $checkStmt->execute([$record['idhanghoa'], $record['idThuocTinh'], $color]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if (!$exists) {
                $insertStmt->execute([
                    $record['idhanghoa'],
                    $record['idThuocTinh'],
                    $color,
                    $record['ghiChu']
                ]);
                echo "  ✅ Đã tạo record mới: $color\n";
            } else {
                echo "  ⚠️ Màu $color đã tồn tại, bỏ qua\n";
            }
        }
        
        echo "---\n";
    }
    
    $db->commit();
    echo "\n✅ Hoàn thành! Đã sửa tất cả records.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n❌ Lỗi: " . $e->getMessage() . "\n";
}

// Kiểm tra lại
echo "\n=== KIỂM TRA LẠI ===\n\n";
$stmt = $db->query("
    SELECT 
        tenThuocTinhHH as mau_sac,
        COUNT(*) as so_luong
    FROM thuoctinhhh
    WHERE idThuocTinh = 26
    GROUP BY tenThuocTinhHH
    ORDER BY so_luong DESC
");

$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($colors as $c) {
    echo "{$c['mau_sac']}: {$c['so_luong']} sản phẩm\n";
}
