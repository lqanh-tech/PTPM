<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
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

            $tenTH = isset($_REQUEST['tenTH']) ? $_REQUEST['tenTH'] : null;
            $SDT = isset($_REQUEST['SDT']) ? $_REQUEST['SDT'] : null;
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;
            $diaChi = isset($_REQUEST['diaChi']) ? $_REQUEST['diaChi'] : null;

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
            $idThuongHieu = isset($_REQUEST['idThuongHieu']) ? $_REQUEST['idThuongHieu'] : null;
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
            $tenTH = isset($_REQUEST['tenTH']) ? $_REQUEST['tenTH'] : null;
            $SDT = isset($_REQUEST['SDT']) ? $_REQUEST['SDT'] : null;
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;
            $diaChi = isset($_REQUEST['diaChi']) ? $_REQUEST['diaChi'] : null;
            $idThuongHieu = isset($_REQUEST['idThuongHieu']) ? $_REQUEST['idThuongHieu'] : null;

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
