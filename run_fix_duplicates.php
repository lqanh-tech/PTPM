<?php
// Script chạy từ command line
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "=== XÓA PHƯƠNG THỨC VẬN CHUYỂN TRÙNG LẶP ===\n\n";

try {
    $db->beginTransaction();
    
    // Bước 1: Kiểm tra trùng lặp
    echo "Bước 1: Kiểm tra dữ liệu hiện tại...\n";
    
    $stmt = $db->query("
        SELECT code, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids, GROUP_CONCAT(name ORDER BY id SEPARATOR ' | ') as names
        FROM shipping_methods
        GROUP BY code
        ORDER BY code
    ");
    
    $codeGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDanh sách phương thức:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-15s %-10s %-20s %s\n", "CODE", "SỐ LƯỢNG", "IDs", "TÊN");
    echo str_repeat("-", 80) . "\n";
    
    $hasDuplicates = false;
    foreach ($codeGroups as $group) {
        $isDuplicate = $group['count'] > 1;
        if ($isDuplicate) $hasDuplicates = true;
        
        $status = $isDuplicate ? "[TRÙNG]" : "[OK]";
        printf("%-15s %-10s %-20s %s %s\n", 
            $group['code'], 
            $group['count'], 
            $group['ids'], 
            substr($group['names'], 0, 30),
            $status
        );
    }
    echo str_repeat("-", 80) . "\n\n";
    
    if (!$hasDuplicates) {
        echo "✅ Không có phương thức trùng lặp!\n";
        $db->rollBack();
        exit(0);
    }
    
    // Bước 2: Xác định bản ghi cần xóa
    echo "Bước 2: Xác định bản ghi cần xử lý...\n";
    
    $stmt = $db->query("
        SELECT code, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids
        FROM shipping_methods
        GROUP BY code
        HAVING COUNT(*) > 1
    ");
    
    $duplicateGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deleteIds = [];
    foreach ($duplicateGroups as $group) {
        $ids = explode(',', $group['all_ids']);
        $keepId = $group['keep_id'];
        $toDelete = array_filter($ids, function($id) use ($keepId) {
            return $id != $keepId;
        });
        
        $deleteIds = array_merge($deleteIds, $toDelete);
        
        echo "  - Code '{$group['code']}': Giữ ID {$keepId}, Xóa ID " . implode(', ', $toDelete) . "\n";
    }
    echo "\n";
    
    // Bước 3: Kiểm tra ràng buộc
    echo "Bước 3: Kiểm tra ràng buộc dữ liệu...\n";
    
    $hasReferences = false;
    foreach ($deleteIds as $id) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM shipping_fees WHERE shipping_method_id = ?");
        $stmt->execute([$id]);
        $feesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM don_hang WHERE shipping_method_id = ?");
        $stmt->execute([$id]);
        $ordersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($feesCount > 0 || $ordersCount > 0) {
            $hasReferences = true;
            echo "  - ID {$id}: {$feesCount} phí vận chuyển, {$ordersCount} đơn hàng\n";
        }
    }
    
    if (!$hasReferences) {
        echo "  ✅ Không có dữ liệu liên quan\n";
    }
    echo "\n";
    
    // Bước 4: Chuyển tham chiếu
    if ($hasReferences) {
        echo "Bước 4: Chuyển tham chiếu...\n";
        
        foreach ($duplicateGroups as $group) {
            $ids = explode(',', $group['all_ids']);
            $keepId = $group['keep_id'];
            $toDelete = array_filter($ids, function($id) use ($keepId) {
                return $id != $keepId;
            });
            
            foreach ($toDelete as $deleteId) {
                // Chuyển shipping_fees
                $stmt = $db->prepare("UPDATE shipping_fees SET shipping_method_id = ? WHERE shipping_method_id = ?");
                $stmt->execute([$keepId, $deleteId]);
                $feesUpdated = $stmt->rowCount();
                
                // Chuyển don_hang
                $stmt = $db->prepare("UPDATE don_hang SET shipping_method_id = ? WHERE shipping_method_id = ?");
                $stmt->execute([$keepId, $deleteId]);
                $ordersUpdated = $stmt->rowCount();
                
                echo "  ✅ Chuyển từ ID {$deleteId} -> ID {$keepId}: {$feesUpdated} phí, {$ordersUpdated} đơn hàng\n";
            }
        }
        echo "\n";
    }
    
    // Bước 5: Xóa bản ghi trùng lặp
    echo "Bước 5: Xóa bản ghi trùng lặp...\n";
    
    if (count($deleteIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
        $stmt = $db->prepare("DELETE FROM shipping_methods WHERE id IN ($placeholders)");
        $stmt->execute($deleteIds);
        $deletedCount = $stmt->rowCount();
        
        echo "  ✅ Đã xóa {$deletedCount} bản ghi: " . implode(', ', $deleteIds) . "\n\n";
    }
    
    // Bước 6: Kiểm tra kết quả
    echo "Bước 6: Kết quả sau khi xử lý...\n";
    
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order, id");
    $finalMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDanh sách cuối cùng:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-15s %-30s %-15s\n", "ID", "CODE", "TÊN", "THỜI GIAN");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($finalMethods as $method) {
        printf("%-5s %-15s %-30s %-15s\n", 
            $method['id'],
            $method['code'],
            substr($method['name'], 0, 30),
            $method['delivery_time']
        );
    }
    echo str_repeat("-", 80) . "\n";
    
    // Commit transaction
    $db->commit();
    
    echo "\n🎉 HOÀN THÀNH!\n";
    echo "Tổng số phương thức còn lại: " . count($finalMethods) . "\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n❌ LỖI: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
