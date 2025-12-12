<?php
/**
 * Thêm các cột cần thiết cho chức năng đổi/trả hàng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>Thêm cột cho chức năng đổi/trả hàng</h1>";
echo "<hr>";

try {
    // 1. Kiểm tra các cột hiện có
    echo "<h2>1. Kiểm tra cột hiện có:</h2>";
    $columnsSql = "SHOW COLUMNS FROM don_hang";
    $columnsStmt = $conn->query($columnsSql);
    $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Các cột hiện có: " . implode(', ', $existingColumns) . "</p>";
    
    // 2. Thêm các cột cần thiết
    echo "<h2>2. Thêm các cột:</h2>";
    
    $columnsToAdd = [
        'trang_thai_doi_tra' => [
            'definition' => "ENUM('none', 'requested', 'approved', 'rejected') DEFAULT 'none'",
            'comment' => 'Trạng thái đổi/trả hàng'
        ],
        'ly_do_doi_tra' => [
            'definition' => "TEXT DEFAULT NULL",
            'comment' => 'Lý do đổi/trả từ khách hàng'
        ],
        'ngay_yeu_cau_doi_tra' => [
            'definition' => "DATETIME DEFAULT NULL",
            'comment' => 'Ngày khách hàng yêu cầu đổi/trả'
        ],
        'admin_note' => [
            'definition' => "TEXT DEFAULT NULL",
            'comment' => 'Ghi chú từ admin khi xử lý'
        ],
        'ngay_xu_ly_doi_tra' => [
            'definition' => "DATETIME DEFAULT NULL",
            'comment' => 'Ngày admin xử lý yêu cầu'
        ]
    ];
    
    $added = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($columnsToAdd as $columnName => $columnInfo) {
        if (in_array($columnName, $existingColumns)) {
            echo "<p style='color: orange;'>⚠️ Cột <strong>$columnName</strong> đã tồn tại - Bỏ qua</p>";
            $skipped++;
        } else {
            try {
                $alterSql = "ALTER TABLE don_hang ADD COLUMN $columnName {$columnInfo['definition']} COMMENT '{$columnInfo['comment']}'";
                $conn->exec($alterSql);
                echo "<p style='color: green;'>✅ Đã thêm cột: <strong>$columnName</strong> - {$columnInfo['comment']}</p>";
                $added++;
            } catch (PDOException $e) {
                $error = "❌ Lỗi khi thêm cột $columnName: " . $e->getMessage();
                echo "<p style='color: red;'>$error</p>";
                $errors[] = $error;
            }
        }
    }
    
    // 3. Kiểm tra lại
    echo "<h2>3. Kiểm tra lại sau khi thêm:</h2>";
    $columnsStmt = $conn->query($columnsSql);
    $newColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Tên cột</th><th>Trạng thái</th></tr>";
    
    foreach ($columnsToAdd as $columnName => $columnInfo) {
        $exists = in_array($columnName, $newColumns);
        $status = $exists ? "<span style='color: green;'>✅ Có</span>" : "<span style='color: red;'>❌ Không có</span>";
        echo "<tr><td><strong>$columnName</strong></td><td>$status</td></tr>";
    }
    echo "</table>";
    
    // 4. Tổng kết
    echo "<h2>4. Tổng kết:</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
    echo "<p>✅ Đã thêm: <strong>$added</strong> cột</p>";
    echo "<p>⚠️ Đã tồn tại: <strong>$skipped</strong> cột</p>";
    
    if (!empty($errors)) {
        echo "<p style='color: red;'>❌ Lỗi: <strong>" . count($errors) . "</strong> cột</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // 5. Hướng dẫn tiếp theo
    if (empty($errors)) {
        echo "<div style='background: #d4edda; padding: 20px; margin-top: 20px; border-left: 4px solid #28a745;'>";
        echo "<h3>🎉 Hoàn tất!</h3>";
        echo "<p>Các cột đã được thêm thành công. Bây giờ bạn có thể:</p>";
        echo "<ol>";
        echo "<li>Quay lại trang chi tiết đơn hàng</li>";
        echo "<li>Thử gửi yêu cầu đổi/trả hàng</li>";
        echo "<li>Kiểm tra xem có hoạt động không</li>";
        echo "</ol>";
        echo "<a href='lequocanh/administrator/elements_LQA/mgiohang/giohangView.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Đi đến Giỏ hàng</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; margin-top: 20px; border-left: 4px solid #dc3545;'>";
        echo "<h3>❌ Có lỗi xảy ra!</h3>";
        echo "<p>Vui lòng kiểm tra lại quyền của database hoặc liên hệ admin.</p>";
        echo "</div>";
    }
    
    // 6. Test query
    echo "<h2>5. Test query:</h2>";
    echo "<p>Thử query để kiểm tra:</p>";
    
    try {
        $testSql = "SELECT id, ma_don_hang_text, trang_thai, trang_thai_doi_tra, ly_do_doi_tra 
                    FROM don_hang 
                    LIMIT 1";
        $testStmt = $conn->query($testSql);
        $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testResult) {
            echo "<p style='color: green;'>✅ Query thành công! Các cột đã hoạt động.</p>";
            echo "<pre>" . print_r($testResult, true) . "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ Không có dữ liệu để test, nhưng query không lỗi.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Lỗi khi test query: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<h3>❌ Lỗi nghiêm trọng:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #f8f9fa;
    }
    h1 {
        color: #2c3e50;
    }
    h2 {
        color: #34495e;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #dee2e6;
    }
    table {
        width: 100%;
        margin: 20px 0;
    }
    th {
        background: #3498db;
        color: white;
        text-align: left;
    }
    pre {
        background: #f4f4f4;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
    }
</style>
