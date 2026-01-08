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

echo "<h2>Debug Quyền Truy Cập Nhân Viên</h2>";
echo "<hr>";

echo "<h3>1. Thông tin Session:</h3>";
echo "<pre>";
echo "SESSION['USER']: " . (isset($_SESSION['USER']) ? $_SESSION['USER'] : 'NOT SET') . "\n";
echo "SESSION['ADMIN']: " . (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : 'NOT SET') . "\n";
echo "Username hiện tại: $username\n";
echo "</pre>";

echo "<h3>2. Kiểm tra User trong Database:</h3>";
$userObj = new user();
$userData = $userObj->UserGetbyUsername($username);
echo "<pre>";
if ($userData) {
    echo "User ID: " . $userData->iduser . "\n";
    echo "Username: " . $userData->username . "\n";
    echo "Họ tên: " . $userData->hoten . "\n";
} else {
    echo "KHÔNG TÌM THẤY USER TRONG DATABASE!\n";
}
echo "</pre>";

echo "<h3>3. Kiểm tra Nhân viên trong Database:</h3>";
$phanQuyen = new PhanQuyen();
$isNhanVien = $phanQuyen->isNhanVien($username);
echo "<pre>";
echo "isNhanVien(): " . ($isNhanVien ? 'TRUE - Là nhân viên' : 'FALSE - Không phải nhân viên') . "\n";
echo "</pre>";

if ($userData) {
    $nvObj = new NhanVien();
    $nhanVienList = $nvObj->nhanvienGetAll();
    $idNhanVien = null;
    $nhanVienInfo = null;
    
    foreach ($nhanVienList as $nv) {
        if ($nv->iduser == $userData->iduser) {
            $idNhanVien = $nv->idNhanVien;
            $nhanVienInfo = $nv;
            break;
        }
    }
    
    echo "<pre>";
    if ($idNhanVien) {
        echo "Nhân viên ID: $idNhanVien\n";
        echo "Tên nhân viên: " . $nhanVienInfo->tenNV . "\n";
        echo "Chức vụ: " . $nhanVienInfo->chucVu . "\n";
    } else {
        echo "KHÔNG TÌM THẤY NHÂN VIÊN LIÊN KẾT VỚI USER NÀY!\n";
    }
    echo "</pre>";
    
    if ($idNhanVien) {
        echo "<h3>4. Danh sách Phần hệ được gán cho nhân viên:</h3>";
        $phanHeObj = new PhanHeQuanLy();
        $listPhanHe = $phanHeObj->getPhanHeByNhanVienId($idNhanVien);
        
        echo "<pre>";
        if (count($listPhanHe) > 0) {
            echo "Số phần hệ được gán: " . count($listPhanHe) . "\n\n";
            foreach ($listPhanHe as $ph) {
                echo "- " . $ph->tenPhanHe . " (" . $ph->maPhanHe . ")\n";
            }
        } else {
            echo "CHƯA ĐƯỢC GÁN PHẦN HỆ NÀO!\n";
            echo "Vui lòng vào trang Quản lý Nhân viên để gán phần hệ cho nhân viên này.\n";
        }
        echo "</pre>";
        
        echo "<h3>5. Kiểm tra quyền truy cập một số module:</h3>";
        $testModules = ['hanghoaview', 'dongiaview', 'baocaoview', 'khachhangview', 'loaihangview'];
        echo "<pre>";
        foreach ($testModules as $module) {
            $hasAccess = $phanHeObj->checkNhanVienHasAccess($idNhanVien, $module);
            echo "$module: " . ($hasAccess ? 'CÓ QUYỀN' : 'KHÔNG CÓ QUYỀN') . "\n";
        }
        echo "</pre>";
    }
}

echo "<h3>6. Tất cả Phần hệ trong hệ thống:</h3>";
$phanHeObj = new PhanHeQuanLy();
$allPhanHe = $phanHeObj->getAllPhanHe();
echo "<pre>";
echo "Tổng số phần hệ: " . count($allPhanHe) . "\n\n";
foreach ($allPhanHe as $ph) {
    echo "- " . $ph->tenPhanHe . " (" . $ph->maPhanHe . ")\n";
}
echo "</pre>";

echo "<hr>";
echo "<a href='index.php'>← Quay lại trang quản trị</a>";
?>
