<div class="admin-title">Sửa phiếu nhập kho</div>
<hr>
<?php
require_once './elements_LQA/mod/mphieunhapCls.php';
require_once './elements_LQA/mod/nhacungcapCls.php';
require_once './elements_LQA/mod/nhanvienCls.php';

$phieuNhapObj = new MPhieuNhap();
$nccObj = new nhacungcap();
$nvObj = new NhanVien();

if (isset($_GET['idpn'])) {
    $idPhieuNhap = $_GET['idpn'];
    $phieuNhap = $phieuNhapObj->getPhieuNhapById($idPhieuNhap);
    
    if (!$phieuNhap) {
        echo "<div class='alert alert-danger'>Không tìm thấy phiếu nhập!</div>";
        echo "<a href='index.php?req=mphieunhap' class='btn btn-primary'>Quay lại</a>";
        exit;
    }
    
    if ($phieuNhap->trangThai != 0) {
        echo "<div class='alert alert-warning'>Không thể sửa phiếu nhập đã được duyệt hoặc đã hủy!</div>";
        echo "<a href='index.php?req=mphieunhap' class='btn btn-primary'>Quay lại</a>";
        exit;
    }
    
    $list_ncc = $nccObj->NhacungcapGetAll();
    $list_nv = $nvObj->nhanvienGetAll();
} else {
    header("Location: index.php?req=mphieunhap");
    exit;
}
?>

<div class="admin-form">
    <h3>Sửa thông tin phiếu nhập</h3>
    <form name="editphieunhap" id="formeditphieunhap" method="post" action='./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=update'>
        <input type="hidden" name="idPhieuNhap" value="<?php echo $phieuNhap->idPhieuNhap; ?>" />
        <table>
            <tr>
                <td>Mã phiếu nhập</td>
                <td><input type="text" name="maPhieuNhap" value="<?php echo $phieuNhap->maPhieuNhap; ?>" required /></td>
            </tr>
            <tr>
                <td>Ngày nhập</td>
                <td><input type="text" value="<?php echo date('d/m/Y H:i', strtotime($phieuNhap->ngayNhap)); ?>" disabled /></td>
            </tr>
            <tr>
                <td>Nhà cung cấp</td>
                <td>
                    <select name="idNCC" required>
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php
                        if (!empty($list_ncc)) {
                            foreach ($list_ncc as $ncc) {
                                $selected = ($ncc->idNCC == $phieuNhap->idNCC) ? 'selected' : '';
                                echo "<option value='{$ncc->idNCC}' {$selected}>{$ncc->tenNCC}</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Nhân viên</td>
                <td>
                    <select name="idNhanVien" required>
                        <option value="">-- Chọn nhân viên --</option>
                        <?php
                        if (!empty($list_nv)) {
                            foreach ($list_nv as $nv) {
                                $selected = ($nv->idNhanVien == $phieuNhap->idNhanVien) ? 'selected' : '';
                                echo "<option value='{$nv->idNhanVien}' {$selected}>{$nv->tenNV}</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Tổng tiền</td>
                <td><input type="text" value="<?php echo number_format($phieuNhap->tongTien, 0, ',', '.') . ' VNĐ'; ?>" disabled /></td>
            </tr>
            <tr>
                <td>Ghi chú</td>
                <td><textarea name="ghiChu" rows="3"><?php echo $phieuNhap->ghiChu; ?></textarea></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="Cập nhật" />
                    <a href="index.php?req=mphieunhap" class="btn btn-secondary">Hủy</a>
                </td>
            </tr>
        </table>
    </form>
</div>

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
    .admin-form select,
    .admin-form textarea {
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
