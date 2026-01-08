<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();
require_once '../mod/mphieunhapCls.php';
require_once '../mod/mchitietphieunhapCls.php';

$phieunhap = new MPhieuNhap();
$chitietphieunhap = new MChiTietPhieuNhap();

if (isset($_GET['reqact'])) {
    $reqact = $_GET['reqact'];
    
    switch ($reqact) {
        case 'addnew':

            if (isset($_POST['idPhieuNhap']) && isset($_POST['idhanghoa']) && isset($_POST['soLuong']) && isset($_POST['donGia']) && isset($_POST['giaNhap'])) {
                $idPhieuNhap = $_POST['idPhieuNhap'];
                $idhanghoa = $_POST['idhanghoa'];
                $soLuong = $_POST['soLuong'];
                $donGia = $_POST['donGia'];
                $giaNhap = $_POST['giaNhap'];
                
                $result = $chitietphieunhap->addChiTietPhieuNhap($idPhieuNhap, $idhanghoa, $soLuong, $donGia, $giaNhap);
                
                if ($result) {
                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $idPhieuNhap . "&result=success");
                } else {
                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $idPhieuNhap . "&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        case 'update':

            if (isset($_POST['idCTPN']) && isset($_POST['soLuong']) && isset($_POST['donGia']) && isset($_POST['giaNhap'])) {
                $idCTPN = $_POST['idCTPN'];
                $soLuong = $_POST['soLuong'];
                $donGia = $_POST['donGia'];
                $giaNhap = $_POST['giaNhap'];
                $idPhieuNhap = $_POST['idPhieuNhap'];
                
                $result = $chitietphieunhap->updateChiTietPhieuNhap($idCTPN, $soLuong, $donGia, $giaNhap);
                
                if ($result) {
                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $idPhieuNhap . "&result=success");
                } else {
                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $idPhieuNhap . "&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mphieunhap&result=fail");
            }
            break;
            
        case 'delete':

            if (isset($_GET['idct']) && isset($_GET['idpn'])) {
                $idCTPN = $_GET['idct'];
                $idPhieuNhap = $_GET['idpn'];
                
                $result = $chitietphieunhap->deleteChiTietPhieuNhap($idCTPN);
                
                if ($result) {
                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $idPhieuNhap . "&result=success");
                } else {
                    header("Location: ../../index.php?req=mchitietphieunhap&idpn=" . $idPhieuNhap . "&result=fail");
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
