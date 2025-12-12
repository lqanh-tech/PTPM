<?php
/**
 * Script kiểm tra quyền truy cập module marketing_content của nhân viên
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/phanquyenCls.php';
require_once 'lequocanh/administrator/elements_LQA/mod/phanHeQuanLyCls.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Kiểm tra quyền Marketing</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Kiểm tra quyền truy cập module Marketing Content</h1>";

try {
    $db = Database::getInstance()->getConnection();
    $phanQuyen = new PhanQuyen();
    $phanHeObj = new PhanHeQuanLy();
    
    // Lấy danh sách tất cả nhân viên
    $sql = "SELECT nv.idNhanVien, nv.tenNhanVien, u.username 
            FROM nhanvien nv 
            INNER JOIN user u ON nv.iduser = u.iduser 
            ORDER BY nv.tenNhanVien";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "<h2>Danh sách nhân viên và quyền truy cập</h2>";
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Tên nhân viên</th>
            <th>Username</th>
            <th>Có quyền Marketing?</th>
            <th>Các module được gán</th>
          </tr>";
    
    foreach ($employees as $emp) {
        $hasAccess = $phanHeObj->checkNhanVienHasAccess($emp->idNhanVien, 'marketing_content');
        $modules = $phanHeObj->getPhanHeByNhanVienId($emp->idNhanVien);
        
        $moduleNames = [];
        foreach ($modules as $module) {
            $moduleNames[] = $module->tenPhanHe;
        }
        
        $accessClass = $hasAccess ? 'success' : 'error';
        $accessText = $hasAccess ? '✓ Có quyền' : '✗ Không có quyền';
        
        echo "<tr>";
        echo "<td>{$emp->idNhanVien}</td>";
        echo "<td>{$emp->tenNhanVien}</td>";
        echo "<td>{$emp->username}</td>";
        echo "<td class='$accessClass'>$accessText</td>";
        echo "<td>" . (count($moduleNames) > 0 ? implode(', ', $moduleNames) : 'Chưa có quyền nào') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Kiểm tra module marketing_content có tồn tại trong database không
    echo "<h2>Kiểm tra module Marketing Content trong database</h2>";
    $sql = "SELECT * FROM PhanHeQuanLy WHERE maPhanHe = 'marketing_content'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $marketingModule = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($marketingModule) {
        echo "<p class='success'>✓ Module 'marketing_content' đã tồn tại trong database</p>";
        echo "<ul>";
        echo "<li>ID: {$marketingModule->idPhanHe}</li>";
        echo "<li>Mã: {$marketingModule->maPhanHe}</li>";
        echo "<li>Tên: {$marketingModule->tenPhanHe}</li>";
        echo "<li>Trạng thái: " . ($marketingModule->trangThai ? 'Kích hoạt' : 'Vô hiệu hóa') . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ Module 'marketing_content' CHƯA tồn tại trong database</p>";
        echo "<p class='info'>Đang thêm module vào database...</p>";
        
        // Thêm module vào database
        $sql = "INSERT INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) 
                VALUES ('marketing_content', 'Nội dung Marketing', 'Quản lý nội dung marketing, banner, tin tức')";
        $stmt = $db->prepare($sql);
        if ($stmt->execute()) {
            echo "<p class='success'>✓ Đã thêm module 'marketing_content' vào database</p>";
        } else {
            echo "<p class='error'>✗ Lỗi khi thêm module vào database</p>";
        }
    }
    
    // Hướng dẫn gán quyền
    echo "<h2>Hướng dẫn gán quyền cho nhân viên</h2>";
    echo "<ol>";
    echo "<li>Đăng nhập với tài khoản Admin</li>";
    echo "<li>Vào menu <strong>Quản lý > Nhân viên</strong></li>";
    echo "<li>Chọn nhân viên cần gán quyền</li>";
    echo "<li>Trong phần 'Phân hệ quản lý', tích chọn <strong>Nội dung Marketing</strong></li>";
    echo "<li>Lưu lại</li>";
    echo "</ol>";
    
    echo "<p class='info'>Sau khi gán quyền, nhân viên sẽ có thể truy cập module Marketing Content mà không cần quyền Admin.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
