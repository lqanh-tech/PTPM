<div class="admin-title">Quản lý phiếu nhập kho</div>
<hr>
<?php
require_once './elements_LQA/mod/mphieunhapCls.php';
require_once './elements_LQA/mod/nhacungcapCls.php';
require_once './elements_LQA/mod/nhanvienCls.php';

$phieuNhapObj = new MPhieuNhap();
$nccObj = new nhacungcap();
$nvObj = new NhanVien();

$list_phieunhap = $phieuNhapObj->getAllPhieuNhap();

$list_ncc = $nccObj->NhacungcapGetAll();
$list_nv = $nvObj->nhanvienGetAll();

$maPhieuNhap = 'PN' . date('YmdHis');
?>

<div class="admin-form">
    <h3>Thêm phiếu nhập kho mới</h3>
    <form name="newphieunhap" id="formaddphieunhap" method="post" action='./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=addnew'>
        <table>
            <tr>
                <td>Mã phiếu nhập</td>
                <td><input type="text" name="maPhieuNhap" value="<?php echo $maPhieuNhap; ?>" required /></td>
            </tr>
            <tr>
                <td>Nhà cung cấp</td>
                <td>
                    <select name="idNCC" required>
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php
                        if (!empty($list_ncc)) {
                            foreach ($list_ncc as $ncc) {
                                echo "<option value='{$ncc->idNCC}'>{$ncc->tenNCC}</option>";
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
                                echo "<option value='{$nv->idNhanVien}'>{$nv->tenNV}</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Ghi chú</td>
                <td><textarea name="ghiChu" rows="3"></textarea></td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="Thêm mới" />
                    <input type="reset" value="Nhập lại" />
                </td>
            </tr>
        </table>
    </form>
</div>

<div class="admin-content-panel">
    <h3>Danh sách phiếu nhập kho</h3>
    <div class="action-buttons">
        <a href="index.php?req=mphieunhapfixtonkho" class="btn-fix-tonkho">
            <i class="fas fa-sync-alt"></i> Cập nhật tồn kho cho phiếu đã duyệt
        </a>
    </div>
    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã phiếu nhập</th>
                <th>Ngày nhập</th>
                <th>Nhân viên</th>
                <th>Nhà cung cấp</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($list_phieunhap)) {
                foreach ($list_phieunhap as $pn) {

                    $trangThai = "";
                    $trangThaiClass = "";
                    switch ($pn->trangThai) {
                        case 0:
                            $trangThai = "Chờ duyệt";
                            $trangThaiClass = "status-pending";
                            break;
                        case 1:
                            $trangThai = "Đã duyệt";
                            $trangThaiClass = "status-approved";
                            break;
                        case 2:
                            $trangThai = "Đã hủy";
                            $trangThaiClass = "status-canceled";
                            break;
                    }
            ?>
                    <tr>
                        <td><?php echo $pn->idPhieuNhap; ?></td>
                        <td><?php echo $pn->maPhieuNhap; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($pn->ngayNhap)); ?></td>
                        <td><?php echo $pn->tenNV ?? 'N/A'; ?></td>
                        <td><?php echo $pn->tenNCC ?? 'N/A'; ?></td>
                        <td><?php echo number_format($pn->tongTien, 0, ',', '.') . ' VNĐ'; ?></td>
                        <td class="<?php echo $trangThaiClass; ?>"><?php echo $trangThai; ?></td>
                        <td align="center">
                            <!-- Xem chi tiết phiếu nhập -->
                            <a href="index.php?req=mchitietphieunhap&idpn=<?php echo $pn->idPhieuNhap; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> Xem
                            </a>

                            <?php if ($pn->trangThai == 0) {
                            ?>
                                <!-- Sửa phiếu nhập -->
                                <a href="index.php?req=mphieunhapedit&idpn=<?php echo $pn->idPhieuNhap; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>

                                <!-- Duyệt phiếu nhập -->
                                <a href="./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=approve&idpn=<?php echo $pn->idPhieuNhap; ?>"
                                    class="btn-approve" onclick="return confirm('Bạn có chắc muốn duyệt phiếu nhập này?')">
                                    <i class="fas fa-check"></i> Duyệt
                                </a>

                                <!-- Hủy phiếu nhập -->
                                <a href="./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=cancel&idpn=<?php echo $pn->idPhieuNhap; ?>"
                                    class="btn-cancel" onclick="return confirm('Bạn có chắc muốn hủy phiếu nhập này?')">
                                    <i class="fas fa-times"></i> Hủy
                                </a>

                                <!-- Xóa phiếu nhập -->
                                <a href="./elements_LQA/mmphieunhap/mphieunhapAct.php?reqact=delete&idpn=<?php echo $pn->idPhieuNhap; ?>"
                                    class="btn-delete" onclick="return confirm('Bạn có chắc muốn xóa phiếu nhập này?')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="8" align="center">Không có phiếu nhập nào</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<style>
    .status-pending {
        color: #f39c12;
        font-weight: bold;
    }

    .status-approved {
        color: #27ae60;
        font-weight: bold;
    }

    .status-canceled {
        color: #e74c3c;
        font-weight: bold;
    }

    .btn-view,
    .btn-edit,
    .btn-approve,
    .btn-cancel,
    .btn-delete {
        display: inline-block;
        margin: 2px;
        padding: 5px 10px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
    }

    .btn-view {
        background-color: #3498db;
    }

    .btn-edit {
        background-color: #f39c12;
    }

    .btn-approve {
        background-color: #27ae60;
    }

    .btn-cancel {
        background-color: #e74c3c;
    }

    .btn-delete {
        background-color: #c0392b;
    }

    .btn-fix-tonkho {
        display: inline-block;
        margin: 0 0 10px 0;
        padding: 8px 15px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
        background-color: #3498db;
    }

    .action-buttons {
        margin-bottom: 15px;
    }
</style>