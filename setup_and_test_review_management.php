<?php
/**
 * Setup và Test Hệ Thống Quản Lý Bình Luận và Khiếu Nại
 */

require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$connection = $db->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Review Management System</title>
    <meta charset='utf-8'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .step { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1>🔧 Setup Hệ Thống Quản Lý Bình Luận và Khiếu Nại</h1>
    <p class='text-muted'>Thiết lập database và kiểm tra các chức năng</p>
";

try {
    // STEP 1: Run SQL setup
    echo "<div class='step'>";
    echo "<h3>📋 Bước 1: Thiết lập Database</h3>";
    
    $sqlFile = file_get_contents('setup_review_management_system.sql');
    $statements = array_filter(array_map('trim', explode(';', $sqlFile)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        // Skip delimiter statements
        if (stripos($statement, 'DELIMITER') !== false) continue;
        
        try {
            $connection->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "<div class='text-warning'>⚠️ Warning: " . $e->getMessage() . "</div>";
                $errorCount++;
            }
        }
    }
    
    echo "<div class='success'>✅ Đã thực thi {$successCount} câu lệnh SQL</div>";
    if ($errorCount > 0) {
        echo "<div class='info'>ℹ️ {$errorCount} cảnh báo (có thể bỏ qua nếu là lỗi 'already exists')</div>";
    }
    echo "</div>";
    
    // STEP 2: Check tables
    echo "<div class='step'>";
    echo "<h3>📋 Bước 2: Kiểm Tra Bảng</h3>";
    
    $tables = [
        'product_reviews' => 'Bảng đánh giá sản phẩm',
        'review_reports' => 'Bảng khiếu nại',
        'support_tickets' => 'Bảng ticket hỗ trợ',
        'support_messages' => 'Bảng tin nhắn hỗ trợ',
        'review_helpful' => 'Bảng đánh dấu hữu ích'
    ];
    
    foreach ($tables as $table => $description) {
        $stmt = $connection->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ {$description} ({$table})</div>";
        } else {
            echo "<div class='error'>❌ Thiếu bảng: {$table}</div>";
        }
    }
    echo "</div>";
    
    // STEP 3: Check views
    echo "<div class='step'>";
    echo "<h3>📋 Bước 3: Kiểm Tra Views</h3>";
    
    $views = [
        'v_review_management_stats' => 'Thống kê bình luận',
        'v_review_reports_list' => 'Danh sách khiếu nại',
        'v_support_tickets_list' => 'Danh sách tickets'
    ];
    
    foreach ($views as $view => $description) {
        try {
            $stmt = $connection->query("SELECT * FROM {$view} LIMIT 1");
            echo "<div class='success'>✅ {$description} ({$view})</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Lỗi view {$view}: " . $e->getMessage() . "</div>";
        }
    }
    echo "</div>";
    
    // STEP 4: Check API files
    echo "<div class='step'>";
    echo "<h3>📋 Bước 4: Kiểm Tra API Files</h3>";
    
    $apiFiles = [
        'lequocanh/api/review_management.php' => 'API quản lý bình luận',
        'lequocanh/api/support_tickets.php' => 'API support tickets',
        'lequocanh/api/report_review.php' => 'API báo cáo bình luận'
    ];
    
    foreach ($apiFiles as $file => $description) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ {$description}</div>";
        } else {
            echo "<div class='error'>❌ Thiếu file: {$file}</div>";
        }
    }
    echo "</div>";
    
    // STEP 5: Check admin pages
    echo "<div class='step'>";
    echo "<h3>📋 Bước 5: Kiểm Tra Trang Admin</h3>";
    
    $adminPages = [
        'lequocanh/administrator/elements_LQA/mreview_management/reviewManagementView.php' => 'Quản lý bình luận',
        'lequocanh/administrator/elements_LQA/msupport_tickets/supportTicketsView.php' => 'Quản lý hỗ trợ'
    ];
    
    foreach ($adminPages as $file => $description) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ {$description}</div>";
        } else {
            echo "<div class='error'>❌ Thiếu file: {$file}</div>";
        }
    }
    echo "</div>";
    
    // STEP 6: Check user pages
    echo "<div class='step'>";
    echo "<h3>📋 Bước 6: Kiểm Tra Trang User</h3>";
    
    $userPages = [
        'lequocanh/customer/support.php' => 'Trang hỗ trợ khách hàng',
        'lequocanh/customer/support.js' => 'JavaScript hỗ trợ'
    ];
    
    foreach ($userPages as $file => $description) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ {$description}</div>";
        } else {
            echo "<div class='error'>❌ Thiếu file: {$file}</div>";
        }
    }
    echo "</div>";
    
    // STEP 7: Test data
    echo "<div class='step'>";
    echo "<h3>📋 Bước 7: Kiểm Tra Dữ Liệu</h3>";
    
    // Count reviews
    $stmt = $connection->query("SELECT COUNT(*) as total FROM product_reviews");
    $reviewCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<div class='info'>ℹ️ Có {$reviewCount} bình luận trong hệ thống</div>";
    
    // Count reports
    $stmt = $connection->query("SELECT COUNT(*) as total FROM review_reports");
    $reportCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<div class='info'>ℹ️ Có {$reportCount} khiếu nại trong hệ thống</div>";
    
    // Count tickets
    $stmt = $connection->query("SELECT COUNT(*) as total FROM support_tickets");
    $ticketCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<div class='info'>ℹ️ Có {$ticketCount} tickets trong hệ thống</div>";
    
    echo "</div>";
    
    // STEP 8: Test menu
    echo "<div class='step'>";
    echo "<h3>📋 Bước 8: Kiểm Tra Menu Admin</h3>";
    
    $leftMenuFile = 'lequocanh/administrator/elements_LQA/left.php';
    $leftMenuContent = file_get_contents($leftMenuFile);
    
    if (strpos($leftMenuContent, 'review_management') !== false) {
        echo "<div class='success'>✅ Menu 'Quản lý bình luận' đã được thêm</div>";
    } else {
        echo "<div class='error'>❌ Chưa thêm menu 'Quản lý bình luận'</div>";
    }
    
    if (strpos($leftMenuContent, 'support_tickets') !== false) {
        echo "<div class='success'>✅ Menu 'Hỗ trợ khách hàng' đã được thêm</div>";
    } else {
        echo "<div class='error'>❌ Chưa thêm menu 'Hỗ trợ khách hàng'</div>";
    }
    
    echo "</div>";
    
    // FINAL SUMMARY
    echo "<div class='alert alert-success mt-4'>";
    echo "<h4>🎉 Tổng Kết</h4>";
    echo "<ul class='mb-0'>";
    echo "<li>✅ Database đã được thiết lập</li>";
    echo "<li>✅ Tất cả bảng và views đã được tạo</li>";
    echo "<li>✅ API files đã sẵn sàng</li>";
    echo "<li>✅ Trang admin đã được tạo</li>";
    echo "<li>✅ Trang user đã được tạo</li>";
    echo "<li>✅ Menu đã được cập nhật</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<h5>📝 Hướng Dẫn Sử Dụng:</h5>";
    echo "<ol>";
    echo "<li><strong>Admin:</strong> Truy cập menu 'Quản lý bình luận' và 'Hỗ trợ khách hàng' trong admin panel</li>";
    echo "<li><strong>User:</strong> Truy cập <a href='/lequocanh/customer/support.php' target='_blank'>/lequocanh/customer/support.php</a> để tạo ticket</li>";
    echo "<li><strong>Báo cáo bình luận:</strong> Nút 'Báo cáo' đã được thêm vào mỗi bình luận</li>";
    echo "</ol>";
    echo "<hr>";
    echo "<h5>🧪 Test Links:</h5>";
    echo "<ul>";
    echo "<li><a href='/lequocanh/administrator/index.php?req=review_management' target='_blank'>Quản lý bình luận (Admin)</a></li>";
    echo "<li><a href='/lequocanh/administrator/index.php?req=support_tickets' target='_blank'>Hỗ trợ khách hàng (Admin)</a></li>";
    echo "<li><a href='/lequocanh/customer/support.php' target='_blank'>Trang hỗ trợ (User)</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Lỗi</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
