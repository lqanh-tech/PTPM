<?php
/**
 * Script đồng bộ các module phân quyền mới vào database
 * Chạy script này sau khi thêm module mới vào menu left.php
 */

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['ADMIN'])) {
    die('Bạn cần đăng nhập với quyền Admin để chạy script này.');
}

require_once './elements_LQA/mod/phanHeQuanLyCls.php';

echo "<h2>Đồng bộ Module Phân Quyền</h2>";

try {
    $phanHeObj = new PhanHeQuanLy();
    
    // Đồng bộ các module mới
    $addedCount = $phanHeObj->syncModules();
    
    if ($addedCount > 0) {
        echo "<p style='color: green;'>✓ Đã thêm thành công <strong>$addedCount</strong> module mới vào hệ thống phân quyền.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Tất cả các module đã tồn tại trong hệ thống.</p>";
    }
    
    // Hiển thị danh sách tất cả module hiện có
    echo "<h3>Danh sách tất cả Module Phân Quyền:</h3>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Mã Module</th><th>Tên Module</th><th>Mô tả</th><th>Trạng thái</th></tr>";
    
    $allModules = $phanHeObj->getAllPhanHe();
    foreach ($allModules as $module) {
        $status = $module->trangThai ? '<span style="color:green">Hoạt động</span>' : '<span style="color:red">Tắt</span>';
        echo "<tr>";
        echo "<td>{$module->idPhanHe}</td>";
        echo "<td>{$module->maPhanHe}</td>";
        echo "<td>{$module->tenPhanHe}</td>";
        echo "<td>{$module->moTa}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><a href='index.php?req=nhanvienview'>← Quay lại Quản lý Nhân viên</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
