<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();

$dongiaPaths = [
    '../../elements_LQA/mod/dongiaCls.php',
    '../mod/dongiaCls.php',
    './elements_LQA/mod/dongiaCls.php',
    './administrator/elements_LQA/mod/dongiaCls.php',
    __DIR__ . '/../mod/dongiaCls.php'
];

$foundDongia = false;
foreach ($dongiaPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundDongia = true;
        if (class_exists('Logger')) {
            Logger::debug("Successfully loaded dongiaCls.php", ['path' => $path]);
        }
        break;
    }
}

if (!$foundDongia) {
    error_log("DongiaAct: Không thể tìm thấy file dongiaCls.php");
    die("Không thể tải file dongiaCls.php");
}

function sendJsonResponse($success, $message = '')
{
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

function redirectWithMessage($success, $message = '')
{
    $_SESSION['dongia_message'] = $message;
    $_SESSION['dongia_success'] = $success;
    header('location: ../../index.php?req=dongiaview');
    exit;
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    switch ($requestAction) {
        case 'addnew':

            error_log("DongiaAct addnew: Starting process");
            error_log("DongiaAct addnew: POST data: " . print_r($_POST, true));
            error_log("DongiaAct addnew: GET data: " . print_r($_GET, true));
            
            $idHangHoa = isset($_REQUEST['idhanghoa']) ? trim($_REQUEST['idhanghoa']) : '';
            $giaBan = isset($_REQUEST['giaban']) ? trim($_REQUEST['giaban']) : '';
            $ngayApDung = isset($_REQUEST['ngayapdung']) ? trim($_REQUEST['ngayapdung']) : '';
            $ngayKetThuc = isset($_REQUEST['ngayketthuc']) ? trim($_REQUEST['ngayketthuc']) : '';
            $dieuKien = isset($_REQUEST['dieukien']) ? trim($_REQUEST['dieukien']) : '';
            $ghiChu = isset($_REQUEST['ghichu']) ? trim($_REQUEST['ghichu']) : '';

            error_log("DongiaAct addnew: Parsed data - idHangHoa: '$idHangHoa', giaBan: '$giaBan', ngayApDung: '$ngayApDung', ngayKetThuc: '$ngayKetThuc'");

            if (empty($idHangHoa) || empty($giaBan) || empty($ngayApDung) || empty($ngayKetThuc)) {
                error_log("DongiaAct addnew: Validation failed - missing required fields");
                if ($isAjax) {
                    sendJsonResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                } else {
                    redirectWithMessage(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }
                return;
            }

            if (!is_numeric($giaBan) || floatval($giaBan) <= 0) {
                error_log("DongiaAct addnew: Validation failed - invalid price: '$giaBan'");
                if ($isAjax) {
                    sendJsonResponse(false, 'Giá bán phải là số dương');
                } else {
                    redirectWithMessage(false, 'Giá bán phải là số dương');
                }
                return;
            }

            if (!DateTime::createFromFormat('Y-m-d', $ngayApDung) || !DateTime::createFromFormat('Y-m-d', $ngayKetThuc)) {
                error_log("DongiaAct addnew: Validation failed - invalid date format");
                if ($isAjax) {
                    sendJsonResponse(false, 'Định dạng ngày không hợp lệ');
                } else {
                    redirectWithMessage(false, 'Định dạng ngày không hợp lệ');
                }
                return;
            }

            if (strtotime($ngayApDung) >= strtotime($ngayKetThuc)) {
                error_log("DongiaAct addnew: Validation failed - invalid date range");
                if ($isAjax) {
                    sendJsonResponse(false, 'Ngày áp dụng phải trước ngày kết thúc');
                } else {
                    redirectWithMessage(false, 'Ngày áp dụng phải trước ngày kết thúc');
                }
                return;
            }

            error_log("DongiaAct addnew: Creating Dongia instance");
            try {
                $dg = new Dongia();
                error_log("DongiaAct addnew: Dongia instance created successfully");

                $kq = $dg->DongiaAdd($idHangHoa, floatval($giaBan), $ngayApDung, $ngayKetThuc, $dieuKien, $ghiChu);
                error_log("DongiaAct addnew: DongiaAdd result: " . ($kq ? "success (ID: $kq)" : "failed"));

                if ($kq) {
                    if ($isAjax) {
                        sendJsonResponse(true, 'Thêm đơn giá thành công');
                    } else {
                        redirectWithMessage(true, 'Thêm đơn giá thành công');
                    }
                } else {
                    if ($isAjax) {
                        sendJsonResponse(false, 'Thêm đơn giá thất bại - Vui lòng kiểm tra lại thông tin');
                    } else {
                        redirectWithMessage(false, 'Thêm đơn giá thất bại - Vui lòng kiểm tra lại thông tin');
                    }
                }
            } catch (Exception $e) {
                error_log("DongiaAct addnew: Exception occurred: " . $e->getMessage());
                error_log("DongiaAct addnew: Stack trace: " . $e->getTraceAsString());
                if ($isAjax) {
                    sendJsonResponse(false, 'Lỗi hệ thống: ' . $e->getMessage());
                } else {
                    redirectWithMessage(false, 'Lỗi hệ thống: ' . $e->getMessage());
                }
            }
            break;

        case 'deletedongia':
            $idDonGia = isset($_REQUEST['idDonGia']) ? $_REQUEST['idDonGia'] : '';

            if (empty($idDonGia)) {
                if ($isAjax) {
                    sendJsonResponse(false, 'ID đơn giá không hợp lệ');
                } else {
                    redirectWithMessage(false, 'ID đơn giá không hợp lệ');
                }
            }

            $dg = new Dongia();
            $kq = $dg->DongiaDelete($idDonGia);

            if ($kq) {
                if ($isAjax) {
                    sendJsonResponse(true, 'Xóa đơn giá thành công');
                } else {
                    redirectWithMessage(true, 'Xóa đơn giá thành công');
                }
            } else {
                if ($isAjax) {
                    sendJsonResponse(false, 'Xóa đơn giá thất bại');
                } else {
                    redirectWithMessage(false, 'Xóa đơn giá thất bại');
                }
            }
            break;

        case 'updatedongia':
            $idDonGia = isset($_REQUEST['idDonGia']) ? $_REQUEST['idDonGia'] : '';
            $idHangHoa = isset($_REQUEST['idhanghoa']) ? $_REQUEST['idhanghoa'] : '';
            $giaBan = isset($_REQUEST['giaban']) ? $_REQUEST['giaban'] : 0;
            $ngayApDung = isset($_REQUEST['ngayapdung']) ? $_REQUEST['ngayapdung'] : '';
            $ngayKetThuc = isset($_REQUEST['ngayketthuc']) ? $_REQUEST['ngayketthuc'] : '';
            $dieuKien = isset($_REQUEST['dieukien']) ? $_REQUEST['dieukien'] : '';
            $ghiChu = isset($_REQUEST['ghichu']) ? $_REQUEST['ghichu'] : '';

            if (empty($idDonGia) || empty($idHangHoa) || empty($giaBan) || empty($ngayApDung) || empty($ngayKetThuc)) {
                if ($isAjax) {
                    sendJsonResponse(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                } else {
                    redirectWithMessage(false, 'Vui lòng điền đầy đủ thông tin bắt buộc');
                }
            }

            $dg = new Dongia();
            $kq = $dg->DongiaUpdate($idDonGia, $idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien, $ghiChu);

            if ($kq) {
                if ($isAjax) {
                    sendJsonResponse(true, 'Cập nhật đơn giá thành công');
                } else {
                    redirectWithMessage(true, 'Cập nhật đơn giá thành công');
                }
            } else {
                if ($isAjax) {
                    sendJsonResponse(false, 'Cập nhật đơn giá thất bại');
                } else {
                    redirectWithMessage(false, 'Cập nhật đơn giá thất bại');
                }
            }
            break;

        default:
            if ($isAjax) {
                sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            } else {
                redirectWithMessage(false, 'Yêu cầu không hợp lệ');
            }
            break;
    }
} else {
    if ($isAjax) {
        sendJsonResponse(false, 'Yêu cầu không hợp lệ');
    } else {
        redirectWithMessage(false, 'Yêu cầu không hợp lệ');
    }
}
