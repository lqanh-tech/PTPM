<?php
/**
 * Migration Script: Thêm cột email vào bảng user
 * Ngày: 2025-11-29
 * Mục đích: Hỗ trợ gửi email thông báo khi thanh toán thành công
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migration - Thêm Email Column</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='mb-4'>🔄 Migration: Thêm cột Email vào bảng User</h1>
";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='alert alert-info'><strong>Bước 1:</strong> Kiểm tra cấu trúc bảng user hiện tại...</div>";
    
    // Kiểm tra cấu trúc bảng hiện tại
    $stmt = $db->query('DESCRIBE user');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Các cột hiện tại trong bảng user:\n";
    foreach($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "</pre>";
    
    // Kiểm tra xem đã có cột email chưa
    $hasEmail = false;
    foreach($columns as $col) {
        if ($col['Field'] === 'email') {
            $hasEmail = true;
            break;
        }
    }
    
    if ($hasEmail) {
        echo "<div class='alert alert-warning'><strong>⚠️ Thông báo:</strong> Cột 'email' đã tồn tại trong bảng user. Không cần migration.</div>";
    } else {
        echo "<div class='alert alert-info'><strong>Bước 2:</strong> Thêm cột email vào bảng user...</div>";
        
        // Thêm cột email
        $alterSql = "ALTER TABLE user ADD COLUMN email VARCHAR(255) NULL AFTER dienthoai";
        $db->exec($alterSql);
        
        echo "<div class='alert alert-success'><strong>✅ Thành công:</strong> Đã thêm cột 'email' vào bảng user</div>";
        
        echo "<div class='alert alert-info'><strong>Bước 3:</strong> Thêm index cho cột email...</div>";
        
        // Thêm index cho email
        try {
            $indexSql = "ALTER TABLE user ADD INDEX idx_email (email)";
            $db->exec($indexSql);
            echo "<div class='alert alert-success'><strong>✅ Thành công:</strong> Đã thêm index cho cột email</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='alert alert-warning'><strong>⚠️ Thông báo:</strong> Index 'idx_email' đã tồn tại</div>";
            } else {
                throw $e;
            }
        }
    }
    
    // Hiển thị cấu trúc bảng sau khi migration
    echo "<div class='alert alert-info'><strong>Bước 4:</strong> Kiểm tra cấu trúc bảng sau migration...</div>";
    
    $stmt = $db->query('DESCRIBE user');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Cấu trúc bảng user sau migration:\n\n";
    foreach($columns as $col) {
        echo sprintf(
            "%-20s %-20s %-10s %-10s %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Key'],
            $col['Default'] ?? 'NULL'
        );
    }
    echo "</pre>";
    
    // Kiểm tra indexes
    echo "<div class='alert alert-info'><strong>Bước 5:</strong> Kiểm tra indexes...</div>";
    
    $indexStmt = $db->query("SHOW INDEX FROM user");
    $indexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Indexes trong bảng user:\n";
    foreach($indexes as $idx) {
        echo "- " . $idx['Key_name'] . " (" . $idx['Column_name'] . ")\n";
    }
    echo "</pre>";
    
    echo "<div class='alert alert-success mt-4'>";
    echo "<h4>🎉 Migration hoàn tất!</h4>";
    echo "<p>Bảng user đã được cập nhật thành công với cột email.</p>";
    echo "<ul>";
    echo "<li>Cột email: VARCHAR(255), NULL</li>";
    echo "<li>Vị trí: Sau cột dienthoai</li>";
    echo "<li>Index: idx_email đã được tạo</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h5>📝 Các bước tiếp theo:</h5>";
    echo "<ol>";
    echo "<li>Cập nhật form đăng ký để thêm trường email</li>";
    echo "<li>Cập nhật class userCls.php để xử lý email</li>";
    echo "<li>Cập nhật form cập nhật thông tin người dùng</li>";
    echo "<li>Kiểm tra chức năng gửi email khi thanh toán</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi Database:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "
    <div class='mt-4'>
        <a href='lequocanh/administrator/signUp.php' class='btn btn-primary'>Đến trang đăng ký</a>
        <a href='lequocanh/administrator/index.php' class='btn btn-secondary'>Về trang quản trị</a>
    </div>
</div>
</body>
</html>";
