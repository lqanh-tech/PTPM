<?php
/**
 * Script cài đặt hệ thống đánh giá sản phẩm
 * Chạy file này để tạo bảng và cấu trúc database
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cài đặt hệ thống đánh giá sản phẩm</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        body { background: #f5f5f5; padding: 40px 0; }
        .setup-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .step { padding: 20px; margin: 15px 0; border-left: 4px solid #007bff; background: #f8f9fa; border-radius: 8px; }
        .step.success { border-color: #28a745; background: #d4edda; }
        .step.error { border-color: #dc3545; background: #f8d7da; }
        .step h5 { margin-bottom: 10px; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px; overflow-x: auto; margin: 10px 0; }
    </style>
</head>
<body>
<div class='setup-container'>
    <h2 class='mb-4'><i class='fas fa-star text-warning'></i> Cài đặt hệ thống đánh giá sản phẩm</h2>
    <p class='text-muted mb-4'>Script này sẽ tạo các bảng và cấu trúc cần thiết cho hệ thống đánh giá</p>
";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Đọc file SQL
    $sqlFile = 'setup_product_reviews_system.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^(--|\/\*|DELIMITER)/', $stmt);
        }
    );
    
    echo "<div class='step'><h5><i class='fas fa-database'></i> Bắt đầu cài đặt...</h5></div>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        try {
            // Bỏ qua comments và DELIMITER
            if (preg_match('/^(--|\/\*|DELIMITER)/i', trim($statement))) {
                continue;
            }
            
            $conn->exec($statement . ';');
            $successCount++;
            
            // Lấy tên bảng/view từ statement
            if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='step success'>
                    <h5><i class='fas fa-check-circle'></i> Tạo bảng: {$matches[1]}</h5>
                    <p class='mb-0 text-muted'>Thành công</p>
                </div>";
            } elseif (preg_match('/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='step success'>
                    <h5><i class='fas fa-check-circle'></i> Tạo view: {$matches[1]}</h5>
                    <p class='mb-0 text-muted'>Thành công</p>
                </div>";
            } elseif (preg_match('/ALTER\s+TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='step success'>
                    <h5><i class='fas fa-check-circle'></i> Cập nhật bảng: {$matches[1]}</h5>
                    <p class='mb-0 text-muted'>Thành công</p>
                </div>";
            } elseif (preg_match('/CREATE\s+(?:PROCEDURE|TRIGGER)/i', $statement)) {
                preg_match('/(?:PROCEDURE|TRIGGER)\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                $name = $matches[1] ?? 'unknown';
                echo "<div class='step success'>
                    <h5><i class='fas fa-check-circle'></i> Tạo procedure/trigger: {$name}</h5>
                    <p class='mb-0 text-muted'>Thành công</p>
                </div>";
            }
            
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // Bỏ qua lỗi "already exists"
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate') !== false) {
                continue;
            }
            
            echo "<div class='step error'>
                <h5><i class='fas fa-exclamation-triangle'></i> Lỗi</h5>
                <p class='mb-0'>{$errorMsg}</p>
            </div>";
        }
    }
    
    echo "<div class='step success'>
        <h5><i class='fas fa-check-circle'></i> Hoàn thành!</h5>
        <p class='mb-2'>Đã thực thi thành công: <strong>{$successCount}</strong> câu lệnh</p>
        " . ($errorCount > 0 ? "<p class='mb-0 text-warning'>Có {$errorCount} lỗi (có thể bỏ qua nếu là lỗi 'already exists')</p>" : "") . "
    </div>";
    
    // Kiểm tra các bảng đã tạo
    echo "<div class='step'>
        <h5><i class='fas fa-list'></i> Kiểm tra cấu trúc</h5>";
    
    $tables = ['product_reviews', 'review_images', 'review_helpful'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p class='mb-1'><i class='fas fa-check text-success'></i> Bảng <code>$table</code>: <strong>$count</strong> bản ghi</p>";
        } else {
            echo "<p class='mb-1'><i class='fas fa-times text-danger'></i> Bảng <code>$table</code>: Không tồn tại</p>";
        }
    }
    
    // Kiểm tra view
    $stmt = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_" . $conn->query("SELECT DATABASE()")->fetchColumn() . " = 'v_product_review_stats'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='mb-1'><i class='fas fa-check text-success'></i> View <code>v_product_review_stats</code>: Đã tạo</p>";
    }
    
    echo "</div>";
    
    // Hướng dẫn sử dụng
    echo "<div class='step'>
        <h5><i class='fas fa-book'></i> Hướng dẫn sử dụng</h5>
        <ol>
            <li><strong>Tích hợp vào trang sản phẩm:</strong>
                <div class='code-block'>&lt;?php include 'lequocanh/components/product_review_display.php'; ?&gt;</div>
            </li>
            <li><strong>Widget đánh giá đã được tích hợp:</strong> Tự động hiển thị trong trang order_success.php khi thanh toán thành công</li>
            <li><strong>API endpoints:</strong>
                <ul>
                    <li><code>GET /api/product_reviews.php?action=list&product_id=X</code> - Lấy danh sách đánh giá</li>
                    <li><code>POST /api/product_reviews.php?action=submit</code> - Gửi đánh giá mới</li>
                    <li><code>GET /api/product_reviews.php?action=check&order_id=X</code> - Kiểm tra đã đánh giá</li>
                </ul>
            </li>
        </ol>
    </div>";
    
    echo "<div class='alert alert-success mt-4'>
        <h5><i class='fas fa-check-circle'></i> Cài đặt thành công!</h5>
        <p class='mb-0'>Hệ thống đánh giá sản phẩm đã sẵn sàng sử dụng.</p>
    </div>";
    
    echo "<div class='mt-4'>
        <a href='lequocanh/index.php' class='btn btn-primary'><i class='fas fa-home'></i> Về trang chủ</a>
        <a href='lequocanh/administrator/elements_LQA/mgiohang/order_success.php?order_id=1' class='btn btn-success'><i class='fas fa-eye'></i> Xem demo</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>
        <h5><i class='fas fa-exclamation-triangle'></i> Lỗi nghiêm trọng</h5>
        <p class='mb-0'>{$e->getMessage()}</p>
    </div>";
}

echo "</div>
</body>
</html>";
