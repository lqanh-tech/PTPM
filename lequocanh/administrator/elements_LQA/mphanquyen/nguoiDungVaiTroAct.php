<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {

    require_once __DIR__ . '/../mod/sessionManager.php';
    require_once __DIR__ . '/../config/logger_config.php';

    SessionManager::start();
}

require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../index.php');
    exit;
}

require_once '../mod/roleCls.php';
$roleObj = new Role();

require_once '../mod/userCls.php';
$userObj = new user();

require_once '../mod/nhanvienCls.php';
$nhanVienObj = new NhanVien();

$reqact = isset($_GET['reqact']) ? $_GET['reqact'] : '';

switch ($reqact) {

    case 'assign':
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;

        if ($user_id <= 0 || $role_id <= 0) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        $user = $userObj->UserGetbyId($user_id);
        if (!$user) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        $role = $roleObj->getRoleById($role_id);
        if (!$role) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        $result = $roleObj->assignRoleToUser($user_id, $role_id);

        if ($result) {

            if (($role->ten_vai_tro ?? '') === 'staff') {

                $existingStaff = false;
                $allStaff = $nhanVienObj->nhanvienGetAll();

                foreach ($allStaff as $staff) {
                    if ($staff->iduser == $user_id) {
                        $existingStaff = true;
                        break;
                    }
                }

                if (!$existingStaff) {

                    $userData = $userObj->UserGetbyId($user_id);

                    $tenNV = $userData->hoten;
                    $SDT = $userData->dienthoai;
                    $email = $userData->email ?? '';
                    $luongCB = 0;
                    $phuCap = 0;
                    $chucVu = 'Nhân viên mới';

                    $nhanVienObj->nhanvienAdd($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $user_id);
                }
            }

            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=ok');
        } else {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=failed');
        }
        break;

    case 'remove':
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;

        if ($user_id <= 0 || $role_id <= 0) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        $user = $userObj->UserGetbyId($user_id);
        if (!$user) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        $role = $roleObj->getRoleById($role_id);
        if (!$role) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        if ($user->username == 'admin' && ($role->ten_vai_tro ?? '') == 'admin') {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=failed');
            exit;
        }

        $result = $roleObj->removeRoleFromUser($user_id, $role_id);

        if ($result) {

            if (($role->ten_vai_tro ?? '') === 'staff') {

                $userRoles = $roleObj->getUserRoles($user_id);
                $hasStaffRole = false;

                foreach ($userRoles as $userRole) {
                    if (($userRole->ten_vai_tro ?? '') === 'staff') {
                        $hasStaffRole = true;
                        break;
                    }
                }

                if (!$hasStaffRole) {

                    $allStaff = $nhanVienObj->nhanvienGetAll();

                    foreach ($allStaff as $staff) {
                        if ($staff->iduser == $user_id) {

                            $nhanVienObj->nhanvienDelete($staff->idNhanVien);
                            break;
                        }
                    }
                }
            }

            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=ok');
        } else {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=failed');
        }
        break;

    default:
        header('Location: ../../index.php?req=nguoiDungVaiTroView');
        break;
}
