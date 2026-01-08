<div class="admin-title">Sửa chi tiết phiếu nhập kho</div>
<hr>
<?php
require_once './elements_LQA/mod/mphieunhapCls.php';
require_once './elements_LQA/mod/mchitietphieunhapCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';

$phieuNhapObj = new MPhieuNhap();
$chiTietObj = new MChiTietPhieuNhap();
$hanghoaObj = new hanghoa();

if (isset($_GET['idct'])) {
    $idCTPN = $_GET['idct'];
    $chiTiet = $chiTietObj->getChiTietById($idCTPN);
    
    if (!$chiTiet) {
        echo "<div class='alert alert-danger'>Không tìm thấy chi tiết phiếu nhập!</div>";
        echo "<a href='index.php?req=mphieunhap' class='btn btn-primary'>Quay lại</a>";
        exit;
    }
    
    $phieuNhap = $phieuNhapObj->getPhieuNhapById($chiTiet->idPhieuNhap);
    
    if ($phieuNhap->trangThai != 0) {
        echo "<div class='alert alert-warning'>Không thể sửa chi tiết phiếu nhập đã được duyệt hoặc đã hủy!</div>";
        echo "<a href='index.php?req=mchitietphieunhap&idpn=" . $chiTiet->idPhieuNhap . "' class='btn btn-primary'>Quay lại</a>";
        exit;
    }
} else {
    header("Location: index.php?req=mphieunhap");
    exit;
}
?>

<div class="admin-form">
    <h3>Sửa thông tin chi tiết phiếu nhập</h3>
    <form name="editchitiet" id="formeditchitiet" method="post" action='./elements_LQA/mmphieunhap/mchitietphieunhapAct.php?reqact=update'>
        <input type="hidden" name="idCTPN" value="<?php echo $chiTiet->idCTPN; ?>" />
        <input type="hidden" name="idPhieuNhap" value="<?php echo $chiTiet->idPhieuNhap; ?>" />
        <table>
            <tr>
                <td>Sản phẩm</td>
                <td><input type="text" value="<?php echo $chiTiet->tenhanghoa; ?>" disabled /></td>
            </tr>
            <tr>
                <td>Đơn vị tính</td>
                <td><input type="text" value="<?php echo $chiTiet->tenDonViTinh ?? 'N/A'; ?>" disabled /></td>
            </tr>
            <tr>
                <td>Số lượng</td>
                <td><input type="number" name="soLuong" id="soLuong" min="1" value="<?php echo $chiTiet->soLuong; ?>" required onchange="tinhThanhTien()" /></td>
            </tr>
            <tr>
                <td>Đơn giá tham khảo</td>
                <td><input type="number" name="donGia" id="donGia" min="0" value="<?php echo $chiTiet->donGia; ?>" required /></td>
            </tr>
            <tr>
                <td>Giá nhập</td>
                <td><input type="number" name="giaNhap" id="giaNhap" min="0" value="<?php echo $chiTiet->giaNhap; ?>" required onchange="tinhThanhTien()" /></td>
            </tr>
            <tr>
                <td>Thành tiền</td>
                <td><input type="number" id="thanhTien" min="0" value="<?php echo $chiTiet->thanhTien; ?>" disabled /></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="Cập nhật" />
                    <a href="index.php?req=mchitietphieunhap&idpn=<?php echo $chiTiet->idPhieuNhap; ?>" class="btn btn-secondary">Hủy</a>
                </td>
            </tr>
        </table>
    </form>
</div>

<script>

    function tinhThanhTien() {
        var soLuong = document.getElementById('soLuong').value || 0;
        var giaNhap = document.getElementById('giaNhap').value || 0;
        var thanhTien = soLuong * giaNhap;
        document.getElementById('thanhTien').value = thanhTien;
    }
</script>

<style>
    .admin-form {
        max-width: 600px;
        margin: 0 auto;
    }
    .admin-form table {
        width: 100%;
    }
    .admin-form table td {
        padding: 8px;
    }
    .admin-form input[type="text"],
    .admin-form input[type="number"],
    .admin-form select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .admin-form input[type="submit"],
    .btn {
        padding: 8px 16px;
        margin: 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .admin-form input[type="submit"] {
        background-color: #3498db;
        color: white;
    }
    .btn-secondary {
        background-color: #95a5a6;
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        display: inline-block;
    }
    .btn-primary {
        background-color: #3498db;
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        display: inline-block;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .alert-danger {
        background-color: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }
    .alert-warning {
        background-color: #fcf8e3;
        color: #8a6d3b;
        border: 1px solid #faebcc;
    }
</style>
