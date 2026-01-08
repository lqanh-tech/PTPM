<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {

    require_once __DIR__ . '/../mod/sessionManager.php';
    require_once __DIR__ . '/../config/logger_config.php';

    SessionManager::start();
}

require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->isNhanVien($username)) {
    header('Location: ../../index.php');
    exit;
}

require_once '../mod/roleCls.php';
$roleObj = new Role();

$reqact = isset($_GET['reqact']) ? $_GET['reqact'] : '';

switch ($reqact) {

    case 'addnew':
        $role_name = isset($_POST['role_name']) ? trim($_POST['role_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        $existingRole = $roleObj->getRoleByName($role_name);
        if ($existingRole) {
            header('Location: ../../index.php?req=vaiTroView&result=exists');
            exit;
        }

        $result = $roleObj->addRole($role_name, $description);

        if ($result) {
            header('Location: ../../index.php?req=vaiTroView&result=ok');
        } else {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
        }
        break;

    case 'update':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $role_name = isset($_POST['role_name']) ? trim($_POST['role_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        $existingRole = $roleObj->getRoleById($id);
        if (!$existingRole) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        if ($existingRole->ten_vai_tro != $role_name) {
            $checkRole = $roleObj->getRoleByName($role_name);
            if ($checkRole) {
                header('Location: ../../index.php?req=vaiTroView&result=exists');
                exit;
            }
        }

        if (in_array($existingRole->ten_vai_tro, ['admin', 'staff', 'customer']) && $existingRole->ten_vai_tro != $role_name) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        $result = $roleObj->updateRole($id, $role_name, $description);

        if ($result) {
            header('Location: ../../index.php?req=vaiTroView&result=ok');
        } else {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
        }
        break;

    case 'delete':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $existingRole = $roleObj->getRoleById($id);
        if (!$existingRole) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        if (in_array($existingRole->ten_vai_tro, ['admin', 'staff', 'customer'])) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        $result = $roleObj->deleteRole($id);

        if ($result) {
            header('Location: ../../index.php?req=vaiTroView&result=ok');
        } else {
            header('Location: ../../index.php?req=vaiTroView&result=in_use');
        }
        break;

    default:
        header('Location: ../../index.php?req=vaiTroView');
        break;
}
