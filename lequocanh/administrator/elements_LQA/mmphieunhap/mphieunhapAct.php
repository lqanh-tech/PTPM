<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    die('CSRF token validation failed');
}

require_once '../mod/mphieunhapCls.php';
require_once '../mod/mchitietphieunhapCls.php';

$phieunhap = new MPhieuNhap();
$chitietphieunhap = new MChiTietPhieuNhap();

if (isset($_GET['reqact'])) {
    $reqact = $_GET['reqact'];
    
    switch ($reqact) {
        case 'addnew':

            if (isset($_POST['maPhieuNhap']) && isset($_POST['idNCC']) && isset($_POST['idNhanVien'])) {
                $maPhieuNhap = sanitizeInput($_POST['maPhieuNhap'] ?? '', 'text');
                $idNCC = sanitizeInput($_POST['idNCC'] ?? '', 'int');
                $idNhanVien = sanitizeInput($_POST['idNhanVien'] ?? '', 'int');
                $ghiChu = sanitizeInput($_POST['ghiChu'] ?? '', 'text');
                
                $result = $phieunhap->addPhieuNhap($maPhieuNhap, $idNhanVien, $idNCC, $ghiChu);
                
                if ($result) {

                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $result);
                } else {
                    header("Location: ../../index.php?req=mphieunhap&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        case 'update':

            if (isset($_POST['idPhieuNhap']) && isset($_POST['maPhieuNhap']) && isset($_POST['idNCC']) && isset($_POST['idNhanVien'])) {
                $idPhieuNhap = sanitizeInput($_POST['idPhieuNhap'] ?? '', 'int');
                $maPhieuNhap = sanitizeInput($_POST['maPhieuNhap'] ?? '', 'text');
                $idNCC = sanitizeInput($_POST['idNCC'] ?? '', 'int');
                $idNhanVien = sanitizeInput($_POST['idNhanVien'] ?? '', 'int');
                $ghiChu = sanitizeInput($_POST['ghiChu'] ?? '', 'text');
                
                $result = $phieunhap->updatePhieuNhap($idPhieuNhap, $maPhieuNhap, $idNhanVien, $idNCC, $ghiChu);
                
                if ($result) {
                    header("Location: ../../index.php?req=mphieunhap&result=success");
                } else {
                    header("Location: ../../index.php?req=mphieunhap&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        case 'delete':

            if (isset($_GET['idpn'])) {
                $idPhieuNhap = $_GET['idpn'];
                $result = $phieunhap->deletePhieuNhap($idPhieuNhap);
                
                if ($result) {
                    header("Location: ../../index.php?req=mphieunhap&result=success");
                } else {
                    header("Location: ../../index.php?req=mphieunhap&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        case 'approve':

            if (isset($_GET['idpn'])) {
                $idPhieuNhap = $_GET['idpn'];
                $result = $phieunhap->approvePhieuNhap($idPhieuNhap);
                
                if ($result) {
                    header("Location: ../../index.php?req=mphieunhap&result=success");
                } else {
                    header("Location: ../../index.php?req=mphieunhap&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        case 'cancel':

            if (isset($_GET['idpn'])) {
                $idPhieuNhap = $_GET['idpn'];
                $result = $phieunhap->cancelPhieuNhap($idPhieuNhap);
                
                if ($result) {
                    header("Location: ../../index.php?req=mphieunhap&result=success");
                } else {
                    header("Location: ../../index.php?req=mphieunhap&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        default:
            header("Location: ../../index.php?req=mphieunhap");
            break;
    }
} else {
    header("Location: ../../index.php?req=mphieunhap");
}
?>
