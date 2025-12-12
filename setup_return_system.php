<?php
/**
 * Setup và kiểm tra hệ thống đổi/trả hàng
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>🔧 Setup Hệ Thống Đổi/Trả Hàng</h1>";
echo "<hr>";

$errors = [];
$success = [];

try {
    // 1. Kiểm tra và thêm các cột cần thiết
    echo "<h2>1. Kiểm tra cấu trúc database</h2>";
    
    $columnsSql = "SHOW COLUMNS FROM don_hang";
    $columnsStmt = $conn->query($columnsSql);
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = [
        'trang_thai_doi_tra' => "ENUM('none', 'requested', 'approved', 'rejected') DEFAULT 'none' COMMENT 'Trạng thái đổi trả'",
        'ly_do_doi_tra' => "TEXT DEFAULT NULL COMMENT 'Lý do đổi trả từ khách hàng'",
        'ngay_yeu_cau_doi_tra' => "DATETIME DEFAULT NULL COMMENT 'Ngày yêu cầu đổi trả'",
        'admin_note' => "TEXT DEFAULT NULL COMMENT 'Ghi chú từ admin khi xử lý'",
        'ngay_xu_ly_doi_tra' => "DATETIME DEFAULT NULL COMMENT 'Ngày admin xử lý yêu cầu'"
    ];
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $columns)) {
            try {
                $alterSql = "ALTER TABLE don_hang ADD COLUMN $columnName $columnDef";
                $conn->exec($alterSql);
                $success[] = "✅ Đã thêm cột: <strong>$columnName</strong>";
            } catch (PDOException $e) {
                $errors[] = "❌ Lỗi khi thêm cột $columnName: " . $e->getMessage();
            }
        } else {
            $success[] = "✅ Cột <strong>$columnName</strong> đã tồn tại";
        }
    }
    
    // 2. Kiểm tra các file cần thiết
    echo "<h2>2. Kiểm tra các file</h2>";
    
    $requiredFiles = [
        'lequocanh/administrator/elements_LQA/mgiohang/orderDetailView_v2.php' => 'Trang chi tiết đơn hàng (phiên bản mới)',
        'lequocanh/administrator/elements_LQA/mgiohang/orderCancelAct.php' => 'Xử lý hủy đơn hàng',
        'lequocanh/administrator/elements_LQA/mgiohang/orderReturnAct.php' => 'Xử lý yêu cầu đổi/trả',
        'lequocanh/administrator/elements_LQA/mod/mtonkhoCls.php' => 'Class quản lý tồn kho'
    ];
    
    foreach ($requiredFiles as $file => $description) {
        if (file_exists($file)) {
            $success[] = "✅ File <strong>$description</strong> tồn tại";
        } else {
            $errors[] = "❌ Thiếu file: <strong>$description</strong> ($file)";
        }
    }
    
    // 3. Test cases
    echo "<h2>3. Các trường hợp test</h2>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;'>";
    echo "<h4>Test Case 1: Hủy đơn hàng (trong 1h)</h4>";
    echo "<ul>";
    echo "<li>Tạo đơn hàng mới với trạng thái 'pending'</li>";
    echo "<li>Trong vòng 1 giờ, khách hàng có thể nhấn nút 'Hủy đơn hàng'</li>";
    echo "<li>Hệ thống sẽ hoàn hàng vào kho và cập nhật trạng thái thành 'cancelled'</li>";
    echo "<li>Sau 1 giờ, nút 'Hủy đơn hàng' sẽ biến mất</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
    echo "<h4>Test Case 2: Yêu cầu đổi/trả hàng</h4>";
    echo "<ul>";
    echo "<li>Đơn hàng phải có trạng thái 'approved' (đã duyệt)</li>";
    echo "<li>Khách hàng nhấn nút 'Yêu cầu đổi/trả hàng'</li>";
    echo "<li>Điền lý do (tối thiểu 20 ký tự, tối đa 1000 ký tự)</li>";
    echo "<li>Check đồng ý điều kiện</li>";
    echo "<li>Gửi yêu cầu → Trạng thái chuyển thành 'requested'</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
    echo "<h4>Test Case 3: Admin xử lý yêu cầu</h4>";
    echo "<ul>";
    echo "<li>Admin vào 'Quản lý đơn hàng'</li>";
    echo "<li>Xem chi tiết đơn hàng có yêu cầu đổi/trả</li>";
    echo "<li>Đọc lý do khách hàng</li>";
    echo "<li>Quyết định: Duyệt (approved) hoặc Từ chối (rejected)</li>";
    echo "<li>Nếu duyệt: Hàng tự động hoàn vào kho</li>";
    echo "</ul>";
    echo "</div>";
    
    // 4. Hướng dẫn sử dụng
    echo "<h2>4. Hướng dẫn sử dụng</h2>";
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
    echo "<h4>Cho Khách Hàng:</h4>";
    echo "<ol>";
    echo "<li>Vào <strong>Giỏ hàng</strong> → <strong>Lịch sử đơn hàng</strong></li>";
    echo "<li>Nhấn <strong>Xem chi tiết</strong> đơn hàng</li>";
    echo "<li><strong>Hủy đơn:</strong> Nhấn nút 'Hủy đơn hàng' (chỉ trong 1h đầu)</li>";
    echo "<li><strong>Đổi/trả:</strong> Nhấn nút 'Yêu cầu đổi/trả hàng' (đơn đã duyệt)</li>";
    echo "<li>Điền lý do chi tiết (≥ 20 ký tự)</li>";
    echo "<li>Check đồng ý điều kiện và gửi</li>";
    echo "</ol>";
    
    echo "<h4>Cho Admin:</h4>";
    echo "<ol>";
    echo "<li>Vào <strong>Quản lý đơn hàng</strong></li>";
    echo "<li>Tìm đơn hàng có trạng thái đổi/trả = 'requested'</li>";
    echo "<li>Xem chi tiết và đọc lý do khách hàng</li>";
    echo "<li>Nhấn <strong>Duyệt đổi trả</strong> hoặc <strong>Từ chối đổi trả</strong></li>";
    echo "<li>Có thể thêm ghi chú cho khách hàng</li>";
    echo "</ol>";
    echo "</div>";
    
    // 5. Cảnh báo quan trọng
    echo "<h2>5. ⚠️ Lưu ý quan trọng</h2>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
    echo "<h4>Bảo mật và Validation:</h4>";
    echo "<ul>";
    echo "<li>✅ Kiểm tra quyền sở hữu đơn hàng</li>";
    echo "<li>✅ Kiểm tra thời gian hủy đơn (≤ 1h)</li>";
    echo "<li>✅ Kiểm tra trạng thái đơn hàng</li>";
    echo "<li>✅ Validate độ dài lý do (20-1000 ký tự)</li>";
    echo "<li>✅ Sanitize input để tránh XSS</li>";
    echo "<li>✅ Sử dụng prepared statements</li>";
    echo "<li>✅ Transaction khi hoàn kho</li>";
    echo "<li>✅ Ghi log mọi thao tác</li>";
    echo "</ul>";
    echo "</div>";
    
    // 6. Kết quả
    echo "<h2>6. Kết quả setup</h2>";
    
    if (!empty($success)) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
        echo "<h4>✅ Thành công:</h4>";
        echo "<ul>";
        foreach ($success as $msg) {
            echo "<li>$msg</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>";
        echo "<h4>❌ Lỗi:</h4>";
        echo "<ul>";
        foreach ($errors as $msg) {
            echo "<li>$msg</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (empty($errors)) {
        echo "<div style='background: #d4edda; padding: 20px; text-align: center; border-radius: 8px; margin: 30px 0;'>";
        echo "<h3 style='color: #28a745;'>🎉 Hệ thống đã sẵn sàng!</h3>";
        echo "<p>Bạn có thể bắt đầu sử dụng chức năng đổi/trả hàng.</p>";
        echo "<a href='lequocanh/administrator/elements_LQA/mgiohang/giohangView.php' class='btn' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Đi đến Giỏ hàng</a>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<h3>❌ Lỗi nghiêm trọng:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
    h1 { color: #2c3e50; }
    h2 { color: #34495e; margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6; }
    h4 { color: #2c3e50; margin-top: 15px; }
    ul { line-height: 1.8; }
    .btn { display: inline-block; }
</style>
