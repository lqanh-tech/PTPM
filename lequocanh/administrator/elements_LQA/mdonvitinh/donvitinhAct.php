<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed');
}

require '../../elements_LQA/mod/donvitinhCls.php';
require_once '../../elements_LQA/mod/database.php';

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

            $tenDonViTinh = sanitizeInput($_REQUEST['tenDonViTinh'] ?? '', 'text');
            $moTa = sanitizeInput($_REQUEST['moTa'] ?? '', 'text');
            $ghiChu = sanitizeInput($_REQUEST['ghiChu'] ?? '', 'text');

            $lh = new DonViTinh();
            $kq = $lh->donvitinhAdd($tenDonViTinh, $moTa, $ghiChu);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Thêm đơn vị tính thành công' : 'Thêm đơn vị tính thất bại');
            } else {

                header('location: ../../index.php?req=donvitinhview&result=' . ($kq ? 'ok' : 'notok'));
            }
            break;

        case 'deletedonvitinh':
            $iddonvitinh = sanitizeInput($_REQUEST['iddonvitinh'] ?? '', 'int');
            $lh = new DonViTinh();
            $kq = $lh->donvitinhDelete($iddonvitinh);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Xóa đơn vị tính thành công' : 'Xóa đơn vị tính thất bại');
            } else {

                header('location: ../../index.php?req=donvitinhview&result=' . ($kq ? 'ok' : 'notok'));
            }
            break;

        case 'updatedonvitinh':
            $idDonViTinh = sanitizeInput($_REQUEST['idDonViTinh'] ?? '', 'int');
            $tenDonViTinh = sanitizeInput($_REQUEST['tenDonViTinh'] ?? '', 'text');
            $moTa = sanitizeInput($_REQUEST['moTa'] ?? '', 'text');
            $ghiChu = sanitizeInput($_REQUEST['ghiChu'] ?? '', 'text');

            if ($idDonViTinh) {
                $debugInfo = [
                    'idDonViTinh' => $idDonViTinh,
                    'tenDonViTinh' => $tenDonViTinh,
                    'moTa' => $moTa,
                    'ghiChu' => $ghiChu
                ];

                try {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT idDonViTinh, tenDonViTinh, mota FROM donvitinh WHERE idDonViTinh = ? LIMIT 1");
                    $stmt->execute([$idDonViTinh]);
                    $beforeUpdate = $stmt->fetch(PDO::FETCH_ASSOC);
                    $debugInfo['before_update'] = $beforeUpdate;

                    $tableInfo = $db->query("DESCRIBE donvitinh")->fetchAll(PDO::FETCH_ASSOC);
                    $debugInfo['table_structure'] = $tableInfo;
                } catch (Exception $e) {
                    $debugInfo['db_error'] = $e->getMessage();
                }

                $lh = new DonViTinh();
                $kq = $lh->donvitinhUpdate($tenDonViTinh, $moTa, $ghiChu, $idDonViTinh);
                $debugInfo['update_result'] = $kq;

                try {
                    $stmt = $db->prepare("SELECT idDonViTinh, tenDonViTinh, mota FROM donvitinh WHERE idDonViTinh = ? LIMIT 1");
                    $stmt->execute([$idDonViTinh]);
                    $afterUpdate = $stmt->fetch(PDO::FETCH_ASSOC);
                    $debugInfo['after_update'] = $afterUpdate;
                } catch (Exception $e) {
                    $debugInfo['after_update_error'] = $e->getMessage();
                }

                sendJsonResponse(true, 'Cập nhật đơn vị tính thành công', $debugInfo);
            } else {
                sendJsonResponse(false, 'Không tìm thấy ID đơn vị tính');
            }
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
