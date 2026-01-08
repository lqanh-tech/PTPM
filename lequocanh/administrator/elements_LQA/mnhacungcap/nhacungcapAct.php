<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
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
            $tenNCC = isset($_REQUEST['tenNCC']) ? $_REQUEST['tenNCC'] : '';
            $nguoiLienHe = isset($_REQUEST['nguoiLienHe']) ? $_REQUEST['nguoiLienHe'] : '';
            $soDienThoai = isset($_REQUEST['soDienThoai']) ? $_REQUEST['soDienThoai'] : '';
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
            $diaChi = isset($_REQUEST['diaChi']) ? $_REQUEST['diaChi'] : '';
            $maSoThue = isset($_REQUEST['maSoThue']) ? $_REQUEST['maSoThue'] : '';
            $ghiChu = isset($_REQUEST['ghiChu']) ? $_REQUEST['ghiChu'] : '';

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
            $idNCC = $_REQUEST['idNCC'];
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
            $idNCC = isset($_REQUEST['idNCC']) ? $_REQUEST['idNCC'] : '';
            $tenNCC = isset($_REQUEST['tenNCC']) ? $_REQUEST['tenNCC'] : '';
            $nguoiLienHe = isset($_REQUEST['nguoiLienHe']) ? $_REQUEST['nguoiLienHe'] : '';
            $soDienThoai = isset($_REQUEST['soDienThoai']) ? $_REQUEST['soDienThoai'] : '';
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
            $diaChi = isset($_REQUEST['diaChi']) ? $_REQUEST['diaChi'] : '';
            $maSoThue = isset($_REQUEST['maSoThue']) ? $_REQUEST['maSoThue'] : '';
            $ghiChu = isset($_REQUEST['ghiChu']) ? $_REQUEST['ghiChu'] : '';
            $trangThai = isset($_REQUEST['trangThai']) ? $_REQUEST['trangThai'] : 1;

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
