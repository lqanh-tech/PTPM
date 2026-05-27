<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed');
}

require_once '../mod/mtonkhoCls.php';

$tonkho = new MTonKho();

if (isset($_GET['reqact'])) {
    $reqact = $_GET['reqact'];

    switch ($reqact) {
        case 'update':

            if (isset($_POST['idTonKho']) && isset($_POST['soLuong']) && isset($_POST['soLuongToiThieu'])) {
                $idTonKho = sanitizeInput($_POST['idTonKho'] ?? '', 'int');
                $soLuong = sanitizeInput($_POST['soLuong'] ?? '', 'int');
                $soLuongToiThieu = sanitizeInput($_POST['soLuongToiThieu'] ?? '', 'int');
                $viTri = sanitizeInput($_POST['viTri'] ?? '', 'text');

                $currentTonKho = $tonkho->getTonKhoById($idTonKho);
                if ($currentTonKho) {

                    $soLuong = $currentTonKho->soLuong;
                }

                $result = $tonkho->updateTonKho($idTonKho, $soLuong, $soLuongToiThieu, $viTri);

                if ($result) {
                    header("Location: ../../index.php?req=mtonkho&result=success");
                } else {
                    header("Location: ../../index.php?req=mtonkho&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mtonkho&result=fail");
            }
            break;

        default:
            header("Location: ../../index.php?req=mtonkho");
            break;
    }
} else {
    header("Location: ../../index.php?req=mtonkho");
}
