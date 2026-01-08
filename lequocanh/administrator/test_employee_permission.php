<?php

session_start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    die('Bạn cần đăng nhập để chạy script này.');
}

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/phanquyenCls.php';
require_once './elements_LQA/mod/phanHeQuanLyCls.php';
require_once './elements_LQA/mod/nhanvienCls.php';
require_once './elements_LQA/mod/userCls.php';

$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
$phanQuyen = new PhanQuyen();
$isAdmin = isset($_SESSION['ADMIN']);
$isNhanVien = $phanQuyen->isNhanVien($username);

echo "<h2>Kiểm tra Quyền Truy Cập - Tài khoản: " . htmlspecialchars($username) . "</h2>";
echo "<hr>";

echo "<p><strong>Loại tài khoản:</strong> ";
if ($isAdmin) {
    echo "<span style='color: green;'>Admin</span> - Có quyền truy cập tất cả";
} elseif ($isNhanVien) {
    echo "<span style='color: blue;'>Nhân viên</span> - Chỉ truy cập các module được phân quyền";
} else {
    echo "<span style='color: gray;'>Người dùng thông thường</span>";
}
echo "</p>";

if ($isNhanVien && !$isAdmin) {

    $userObj = new user();
    $userData = $userObj->UserGetbyUsername($username);
    
    if ($userData) {
        $nvObj = new NhanVien();
        $nhanVienList = $nvObj->nhanvienGetAll();
        $idNhanVien = null;
        
        foreach ($nhanVienList as $nv) {
            if ($nv->iduser == $userData->iduser) {
                $idNhanVien = $nv->idNhanVien;
                break;
            }
        }
        
        if ($idNhanVien) {
            $phanHeObj = new PhanHeQuanLy();
            $assignedModules = $phanHeObj->getPhanHeByNhanVienId($idNhanVien);
            
            echo "<h3>Các module được phân quyền:</h3>";
            if (count($assignedModules) > 0) {
                echo "<ul style='color: green;'>";
                foreach ($assignedModules as $module) {
                    echo "<li><strong>" . htmlspecialchars($module->tenPhanHe) . "</strong> (" . htmlspecialchars($module->maPhanHe) . ")</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>Chưa được phân quyền module nào!</p>";
            }
            
            echo "<h3>Kiểm tra quyền truy cập:</h3>";
            echo "<table border='1' cellpadding='10' cellspacing='0'>";
            echo "<tr><th>Module</th><th>Tên</th><th>Quyền truy cập</th></tr>";
            
            $testModules = [
                'hanghoaview' => 'Quản lý hàng hóa',
                'dongiaview' => 'Quản lý đơn giá',
                'khachhangview' => 'Quản lý khách hàng',
                'loaihangview' => 'Quản lý loại hàng',
                'baocaoview' => 'Báo cáo tổng hợp',
                'mtonkho' => 'Quản lý tồn kho',
                'quanLySanPhamDacBiet' => 'Quản Lý & Khuyến Mãi SP',
                'marketing_content' => 'Nội dung Marketing',
                'nhanvienview' => 'Quản lý nhân viên',
                'userview' => 'Quản lý tài khoản'
            ];
            
            foreach ($testModules as $moduleCode => $moduleName) {
                $hasAccess = $phanQuyen->checkAccessForEmployee($moduleCode, $username);
                $accessText = $hasAccess ? 
                    "<span style='color: green;'>✓ CÓ QUYỀN</span>" : 
                    "<span style='color: red;'>✗ KHÔNG CÓ QUYỀN</span>";
                echo "<tr>";
                echo "<td>" . htmlspecialchars($moduleCode) . "</td>";
                echo "<td>" . htmlspecialchars($moduleName) . "</td>";
                echo "<td>$accessText</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>Test truy cập trực tiếp:</h3>";
            echo "<p>Click vào các link dưới đây để test xem có bị chặn không:</p>";
            echo "<ul>";
            foreach ($testModules as $moduleCode => $moduleName) {
                $hasAccess = $phanQuyen->checkAccessForEmployee($moduleCode, $username);
                $style = $hasAccess ? "color: green;" : "color: red;";
                echo "<li><a href='index.php?req=$moduleCode' style='$style' target='_blank'>$moduleName ($moduleCode)</a>";
                echo $hasAccess ? " - Nên truy cập được" : " - Nên bị chặn";
                echo "</li>";
            }
            echo "</ul>";
        }
    }
}

echo "<hr>";
echo "<a href='index.php'>← Quay lại trang quản trị</a>";
?>
