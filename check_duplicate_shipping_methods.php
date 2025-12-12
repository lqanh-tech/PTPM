<?php
require_once 'bootstrap.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra phương thức vận chuyển trùng lặp</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .duplicate { background-color: #ffcccc !important; }
        .action { margin: 20px 0; padding: 15px; background: #fff3cd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Kiểm tra phương thức vận chuyển</h1>";

try {
    // Lấy tất cả phương thức vận chuyển
    $stmt = $db->query("SELECT * FROM shipping_methods ORDER BY sort_order, id");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📋 Danh sách phương thức vận chuyển hiện tại:</h2>";
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Code</th>
            <th>Tên</th>
            <th>Mô tả</th>
            <th>Thời gian giao</th>
            <th>Hệ số giá</th>
            <th>Thứ tự</th>
            <th>Trạng thái</th>
          </tr>";
    
    $codes = [];
    $duplicates = [];
    
    foreach ($methods as $method) {
        $isDuplicate = false;
        if (isset($codes[$method['code']])) {
            $duplicates[] = $method['id'];
            $duplicates[] = $codes[$method['code']];
            $isDuplicate = true;
        }
        $codes[$method['code']] = $method['id'];
        
        $rowClass = $isDuplicate ? "class='duplicate'" : "";
        
        echo "<tr $rowClass>";
        echo "<td>{$method['id']}</td>";
        echo "<td>{$method['code']}</td>";
        echo "<td>{$method['name']}</td>";
        echo "<td>{$method['description']}</td>";
        echo "<td>{$method['delivery_time']}</td>";
        echo "<td>{$method['price_multiplier']}</td>";
        echo "<td>{$method['sort_order']}</td>";
        echo "<td>" . ($method['is_active'] ? '✅ Hoạt động' : '❌ Không hoạt động') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Kiểm tra trùng lặp
    $duplicates = array_unique($duplicates);
    
    if (count($duplicates) > 0) {
        echo "<div class='action'>";
        echo "<h2>⚠️ Phát hiện phương thức trùng lặp!</h2>";
        echo "<p>Các ID bị trùng: " . implode(', ', $duplicates) . "</p>";
        echo "<p><strong>Hành động đề xuất:</strong></p>";
        echo "<ul>";
        echo "<li>Xóa các bản ghi trùng lặp (giữ lại bản ghi có ID nhỏ nhất)</li>";
        echo "<li>Hoặc cập nhật code để không bị trùng</li>";
        echo "</ul>";
        echo "</div>";
        
        // Tạo script xóa trùng lặp
        echo "<h2>🔧 Script xóa trùng lặp:</h2>";
        echo "<pre>";
        echo "-- Xóa các bản ghi trùng lặp (giữ lại bản ghi có ID nhỏ nhất)\n";
        
        $stmt = $db->query("
            SELECT code, MIN(id) as keep_id, GROUP_CONCAT(id) as all_ids
            FROM shipping_methods
            GROUP BY code
            HAVING COUNT(*) > 1
        ");
        
        $duplicateGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($duplicateGroups as $group) {
            $ids = explode(',', $group['all_ids']);
            $keepId = $group['keep_id'];
            $deleteIds = array_filter($ids, function($id) use ($keepId) {
                return $id != $keepId;
            });
            
            if (count($deleteIds) > 0) {
                echo "DELETE FROM shipping_methods WHERE id IN (" . implode(',', $deleteIds) . "); -- Code: {$group['code']}\n";
            }
        }
        
        echo "</pre>";
        
    } else {
        echo "<div class='action' style='background: #d4edda;'>";
        echo "<h2>✅ Không có phương thức trùng lặp</h2>";
        echo "<p>Tất cả phương thức vận chuyển đều có code duy nhất.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; background: #ffebee;'>";
    echo "<h2>❌ Lỗi:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
