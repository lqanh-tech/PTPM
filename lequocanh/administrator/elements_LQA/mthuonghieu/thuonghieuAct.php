<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed');
}

require '../../elements_LQA/mod/thuonghieuCls.php';

function sendJsonResponse($success, $message = '', $debug = null)
{

    if (ob_get_contents()) ob_clean();

    header('Content-Type: application/json');
    header("Cache-Control: no-cache, must-revalidate");

    $response = ['success' => $success, 'message' => $message];
    if ($debug !== null) {
        $response['debug'] = $debug;
    }
    echo json_encode($response);
    exit;
}

if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    switch ($requestAction) {
        case 'addnew':

            $tenTH = sanitizeInput($_REQUEST['tenTH'] ?? '', 'text');
            $SDT = sanitizeInput($_REQUEST['SDT'] ?? '', 'phone');
            $email = sanitizeInput($_REQUEST['email'] ?? '', 'email');
            $diaChi = sanitizeInput($_REQUEST['diaChi'] ?? '', 'text');

            if (empty($_FILES['fileimage']['tmp_name'])) {
                sendJsonResponse(false, 'Vui lòng nhập ảnh trước khi thêm thương hiệu.');
                exit;
            }

            $hinhanh_file = $_FILES['fileimage']['tmp_name'];
            $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));

            $lh = new ThuongHieu();
            $kq = $lh->thuonghieuAdd($tenTH, $SDT, $email, $diaChi, $hinhanh);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Thêm thương hiệu thành công' : 'Thêm thương hiệu thất bại');
            } else {

                header('location: ../../index.php?req=thuonghieuview&result=' . ($kq ? 'ok' : 'notok'));
                exit;
            }
            break;

        case 'deletethuonghieu':
            $idThuongHieu = sanitizeInput($_REQUEST['idThuongHieu'] ?? '', 'int');
            $lh = new ThuongHieu();
            $kq = $lh->thuonghieuDelete($idThuongHieu);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Xóa thương hiệu thành công' : 'Xóa thương hiệu thất bại');
            } else {

                header('location: ../../index.php?req=thuonghieuview&result=' . ($kq ? 'ok' : 'notok'));
                exit;
            }
            break;

        case 'updatethuonghieu':
            $tenTH = sanitizeInput($_REQUEST['tenTH'] ?? '', 'text');
            $SDT = sanitizeInput($_REQUEST['SDT'] ?? '', 'phone');
            $email = sanitizeInput($_REQUEST['email'] ?? '', 'email');
            $diaChi = sanitizeInput($_REQUEST['diaChi'] ?? '', 'text');
            $idThuongHieu = sanitizeInput($_REQUEST['idThuongHieu'] ?? '', 'int');

            if (isset($_FILES['fileimage']) && !empty($_FILES['fileimage']['tmp_name'])) {
                $hinhanh_file = $_FILES['fileimage']['tmp_name'];
                $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));
            } else {

                $hinhanh = isset($_REQUEST['hinhanh']) ? $_REQUEST['hinhanh'] : '';
            }

            $debugInfo = [
                'idThuongHieu' => $idThuongHieu,
                'tenTH' => $tenTH,
                'SDT' => $SDT,
                'email' => $email,
                'diaChi' => $diaChi,
                'hinhanh_length' => strlen($hinhanh)
            ];

            $lh = new ThuongHieu();
            $kq = $lh->thuonghieuUpdate($tenTH, $SDT, $email, $diaChi, $hinhanh, $idThuongHieu);
            $debugInfo['update_result'] = $kq;

            sendJsonResponse(true, 'Cập nhật thương hiệu thành công', $debugInfo);
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
