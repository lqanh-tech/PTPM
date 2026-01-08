<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
require '../../elements_LQA/mod/nhanvienCls.php';
require '../../elements_LQA/mod/userRoleCls.php';
require '../../elements_LQA/mod/phanHeQuanLyCls.php';

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
            $tenNV = isset($_REQUEST['tenNV']) ? $_REQUEST['tenNV'] : null;
            $SDT = isset($_REQUEST['SDT']) ? $_REQUEST['SDT'] : null;
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;
            $luongCB = isset($_REQUEST['luongCB']) ? $_REQUEST['luongCB'] : null;
            $phuCap = isset($_REQUEST['phuCap']) ? $_REQUEST['phuCap'] : null;
            $chucVu = isset($_REQUEST['chucVu']) ? $_REQUEST['chucVu'] : null;
            $iduser = isset($_REQUEST['iduser']) ? $_REQUEST['iduser'] : null;
            $phanHeList = isset($_REQUEST['phanHe']) ? $_REQUEST['phanHe'] : [];

            $nv = new NhanVien();

            $isUserAlreadyAssigned = false;

            if (!empty($iduser)) {
                $allEmployees = $nv->nhanvienGetAll();
                foreach ($allEmployees as $emp) {
                    if ($emp->iduser == $iduser) {
                        $isUserAlreadyAssigned = true;
                        break;
                    }
                }
            }

            $kq = $nv->nhanvienAdd($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $iduser);

            $idNhanVien = $nv->getLastInsertId();

            if ($kq && $idNhanVien && !empty($phanHeList)) {
                $phanHeObj = new PhanHeQuanLy();

                foreach ($phanHeList as $idPhanHe) {
                    $phanHeObj->assignPhanHeToNhanVien($idNhanVien, $idPhanHe);
                }
            }

            if ($kq && !empty($iduser)) {
                $userRole = new UserRole();
                $userRole->assignStaffRole($iduser);
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                if ($isUserAlreadyAssigned) {
                    sendJsonResponse($kq, $kq ? 'Thêm nhân viên thành công. Lưu ý: Người dùng này đã được gán cho một nhân viên khác.' : 'Thêm nhân viên thất bại');
                } else {
                    sendJsonResponse($kq, $kq ? 'Thêm nhân viên thành công' : 'Thêm nhân viên thất bại');
                }
            } else {

                $notice = $isUserAlreadyAssigned ? "&notice=duplicate_user" : "";
                header("location:../../index.php?req=nhanvienview&result=" . ($kq ? "ok" : "notok") . $notice);
            }
            break;

        case 'deletenhanvien':
            $idNhanVien = isset($_REQUEST['idNhanVien']) ? $_REQUEST['idNhanVien'] : null;
            if ($idNhanVien) {
                $nv = new NhanVien();

                $staffInfo = $nv->nhanvienGetbyId($idNhanVien);
                $iduser = $staffInfo ? $staffInfo->iduser : null;

                $kq = $nv->nhanvienDelete($idNhanVien);

                if ($kq && !empty($iduser)) {
                    $userRole = new UserRole();

                    $allStaff = $nv->nhanvienGetAll();
                    $stillStaff = false;
                    foreach ($allStaff as $staff) {
                        if ($staff->iduser == $iduser) {
                            $stillStaff = true;
                            break;
                        }
                    }

                    if (!$stillStaff) {
                        $userRole->assignDefaultRole($iduser, 'customer');
                    }
                }

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    sendJsonResponse($kq, $kq ? 'Xóa nhân viên thành công' : 'Xóa nhân viên thất bại');
                } else {

                    header("location:../../index.php?req=nhanvienview&result=" . ($kq ? "ok" : "notok"));
                }
            } else {
                sendJsonResponse(false, 'Không tìm thấy ID nhân viên');
            }
            break;

        case 'updatenhanvien':
            $idNhanVien = isset($_REQUEST['idNhanVien']) ? $_REQUEST['idNhanVien'] : null;
            $tenNV = isset($_REQUEST['tenNV']) ? $_REQUEST['tenNV'] : null;
            $SDT = isset($_REQUEST['SDT']) ? $_REQUEST['SDT'] : null;
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;
            $luongCB = isset($_REQUEST['luongCB']) ? $_REQUEST['luongCB'] : 0;
            $phuCap = isset($_REQUEST['phuCap']) ? $_REQUEST['phuCap'] : 0;
            $chucVu = isset($_REQUEST['chucVu']) ? $_REQUEST['chucVu'] : null;
            $iduser = isset($_REQUEST['iduser']) && $_REQUEST['iduser'] !== '' ? $_REQUEST['iduser'] : null;
            $phanHeList = isset($_REQUEST['phanHe']) ? $_REQUEST['phanHe'] : [];

            if ($idNhanVien) {
                $nv = new NhanVien();
                $kq = $nv->nhanvienUpdate($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $idNhanVien, $iduser);

                if ($kq) {
                    $phanHeObj = new PhanHeQuanLy();

                    $phanHeObj->removeAllPhanHeFromNhanVien($idNhanVien);

                    if (!empty($phanHeList)) {
                        foreach ($phanHeList as $idPhanHe) {
                            $phanHeObj->assignPhanHeToNhanVien($idNhanVien, $idPhanHe);
                        }
                    }
                }

                if ($kq && !empty($iduser)) {
                    $userRole = new UserRole();
                    $userRole->assignStaffRole($iduser);
                }

                sendJsonResponse(true, 'Cập nhật nhân viên thành công');
            } else {
                sendJsonResponse(false, 'Không tìm thấy ID nhân viên');
            }
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
