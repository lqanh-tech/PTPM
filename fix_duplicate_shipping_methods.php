<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Xóa phương thức vận chuyển trùng lặp</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .duplicate { background-color: #ffcccc !important; font-weight: bold; }
        .keep { background-color: #ccffcc !important; }
        .success { padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724; margin: 20px 0; }
        .warning { padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404; margin: 20px 0; }
        .error { padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; margin: 20px 0; }
        .action-btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .action-btn:hover { background: #45a049; }
        .delete-btn { background: #f44336; }
        .delete-btn:hover { background: #da190b; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Xóa phương thức vận chuyển trùng lặp</h1>";

try {
    $db->beginTransaction();
    
    // Bước 1: Kiểm tra trùng lặp
    echo "<h2>📋 Bước 1: Kiểm tra dữ liệu hiện tại</h2>";
    
    $stmt = $db->query("
        SELECT code, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids, GROUP_CONCAT(name ORDER BY id SEPARATOR ' | ') as names
        FROM shipping_methods
        GROUP BY code
        ORDER BY code
    ");
    
    $codeGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Code</th><th>Số lượng</th><th>IDs</th><th>Tên</th><th>Trạng thái</th></tr>";
    
    $hasDuplicates = false;
    foreach ($codeGroups as $group) {
        $isDuplicate = $group['count'] > 1;
        if ($isDuplicate) $hasDuplicates = true;
        
        $rowClass = $isDuplicate ? "class='duplicate'" : "";
        echo "<tr $rowClass>";
        echo "<td><strong>{$group['code']}</strong></td>";
        echo "<td>{$group['count']}</td>";
        echo "<td>{$group['ids']}</td>";
        echo "<td>{$group['names']}</td>";
        echo "<td>" . ($isDuplicate ? "⚠️ TRÙNG LẶP" : "✅ OK") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$hasDuplicates) {
        echo "<div class='success'>";
        echo "<h3>✅ Không có phương thức trùng lặp</h3>";
        echo "<p>Tất cả phương thức vận chuyển đều có code duy nhất.</p>";
        echo "</div>";
        $db->rollBack();
        echo "</div></body></html>";
        exit;
    }
    
    // Bước 2: Xác định bản ghi cần giữ và xóa
    echo "<h2>🎯 Bước 2: Xác định bản ghi cần xử lý</h2>";
    
    $stmt = $db->query("
        SELECT code, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids
        FROM shipping_methods
        GROUP BY code
        HAVING COUNT(*) > 1
    ");
    
    $duplicateGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Code</th><th>Giữ lại ID</th><th>Xóa IDs</th><th>Hành động</th></tr>";
    
    $deleteIds = [];
    foreach ($duplicateGroups as $group) {
        $ids = explode(',', $group['all_ids']);
        $keepId = $group['keep_id'];
        $toDelete = array_filter($ids, function($id) use ($keepId) {
            return $id != $keepId;
        });
        
        $deleteIds = array_merge($deleteIds, $toDelete);
        
        echo "<tr>";
        echo "<td><strong>{$group['code']}</strong></td>";
        echo "<td class='keep'>{$keepId}</td>";
        echo "<td class='duplicate'>" . implode(', ', $toDelete) . "</td>";
        echo "<td>Xóa " . count($toDelete) . " bản ghi</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Bước 3: Kiểm tra ràng buộc khóa ngoại
    echo "<h2>🔗 Bước 3: Kiểm tra ràng buộc dữ liệu</h2>";
    
    $hasReferences = false;
    $referenceDetails = [];
    
    foreach ($deleteIds as $id) {
        // Kiểm tra shipping_fees
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM shipping_fees WHERE shipping_method_id = ?");
        $stmt->execute([$id]);
        $feesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Kiểm tra don_hang
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM don_hang WHERE shipping_method_id = ?");
        $stmt->execute([$id]);
        $ordersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($feesCount > 0 || $ordersCount > 0) {
            $hasReferences = true;
            $referenceDetails[$id] = [
                'fees' => $feesCount,
                'orders' => $ordersCount
            ];
        }
    }
    
    if ($hasReferences) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Phát hiện dữ liệu liên quan</h3>";
        echo "<table>";
        echo "<tr><th>ID cần xóa</th><th>Phí vận chuyển</th><th>Đơn hàng</th><th>Hành động</th></tr>";
        
        foreach ($referenceDetails as $id => $refs) {
            echo "<tr>";
            echo "<td>{$id}</td>";
            echo "<td>{$refs['fees']} bản ghi</td>";
            echo "<td>{$refs['orders']} đơn hàng</td>";
            echo "<td>Cần chuyển sang ID khác</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Giải pháp:</strong> Chuyển các tham chiếu sang bản ghi được giữ lại trước khi xóa.</p>";
        echo "</div>";
        
        // Chuyển tham chiếu
        echo "<h2>🔄 Bước 4: Chuyển tham chiếu</h2>";
        
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
                
                echo "<div class='success'>";
                echo "✅ Đã chuyển tham chiếu từ ID {$deleteId} sang ID {$keepId}:<br>";
                echo "- Phí vận chuyển: {$feesUpdated} bản ghi<br>";
                echo "- Đơn hàng: {$ordersUpdated} bản ghi";
                echo "</div>";
            }
        }
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Không có dữ liệu liên quan</h3>";
        echo "<p>Có thể xóa trực tiếp các bản ghi trùng lặp.</p>";
        echo "</div>";
    }
    
    // Bước 5: Xóa bản ghi trùng lặp
    echo "<h2>🗑️ Bước 5: Xóa bản ghi trùng lặp</h2>";
    
    if (count($deleteIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
        $stmt = $db->prepare("DELETE FROM shipping_methods WHERE id IN ($placeholders)");
        $stmt->execute($deleteIds);
        $deletedCount = $stmt->rowCount();
        
        echo "<div class='success'>";
        echo "<h3>✅ Đã xóa {$deletedCount} bản ghi trùng lặp</h3>";
        echo "<p>IDs đã xóa: " . implode(', ', $deleteIds) . "</p>";
        echo "</div>";
    }
    
    // Bước 6: Kiểm tra kết quả
    echo "<h2>✅ Bước 6: Kết quả sau khi xử lý</h2>";
    
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order, id");
    $finalMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Code</th><th>Tên</th><th>Mô tả</th><th>Thời gian giao</th><th>Trạng thái</th></tr>";
    
    foreach ($finalMethods as $method) {
        echo "<tr>";
        echo "<td>{$method['id']}</td>";
        echo "<td><strong>{$method['code']}</strong></td>";
        echo "<td>{$method['name']}</td>";
        echo "<td>{$method['description']}</td>";
        echo "<td>{$method['delivery_time']}</td>";
        echo "<td>" . ($method['is_active'] ? '✅ Hoạt động' : '❌ Không hoạt động') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Commit transaction
    $db->commit();
    
    echo "<div class='success'>";
    echo "<h3>🎉 Hoàn thành!</h3>";
    echo "<p>Đã xóa thành công các phương thức vận chuyển trùng lặp.</p>";
    echo "<p>Tổng số bản ghi còn lại: " . count($finalMethods) . "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div class='error'>";
    echo "<h2>❌ Lỗi:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
