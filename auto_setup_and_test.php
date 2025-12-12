<?php
/**
 * Tự động setup và test hệ thống đổi/trả hàng
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$results = [];
$errors = [];

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Auto Setup & Test - Hệ thống đổi/trả hàng</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
        h1 { color: #2c3e50; }
        h2 { color: #34495e; margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; border-radius: 4px; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545; border-radius: 4px; }
        .warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
        .info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 4px solid #17a2b8; border-radius: 4px; }
        .step { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #3498db; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        tr:hover { background: #f8f9fa; }
        .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #218838; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .progress { background: #e9ecef; height: 30px; border-radius: 5px; margin: 20px 0; }
        .progress-bar { background: #28a745; height: 100%; line-height: 30px; color: white; text-align: center; border-radius: 5px; transition: width 0.3s; }
    </style>
</head>
<body>
    <h1>🚀 Auto Setup & Test - Hệ thống đổi/trả hàng</h1>
    <hr>
";

// ============================================
// BƯỚC 1: KIỂM TRA KẾT NỐI DATABASE
// ============================================
echo "<div class='step'>";
echo "<h2>📊 Bước 1: Kiểm tra kết nối Database</h2>";

try {
    $testQuery = $conn->query("SELECT 1");
    echo "<div class='success'>✅ Kết nối database thành công!</div>";
    $results['db_connection'] = true;
} catch (PDOException $e) {
    echo "<div class='error'>❌ Lỗi kết nối database: " . $e->getMessage() . "</div>";
    $errors[] = "Database connection failed";
    $results['db_connection'] = false;
}
echo "</div>";

// ============================================
// BƯỚC 2: KIỂM TRA VÀ THÊM CỘT
// ============================================
echo "<div class='step'>";
echo "<h2>🔧 Bước 2: Kiểm tra và thêm cột</h2>";

try {
    // Lấy danh sách cột hiện có
    $columnsSql = "SHOW COLUMNS FROM don_hang";
    $columnsStmt = $conn->query($columnsSql);
    $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'>📋 Các cột hiện có: " . count($existingColumns) . " cột</div>";
    
    // Định nghĩa các cột cần thêm
    $columnsToAdd = [
        'trang_thai_doi_tra' => "ENUM('none', 'requested', 'approved', 'rejected') DEFAULT 'none' COMMENT 'Trạng thái đổi/trả'",
        'ly_do_doi_tra' => "TEXT DEFAULT NULL COMMENT 'Lý do đổi/trả'",
        'ngay_yeu_cau_doi_tra' => "DATETIME DEFAULT NULL COMMENT 'Ngày yêu cầu'",
        'admin_note' => "TEXT DEFAULT NULL COMMENT 'Ghi chú admin'",
        'ngay_xu_ly_doi_tra' => "DATETIME DEFAULT NULL COMMENT 'Ngày xử lý'"
    ];
    
    $added = 0;
    $skipped = 0;
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (in_array($columnName, $existingColumns)) {
            echo "<div class='warning'>⚠️ Cột <strong>$columnName</strong> đã tồn tại</div>";
            $skipped++;
        } else {
            try {
                $alterSql = "ALTER TABLE don_hang ADD COLUMN $columnName $columnDef";
                $conn->exec($alterSql);
                echo "<div class='success'>✅ Đã thêm cột: <strong>$columnName</strong></div>";
                $added++;
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Lỗi thêm cột $columnName: " . $e->getMessage() . "</div>";
                $errors[] = "Failed to add column: $columnName";
            }
        }
    }
    
    echo "<div class='info'>📊 Tổng kết: Đã thêm <strong>$added</strong> cột, Bỏ qua <strong>$skipped</strong> cột</div>";
    $results['columns_added'] = $added;
    $results['columns_skipped'] = $skipped;
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
    $errors[] = "Column check failed";
}
echo "</div>";

// ============================================
// BƯỚC 3: TEST QUERY
// ============================================
echo "<div class='step'>";
echo "<h2>🧪 Bước 3: Test Query</h2>";

try {
    $testSql = "SELECT id, ma_don_hang_text, trang_thai, trang_thai_doi_tra, ly_do_doi_tra 
                FROM don_hang 
                LIMIT 3";
    $testStmt = $conn->query($testSql);
    $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($testResults) {
        echo "<div class='success'>✅ Query thành công! Tìm thấy " . count($testResults) . " đơn hàng</div>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Mã đơn hàng</th><th>Trạng thái</th><th>Trạng thái đổi/trả</th></tr>";
        foreach ($testResults as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['ma_don_hang_text']}</td>";
            echo "<td>{$row['trang_thai']}</td>";
            echo "<td>" . ($row['trang_thai_doi_tra'] ?? 'none') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $results['query_test'] = true;
    } else {
        echo "<div class='warning'>⚠️ Không có dữ liệu để test</div>";
        $results['query_test'] = true;
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Lỗi query: " . $e->getMessage() . "</div>";
    $errors[] = "Query test failed";
    $results['query_test'] = false;
}
echo "</div>";

// ============================================
// BƯỚC 4: KIỂM TRA FILE
// ============================================
echo "<div class='step'>";
echo "<h2>📁 Bước 4: Kiểm tra các file cần thiết</h2>";

$requiredFiles = [
    'lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php' => 'Trang chi tiết đơn hàng',
    'lequocanh/administrator/elements_LQA/mgiohang/returnRequestHandler.php' => 'Xử lý yêu cầu đổi/trả',
    'lequocanh/administrator/elements_LQA/mgiohang/giohangView.php' => 'Trang giỏ hàng',
    'lequocanh/administrator/elements_LQA/mod/mtonkhoCls.php' => 'Class quản lý tồn kho'
];

$filesOk = 0;
$filesMissing = 0;

echo "<table>";
echo "<tr><th>File</th><th>Mô tả</th><th>Trạng thái</th></tr>";

foreach ($requiredFiles as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? "<span style='color: green;'>✅ Có</span>" : "<span style='color: red;'>❌ Thiếu</span>";
    
    echo "<tr>";
    echo "<td><code>$file</code></td>";
    echo "<td>$description</td>";
    echo "<td>$status</td>";
    echo "</tr>";
    
    if ($exists) {
        $filesOk++;
    } else {
        $filesMissing++;
        $errors[] = "Missing file: $file";
    }
}

echo "</table>";
echo "<div class='info'>📊 Kết quả: <strong>$filesOk</strong> file OK, <strong>$filesMissing</strong> file thiếu</div>";
$results['files_ok'] = $filesOk;
$results['files_missing'] = $filesMissing;

echo "</div>";

// ============================================
// BƯỚC 5: TẠO ĐƠN HÀNG TEST (NẾU CẦN)
// ============================================
echo "<div class='step'>";
echo "<h2>🧪 Bước 5: Tạo đơn hàng test</h2>";

try {
    // Kiểm tra xem có đơn hàng nào chưa
    $countSql = "SELECT COUNT(*) as total FROM don_hang WHERE trang_thai = 'approved'";
    $countStmt = $conn->query($countSql);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count > 0) {
        echo "<div class='success'>✅ Đã có <strong>$count</strong> đơn hàng 'Đã duyệt' để test</div>";
        
        // Lấy 1 đơn hàng mẫu
        $sampleSql = "SELECT id, ma_don_hang_text, trang_thai, trang_thai_doi_tra 
                      FROM don_hang 
                      WHERE trang_thai = 'approved' 
                      LIMIT 1";
        $sampleStmt = $conn->query($sampleSql);
        $sample = $sampleStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample) {
            echo "<div class='info'>";
            echo "<p>📦 Đơn hàng mẫu để test:</p>";
            echo "<ul>";
            echo "<li>ID: <strong>{$sample['id']}</strong></li>";
            echo "<li>Mã: <strong>{$sample['ma_don_hang_text']}</strong></li>";
            echo "<li>Trạng thái: <strong>{$sample['trang_thai']}</strong></li>";
            echo "<li>Đổi/trả: <strong>" . ($sample['trang_thai_doi_tra'] ?? 'none') . "</strong></li>";
            echo "</ul>";
            echo "<a href='lequocanh/administrator/elements_LQA/mgiohang/orderDetailView.php?id={$sample['id']}' class='btn' target='_blank'>🔗 Xem đơn hàng này</a>";
            echo "</div>";
            $results['test_order_id'] = $sample['id'];
        }
    } else {
        echo "<div class='warning'>⚠️ Chưa có đơn hàng 'Đã duyệt' nào để test chức năng đổi/trả</div>";
        echo "<div class='info'>💡 Bạn cần tạo đơn hàng và duyệt nó trước khi test chức năng đổi/trả</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Lỗi: " . $e->getMessage() . "</div>";
}

echo "</div>";

// ============================================
// BƯỚC 6: TỔNG KẾT
// ============================================
echo "<div class='step'>";
echo "<h2>📊 Bước 6: Tổng kết</h2>";

$totalSteps = 5;
$successSteps = 0;

if ($results['db_connection'] ?? false) $successSteps++;
if (($results['columns_added'] ?? 0) >= 0) $successSteps++;
if ($results['query_test'] ?? false) $successSteps++;
if (($results['files_ok'] ?? 0) > 0) $successSteps++;
if (isset($results['test_order_id'])) $successSteps++;

$percentage = round(($successSteps / $totalSteps) * 100);

echo "<div class='progress'>";
echo "<div class='progress-bar' style='width: {$percentage}%'>{$percentage}%</div>";
echo "</div>";

if (empty($errors)) {
    echo "<div class='success'>";
    echo "<h3>🎉 Hoàn tất! Hệ thống đã sẵn sàng</h3>";
    echo "<p>Tất cả các bước đã hoàn thành thành công. Bạn có thể bắt đầu sử dụng chức năng đổi/trả hàng.</p>";
    echo "<h4>📝 Hướng dẫn sử dụng:</h4>";
    echo "<ol>";
    echo "<li>Vào <strong>Giỏ hàng</strong> → <strong>Lịch sử đơn hàng</strong></li>";
    echo "<li>Chọn đơn hàng đã duyệt và nhấn <strong>Xem chi tiết</strong></li>";
    echo "<li>Nhấn nút <strong>Yêu cầu đổi/trả hàng</strong></li>";
    echo "<li>Điền lý do (tối thiểu 20 ký tự) và gửi</li>";
    echo "</ol>";
    echo "<a href='lequocanh/administrator/elements_LQA/mgiohang/giohangView.php' class='btn'>🛒 Đi đến Giỏ hàng</a>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Có lỗi xảy ra</h3>";
    echo "<p>Một số bước chưa hoàn thành:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Hiển thị chi tiết kết quả
echo "<h4>Chi tiết kết quả:</h4>";
echo "<pre>" . print_r($results, true) . "</pre>";

echo "</div>";

echo "</body></html>";
?>
