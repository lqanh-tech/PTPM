<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed. Vui lòng tải lại trang và thử lại.');
}

require '../../elements_LQA/mod/loaihangCls.php';

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
            $tenloaihang = sanitizeInput($_REQUEST['tenloaihang'] ?? '', 'text');
            $mota = sanitizeInput($_REQUEST['mota'] ?? '', 'text');

            if (empty($_FILES['fileimage']['tmp_name'])) {
                sendJsonResponse(false, 'Vui lòng nhập ảnh trước khi thêm loại hàng.');
            }

            $hinhanh_file = $_FILES['fileimage']['tmp_name'];
            $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));

            $lh = new loaihang();
            $kq = $lh->LoaihangAdd($tenloaihang, $hinhanh, $mota);
            if ($kq) {

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    sendJsonResponse(true, 'Thêm loại hàng thành công');
                } else {

                    header("location:../../index.php?req=loaihangview");
                }
            } else {
                sendJsonResponse(false, 'Thêm loại hàng thất bại');
            }
            break;

        case 'deleteloaihang':
            try {
                $idloaihang = sanitizeInput($_REQUEST['idloaihang'] ?? '', 'int');
                $lh = new loaihang();
                $kq = $lh->LoaihangDelete($idloaihang);
                
                if ($kq > 0) {

                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        sendJsonResponse(true, 'Xóa loại hàng thành công');
                    } else {

                        header("location:../../index.php?req=loaihangview&success=delete");
                    }
                } else {
                    sendJsonResponse(false, 'Không tìm thấy loại hàng để xóa');
                }
            } catch (Exception $e) {

                $errorMessage = $e->getMessage();
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    sendJsonResponse(false, $errorMessage);
                } else {

                    header("location:../../index.php?req=loaihangview&error=" . urlencode($errorMessage));
                }
            }
            break;

        case 'updateloaihang':
            $idloaihang = sanitizeInput($_REQUEST['idloaihang'] ?? '', 'int');
            $tenloaihang = sanitizeInput($_REQUEST['tenloaihang'] ?? '', 'text');
            $mota = sanitizeInput($_REQUEST['mota'] ?? '', 'text');

            if (isset($_FILES['fileimage']) && $_FILES['fileimage']['error'] == 0) {
                $hinhanh_file = $_FILES['fileimage']['tmp_name'];
                $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));
            } else {
                $hinhanh = $_REQUEST['hinhanh'];
            }

            $lh = new loaihang();
            $kq = $lh->LoaihangUpdate($tenloaihang, $hinhanh, $mota, $idloaihang);
            
            sendJsonResponse(true, 'Cập nhật loại hàng thành công');
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
