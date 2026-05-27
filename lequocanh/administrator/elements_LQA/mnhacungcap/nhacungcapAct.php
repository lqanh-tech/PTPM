<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed');
}

require '../../elements_LQA/mod/nhacungcapCls.php';

function sendJsonResponse($success, $message = '')
{

    if (ob_get_contents()) ob_clean();

    header('Content-Type: application/json');
    header("Cache-Control: no-cache, must-revalidate");

    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    switch ($requestAction) {
        case 'addnew':
            $tenNCC = sanitizeInput($_REQUEST['tenNCC'] ?? '', 'text');
            $nguoiLienHe = sanitizeInput($_REQUEST['nguoiLienHe'] ?? '', 'text');
            $soDienThoai = sanitizeInput($_REQUEST['soDienThoai'] ?? '', 'phone');
            $email = sanitizeInput($_REQUEST['email'] ?? '', 'email');
            $diaChi = sanitizeInput($_REQUEST['diaChi'] ?? '', 'text');
            $maSoThue = sanitizeInput($_REQUEST['maSoThue'] ?? '', 'text');
            $ghiChu = sanitizeInput($_REQUEST['ghiChu'] ?? '', 'text');

            if (empty($tenNCC)) {
                sendJsonResponse(false, 'Tên nhà cung cấp không được để trống');
            }

            $ncc = new nhacungcap();
            $kq = $ncc->NhacungcapAdd($tenNCC, $nguoiLienHe, $soDienThoai, $email, $diaChi, $maSoThue, $ghiChu);
            if ($kq) {

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    sendJsonResponse(true, 'Thêm nhà cung cấp thành công');
                } else {

                    header("location:../../index.php?req=nhacungcapview&result=ok");
                }
            } else {
                sendJsonResponse(false, 'Thêm nhà cung cấp thất bại');
            }
            break;

        case 'deletenhacungcap':
            $idNCC = sanitizeInput($_REQUEST['idNCC'] ?? '', 'int');
            $ncc = new nhacungcap();
            $kq = $ncc->NhacungcapDelete($idNCC);
            if ($kq) {

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    sendJsonResponse(true, 'Xóa nhà cung cấp thành công');
                } else {

                    header("location:../../index.php?req=nhacungcapview&result=ok");
                }
            } else {
                sendJsonResponse(false, 'Xóa nhà cung cấp thất bại');
            }
            break;

        case 'updatenhacungcap':
            $idNCC = sanitizeInput($_REQUEST['idNCC'] ?? '', 'int');
            $tenNCC = sanitizeInput($_REQUEST['tenNCC'] ?? '', 'text');
            $nguoiLienHe = sanitizeInput($_REQUEST['nguoiLienHe'] ?? '', 'text');
            $soDienThoai = sanitizeInput($_REQUEST['soDienThoai'] ?? '', 'phone');
            $email = sanitizeInput($_REQUEST['email'] ?? '', 'email');
            $diaChi = sanitizeInput($_REQUEST['diaChi'] ?? '', 'text');
            $maSoThue = sanitizeInput($_REQUEST['maSoThue'] ?? '', 'text');
            $ghiChu = sanitizeInput($_REQUEST['ghiChu'] ?? '', 'text');
            $trangThai = sanitizeInput($_REQUEST['trangThai'] ?? '1', 'int');

            if (empty($idNCC)) {
                sendJsonResponse(false, 'ID nhà cung cấp không được để trống');
            }

            if (empty($tenNCC)) {
                sendJsonResponse(false, 'Tên nhà cung cấp không được để trống');
            }

            $ncc = new nhacungcap();
            $kq = $ncc->NhacungcapUpdate($tenNCC, $nguoiLienHe, $soDienThoai, $email, $diaChi, $maSoThue, $ghiChu, $trangThai, $idNCC);

            if ($kq) {
                sendJsonResponse(true, 'Cập nhật nhà cung cấp thành công');
            } else {
                sendJsonResponse(false, 'Cập nhật nhà cung cấp thất bại');
            }
            break;

        case 'updatestatus':
            $idNCC = isset($_REQUEST['idNCC']) ? $_REQUEST['idNCC'] : '';
            $trangThai = isset($_REQUEST['trangThai']) ? $_REQUEST['trangThai'] : 1;

            if (empty($idNCC)) {
                sendJsonResponse(false, 'ID nhà cung cấp không được để trống');
            }

            $ncc = new nhacungcap();
            $kq = $ncc->UpdateStatus($idNCC, $trangThai);

            if ($kq) {
                sendJsonResponse(true, 'Cập nhật trạng thái nhà cung cấp thành công');
            } else {
                sendJsonResponse(false, 'Cập nhật trạng thái nhà cung cấp thất bại');
            }
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
