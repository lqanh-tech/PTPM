<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Page</h2>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>1. Session Info:</h3>";
echo "<pre>";
echo "SESSION['USER']: " . (isset($_SESSION['USER']) ? $_SESSION['USER'] : 'NOT SET') . "\n";
echo "SESSION['ADMIN']: " . (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : 'NOT SET') . "\n";
echo "</pre>";

$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');
echo "<p>Username: <strong>$username</strong></p>";

if (empty($username)) {
    die("<p style='color:red'>Bạn chưa đăng nhập!</p>");
}

echo "<h3>2. Test Database Connection:</h3>";
try {
    require_once './elements_LQA/mod/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p style='color:green'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h3>3. Test PhanQuyen Class:</h3>";
try {
    require_once './elements_LQA/mod/phanquyenCls.php';
    $phanQuyen = new PhanQuyen();
    echo "<p style='color:green'>✓ PhanQuyen class loaded</p>";
    
    $isNhanVien = $phanQuyen->isNhanVien($username);
    echo "<p>isNhanVien('$username'): <strong>" . ($isNhanVien ? 'TRUE' : 'FALSE') . "</strong></p>";
    
    $isAdmin = isset($_SESSION['ADMIN']);
    echo "<p>isAdmin (by session): <strong>" . ($isAdmin ? 'TRUE' : 'FALSE') . "</strong></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ PhanQuyen error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>4. Test User Info:</h3>";
try {
    require_once './elements_LQA/mod/userCls.php';
    $userObj = new user();
    $userData = $userObj->UserGetbyUsername($username);
    
    if ($userData) {
        echo "<p style='color:green'>✓ User found in database</p>";
        echo "<pre>";
        echo "User ID: " . $userData->iduser . "\n";
        echo "Username: " . $userData->username . "\n";
        echo "Họ tên: " . $userData->hoten . "\n";
        echo "</pre>";
    } else {
        echo "<p style='color:red'>✗ User NOT found in database!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ User error: " . $e->getMessage() . "</p>";
}

echo "<h3>5. Test NhanVien Info:</h3>";
try {
    require_once './elements_LQA/mod/nhanvienCls.php';
    $nvObj = new NhanVien();
    $nhanVienList = $nvObj->nhanvienGetAll();
    
    $idNhanVien = null;
    $nhanVienInfo = null;
    
    if ($userData) {
        foreach ($nhanVienList as $nv) {
            if ($nv->iduser == $userData->iduser) {
                $idNhanVien = $nv->idNhanVien;
                $nhanVienInfo = $nv;
                break;
            }
        }
    }
    
    if ($idNhanVien) {
        echo "<p style='color:green'>✓ Nhân viên found</p>";
        echo "<pre>";
        echo "Nhân viên ID: " . $idNhanVien . "\n";
        echo "Tên NV: " . $nhanVienInfo->tenNV . "\n";
        echo "Chức vụ: " . $nhanVienInfo->chucVu . "\n";
        echo "</pre>";
    } else {
        echo "<p style='color:red'>✗ Nhân viên NOT found for this user!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ NhanVien error: " . $e->getMessage() . "</p>";
}

echo "<h3>6. Test PhanHeQuanLy:</h3>";
try {
    require_once './elements_LQA/mod/phanHeQuanLyCls.php';
    $phanHeObj = new PhanHeQuanLy();
    echo "<p style='color:green'>✓ PhanHeQuanLy class loaded</p>";
    
    if ($idNhanVien) {
        $assignedModules = $phanHeObj->getPhanHeByNhanVienId($idNhanVien);
        echo "<p>Số module được gán: <strong>" . count($assignedModules) . "</strong></p>";
        
        if (count($assignedModules) > 0) {
            echo "<ul>";
            foreach ($assignedModules as $module) {
                echo "<li>" . $module->tenPhanHe . " (" . $module->maPhanHe . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠ Nhân viên chưa được gán module nào!</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ PhanHeQuanLy error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>7. Test checkAccessForEmployee:</h3>";
try {
    $testModules = ['hanghoaview', 'dongiaview', 'baocaoview'];
    foreach ($testModules as $module) {
        $hasAccess = $phanQuyen->checkAccessForEmployee($module, $username);
        $status = $hasAccess ? "<span style='color:green'>CÓ QUYỀN</span>" : "<span style='color:red'>KHÔNG CÓ QUYỀN</span>";
        echo "<p>$module: $status</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ checkAccessForEmployee error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Quay lại trang quản trị</a></p>";
?>
