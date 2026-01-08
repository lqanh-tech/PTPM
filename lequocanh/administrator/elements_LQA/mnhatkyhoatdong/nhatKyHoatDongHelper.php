<?php

$possible_paths = array(
    dirname(__FILE__) . '/../mod/nhatKyHoatDongCls.php',
    dirname(dirname(dirname(__FILE__))) . '/elements_LQA/mod/nhatKyHoatDongCls.php',
    dirname(dirname(dirname(dirname(__FILE__)))) . '/administrator/elements_LQA/mod/nhatKyHoatDongCls.php'
);

$nhatKyFile = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $nhatKyFile = $path;
        break;
    }
}

if ($nhatKyFile === null) {

    error_log("Không thể tìm thấy file nhatKyHoatDongCls.php");

    function ghiNhatKyHoatDong($username, $hanhDong, $doiTuong, $doiTuongId = null, $chiTiet = '')
    {
        error_log("Không thể ghi nhật ký hoạt động: $username, $hanhDong, $doiTuong, $doiTuongId, $chiTiet");
        return false;
    }
} else {
    require_once $nhatKyFile;

    function ghiNhatKyHoatDong($username, $hanhDong, $doiTuong, $doiTuongId = null, $chiTiet = '')
    {

        $nhatKyObj = new NhatKyHoatDong();

        return $nhatKyObj->ghiNhatKy($username, $hanhDong, $doiTuong, $doiTuongId, $chiTiet);
    }
}

function ghiNhatKyDangNhap($username)
{
    return ghiNhatKyHoatDong($username, 'Đăng nhập', 'Hệ thống', null, 'Đăng nhập vào hệ thống');
}

function ghiNhatKyDangXuat($username)
{
    return ghiNhatKyHoatDong($username, 'Đăng xuất', 'Hệ thống', null, 'Đăng xuất khỏi hệ thống');
}

function ghiNhatKyThemMoi($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'Thêm mới', $doiTuong, $doiTuongId, $chiTiet);
}

function ghiNhatKyCapNhat($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'Cập nhật', $doiTuong, $doiTuongId, $chiTiet);
}

function ghiNhatKyXoa($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'Xóa', $doiTuong, $doiTuongId, $chiTiet);
}

function ghiNhatKyXem($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'Xem danh sách', $doiTuong, $doiTuongId, $chiTiet);
}

function ghiNhatKyDuyet($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'duyệt', $doiTuong, $doiTuongId, $chiTiet);
}

function ghiNhatKyHuy($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'hủy', $doiTuong, $doiTuongId, $chiTiet);
}

function ghiNhatKyPhanQuyen($username, $doiTuong, $doiTuongId, $chiTiet = '')
{
    return ghiNhatKyHoatDong($username, 'phân quyền', $doiTuong, $doiTuongId, $chiTiet);
}
