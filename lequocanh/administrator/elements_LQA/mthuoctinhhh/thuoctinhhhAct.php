<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../mod/securityEnhancement.php';

SecurityEnhancement::initializePage(true);

SessionManager::start();
require '../../elements_LQA/mod/thuoctinhhhCls.php';

if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    $thuocTinhHHObj = new ThuocTinhHH();

    switch ($requestAction) {
        case 'addnew':

            $idhanghoa = $_POST['idhanghoa'] ?? null;
            $idThuocTinh = $_POST['idThuocTinh'] ?? null;
            $tenThuocTinhHH = $_POST['tenThuocTinhHH'] ?? null;
            $ghiChu = $_POST['ghiChu'] ?? null;

            if ($idhanghoa && $idThuocTinh && $tenThuocTinhHH) {
                $result = $thuocTinhHHObj->thuoctinhhhAdd($idhanghoa, $idThuocTinh, $tenThuocTinhHH,  $ghiChu);
                header("Location: ../../index.php?req=thuoctinhhhview&result=" . ($result ? 'ok' : 'notok'));
            } else {

                header("Location: ../../index.php?req=thuoctinhhhview&result=notok&error=missing_data");
            }
            break;

        case 'deletethuoctinhhh':

            $idThuocTinhHH = $_GET['idThuocTinhHH'] ?? null;
            if ($idThuocTinhHH) {
                $result = $thuocTinhHHObj->thuoctinhhhDelete($idThuocTinhHH);
                header("Location: ../../index.php?req=thuoctinhhhview&result=" . ($result ? 'ok' : 'notok'));
            } else {

                header("Location: ../../index.php?req=thuoctinhhhview&result=notok&error=missing_id");
            }
            break;

        case 'updatethuoctinhhh':

            $idThuocTinhHH = $_POST['idThuocTinhHH'] ?? null;
            $idhanghoa = $_POST['idhanghoa'] ?? null;
            $idThuocTinh = $_POST['idThuocTinh'] ?? null;
            $tenThuocTinhHH = $_POST['tenThuocTinhHH'] ?? null;
            $debug_log = $_POST['debug_log'] ?? false;

            if ($debug_log) {
                $log_file = __DIR__ . '/debug_log.txt';
                $log_data = date('Y-m-d H:i:s') . " - Update request:\n";
                $log_data .= "idThuocTinhHH: $idThuocTinhHH\n";
                $log_data .= "idhanghoa: $idhanghoa\n";
                $log_data .= "idThuocTinh: $idThuocTinh\n";
                $log_data .= "tenThuocTinhHH: $tenThuocTinhHH\n";
                $log_data .= "POST: " . print_r($_POST, true) . "\n";
                $log_data .= "GET: " . print_r($_GET, true) . "\n";
                $log_data .= "REQUEST: " . print_r($_REQUEST, true) . "\n";
                $log_data .= "--------------------------------------\n";
                file_put_contents($log_file, $log_data, FILE_APPEND);
            }

            $debug_data = array(
                'idThuocTinhHH' => $idThuocTinhHH,
                'idhanghoa' => $idhanghoa,
                'idThuocTinh' => $idThuocTinh,
                'tenThuocTinhHH' => $tenThuocTinhHH,
                'POST' => $_POST,
                'GET' => $_GET
            );
            error_log("DEBUG thuoctinhhhUpdate: " . print_r($debug_data, true));

            if ($idThuocTinhHH && $idhanghoa && $idThuocTinh && $tenThuocTinhHH) {
                try {

                    $idThuocTinhHH = (int)$idThuocTinhHH;
                    $idhanghoa = (int)$idhanghoa;
                    $idThuocTinh = (int)$idThuocTinh;

                    $result = $thuocTinhHHObj->thuoctinhhhUpdate($idhanghoa, $idThuocTinh, $tenThuocTinhHH, $idThuocTinhHH);

                    if ($debug_log) {
                        $log_data = date('Y-m-d H:i:s') . " - Update result: " . ($result !== false ? "Success" : "Failed") . "\n";
                        file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                    }

                    if ($result !== false) {

                        error_log(date('Y-m-d H:i:s') . " - INFO thuoctinhhhUpdate: Updated property ID: $idThuocTinhHH, Name: $tenThuocTinhHH");

                        if (
                            isset($_POST['ajax']) || isset($_GET['ajax']) ||
                            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                        ) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => 'Cập nhật thuộc tính hàng hóa thành công!'
                            ]);
                            exit;
                        } else {

                            echo '<!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset="UTF-8">
                                <meta http-equiv="refresh" content="2;url=../../index.php?req=thuoctinhhhview&result=ok">
                                <title>Cập nhật thành công</title>
                                <style>
                                    body {
                                        font-family: Arial, sans-serif;
                                        background: #f0f0f0;
                                        display: flex;
                                        justify-content: center;
                                        align-items: center;
                                        height: 100vh;
                                        margin: 0;
                                    }
                                    .message {
                                        background: #fff;
                                        padding: 20px;
                                        border-radius: 5px;
                                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                        text-align: center;
                                        max-width: 500px;
                                    }
                                    .success {
                                        color: #28a745;
                                        font-size: 24px;
                                        margin-bottom: 10px;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="message">
                                    <div class="success">✓ Cập nhật thành công</div>
                                    <p>Thuộc tính hàng hóa đã được cập nhật.</p>
                                    <p>Bạn sẽ được chuyển hướng trong 2 giây...</p>
                                    <p><a href="../../index.php?req=thuoctinhhhview">Nhấp vào đây nếu không được chuyển hướng tự động</a></p>
                                </div>
                            </body>
                            </html>';
                            exit;
                        }
                    } else {

                        error_log(date('Y-m-d H:i:s') . " - ERROR thuoctinhhhUpdate: Update failed for ID: $idThuocTinhHH");

                        if (
                            isset($_POST['ajax']) || isset($_GET['ajax']) ||
                            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                        ) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => false,
                                'message' => 'Cập nhật thất bại. Vui lòng thử lại sau!'
                            ]);
                            exit;
                        } else {

                            echo '<!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset="UTF-8">
                                <meta http-equiv="refresh" content="3;url=../../index.php?req=thuoctinhhhview&result=notok">
                                <title>Cập nhật thất bại</title>
                                <style>
                                    body {
                                        font-family: Arial, sans-serif;
                                        background: #f0f0f0;
                                        display: flex;
                                        justify-content: center;
                                        align-items: center;
                                        height: 100vh;
                                        margin: 0;
                                    }
                                    .message {
                                        background: #fff;
                                        padding: 20px;
                                        border-radius: 5px;
                                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                        text-align: center;
                                        max-width: 500px;
                                    }
                                    .error {
                                        color: #dc3545;
                                        font-size: 24px;
                                        margin-bottom: 10px;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="message">
                                    <div class="error">⚠ Cập nhật thất bại</div>
                                    <p>Có lỗi xảy ra khi cập nhật thuộc tính hàng hóa.</p>
                                    <p>Bạn sẽ được chuyển hướng trong 3 giây...</p>
                                    <p><a href="../../index.php?req=thuoctinhhhview">Nhấp vào đây nếu không được chuyển hướng tự động</a></p>
                                </div>
                            </body>
                            </html>';
                            exit;
                        }
                    }
                } catch (Exception $e) {

                    $error_message = $e->getMessage();
                    error_log("EXCEPTION thuoctinhhhUpdate: " . $error_message);

                    if ($debug_log) {
                        $log_data = date('Y-m-d H:i:s') . " - Exception: " . $error_message . "\n";
                        file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                    }

                    if (
                        isset($_POST['ajax']) || isset($_GET['ajax']) ||
                        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    ) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Lỗi: ' . $error_message
                        ]);
                        exit;
                    } else {
                        header("Location: ../../index.php?req=thuoctinhhhview&result=notok&error=exception&message=" . urlencode($error_message));
                        exit;
                    }
                }
            } else {

                $missing = array();
                if (!$idThuocTinhHH) $missing[] = 'idThuocTinhHH';
                if (!$idhanghoa) $missing[] = 'idhanghoa';
                if (!$idThuocTinh) $missing[] = 'idThuocTinh';
                if (!$tenThuocTinhHH) $missing[] = 'tenThuocTinhHH';

                $error_msg = "Thiếu dữ liệu: " . implode(', ', $missing);
                error_log("ERROR thuoctinhhhUpdate: $error_msg");

                if ($debug_log) {
                    $log_data = date('Y-m-d H:i:s') . " - Missing data: " . $error_msg . "\n";
                    file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);
                }

                if (
                    isset($_POST['ajax']) || isset($_GET['ajax']) ||
                    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                ) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $error_msg
                    ]);
                    exit;
                } else {
                    header("Location: ../../index.php?req=thuoctinhhhview&result=notok&error=missing_data&fields=" . implode(',', $missing));
                    exit;
                }
            }
            exit;
            break;
        default:
            header('Location: ../../index.php?req=thuoctinhhhview');
            break;
    }
} else {
    header('Location: ../../index.php?req=thuoctinhhhview');
}
