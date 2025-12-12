<?php
/**
 * Script tự động gán quyền marketing_content cho nhân viên
 * Sử dụng: grant_marketing_permission.php?username=nhanviendatcap
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/phanHeQuanLyCls.php';
require_once 'lequocanh/administrator/elements_LQA/mod/userCls.php';
require_once 'lequocanh/administrator/elements_LQA/mod/nhanvienCls.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Gán quyền Marketing</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        input[type='text'] { padding: 8px; width: 300px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Gán quyền Marketing Content cho nhân viên</h1>";

try {
    $db = Database::getInstance()->getConnection();
    $phanHeObj = new PhanHeQuanLy();
    
    // Kiểm tra và thêm module marketing_content nếu chưa có
    $sql = "SELECT idPhanHe FROM PhanHeQuanLy WHERE maPhanHe = 'marketing_content'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $marketingModule = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$marketingModule) {
        echo "<p class='info'>Module 'marketing_content' chưa tồn tại. Đang thêm vào database...</p>";
        $sql = "INSERT INTO PhanHeQuanLy (maPhanHe, tenPhanHe, moTa) 
                VALUES ('marketing_content', 'Nội dung Marketing', 'Quản lý nội dung marketing, banner, tin tức')";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $idPhanHe = $db->lastInsertId();
        echo "<p class='success'>✓ Đã thêm module 'marketing_content' vào database (ID: $idPhanHe)</p>";
    } else {
        $idPhanHe = $marketingModule->idPhanHe;
    }
    
    // Xử lý form submit
    if (isset($_GET['username']) && !empty($_GET['username'])) {
        $username = trim($_GET['username']);
        
        // Lấy thông tin user
        $userObj = new user();
        $userData = $userObj->UserGetbyUsername($username);
        
        if (!$userData) {
            echo "<p class='error'>✗ Không tìm thấy user với username: $username</p>";
        } else {
            // Lấy thông tin nhân viên
            $nvObj = new NhanVien();
            $nhanVienList = $nvObj->nhanvienGetAll();
            $idNhanVien = null;
            
            foreach ($nhanVienList as $nv) {
                if ($nv->iduser == $userData->iduser) {
                    $idNhanVien = $nv->idNhanVien;
                    break;
                }
            }
            
            if (!$idNhanVien) {
                echo "<p class='error'>✗ User '$username' không phải là nhân viên</p>";
            } else {
                // Gán quyền
                $result = $phanHeObj->assignPhanHeToNhanVien($idNhanVien, $idPhanHe);
                
                if ($result) {
                    echo "<p class='success'>✓ Đã gán quyền 'Nội dung Marketing' cho nhân viên '$username' (ID: $idNhanVien)</p>";
                    echo "<p class='info'>Nhân viên này giờ có thể truy cập module Marketing Content mà không cần quyền Admin.</p>";
                } else {
                    echo "<p class='error'>✗ Lỗi khi gán quyền cho nhân viên</p>";
                }
            }
        }
    }
    
    // Hiển thị form
    echo "<h2>Gán quyền cho nhân viên</h2>";
    echo "<form method='GET'>";
    echo "<label for='username'>Username nhân viên:</label><br>";
    echo "<input type='text' id='username' name='username' placeholder='Nhập username nhân viên' required><br><br>";
    echo "<button type='submit'>Gán quyền Marketing</button>";
    echo "</form>";
    
    // Hiển thị danh sách nhân viên
    echo "<h2>Danh sách nhân viên</h2>";
    $sql = "SELECT nv.idNhanVien, nv.tenNhanVien, u.username 
            FROM nhanvien nv 
            INNER JOIN user u ON nv.iduser = u.iduser 
            ORDER BY nv.tenNhanVien";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "<ul>";
    foreach ($employees as $emp) {
        $hasAccess = $phanHeObj->checkNhanVienHasAccess($emp->idNhanVien, 'marketing_content');
        $status = $hasAccess ? '✓ Đã có quyền' : '✗ Chưa có quyền';
        $statusClass = $hasAccess ? 'success' : 'error';
        echo "<li><strong>{$emp->tenNhanVien}</strong> (username: {$emp->username}) - <span class='$statusClass'>$status</span>";
        if (!$hasAccess) {
            echo " <a href='?username={$emp->username}'>[Gán quyền]</a>";
        }
        echo "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
