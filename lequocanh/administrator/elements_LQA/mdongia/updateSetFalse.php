<?php
session_start();
require_once __DIR__ . '/../mod/dongiaCls.php';

function redirectWithMessage($success, $message = '')
{
    $_SESSION['dongia_message'] = $message;
    $_SESSION['dongia_success'] = $success;
    header('location: ../../index.php?req=dongiaview');
    exit;
}

if (isset($_REQUEST['idDonGia'])) {
    $idDonGia = $_REQUEST['idDonGia'];
    $apDung = ($_REQUEST['apDung'] === 'true');

    $dongiaObj = new Dongia();
    $dongia = $dongiaObj->DongiaGetbyId($idDonGia);

    if (!$dongia) {
        redirectWithMessage(false, 'Không tìm thấy đơn giá');
    }

    if ($apDung) {

        $dongiaObj->DongiaSetAllToFalse($dongia->idHangHoa);

        $kq = $dongiaObj->DongiaUpdateStatus($idDonGia, true);

        if ($kq) {

            $dongiaObj->HanghoaUpdatePrice($dongia->idHangHoa, $dongia->giaBan);
            redirectWithMessage(true, 'Đã chuyển đơn giá thành đang áp dụng và cập nhật giá sản phẩm');
        } else {
            redirectWithMessage(false, 'Không thể áp dụng đơn giá này');
        }
    } else {

        $kq = $dongiaObj->DongiaUpdateStatus($idDonGia, false);

        if ($kq) {

            $dongiaObj->UpdateLatestPriceForProduct($dongia->idHangHoa);
            redirectWithMessage(true, 'Đã ngừng áp dụng đơn giá này và cập nhật giá mới nhất');
        } else {
            redirectWithMessage(false, 'Không thể ngừng áp dụng đơn giá này');
        }
    }
} else {
    redirectWithMessage(false, 'Không có thông tin đơn giá');
}
?>