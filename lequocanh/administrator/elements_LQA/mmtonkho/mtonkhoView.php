<div class="admin-title">Quản lý tồn kho</div>
<hr>
<?php
require_once './elements_LQA/mod/mtonkhoCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';

$tonkhoObj = new MTonKho();
$hanghoaObj = new hanghoa();

$list_tonkho = $tonkhoObj->getAllTonKho();

$list_saphet = $tonkhoObj->getHangHoaSapHet();

$list_hethang = $tonkhoObj->getHangHoaHetHang();
?>

<div class="admin-content-panel">
    <h3>Danh sách tồn kho</h3>
    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Đơn vị tính</th>
                <th>Số lượng</th>
                <th>Số lượng tối thiểu</th>
                <th>Vị trí</th>
                <th>Ngày cập nhật</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($list_tonkho)) {
                foreach ($list_tonkho as $item) {

                    $trangThai = "";
                    $trangThaiClass = "";
                    if ($item->soLuong == 0) {
                        $trangThai = "Hết hàng";
                        $trangThaiClass = "status-out";
                    } elseif ($item->soLuong > 0 && $item->soLuong <= $item->soLuongToiThieu && $item->soLuongToiThieu > 0) {
                        $trangThai = "Sắp hết";
                        $trangThaiClass = "status-low";
                    } else {
                        $trangThai = "Còn hàng";
                        $trangThaiClass = "status-ok";
                    }
            ?>
                    <tr>
                        <td><?php echo $item->idTonKho; ?></td>
                        <td><?php echo $item->tenhanghoa ?? 'N/A'; ?></td>
                        <td><?php echo $item->tenDonViTinh ?? 'N/A'; ?></td>
                        <td><?php echo $item->soLuong; ?></td>
                        <td><?php echo $item->soLuongToiThieu; ?></td>
                        <td><?php echo $item->viTri; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($item->ngayCapNhat)); ?></td>
                        <td class="<?php echo $trangThaiClass; ?>"><?php echo $trangThai; ?></td>
                        <td align="center">
                            <!-- Sửa thông tin tồn kho -->
                            <a href="index.php?req=mtonkhoedit&idtk=<?php echo $item->idTonKho; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                        </td>
                    </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="9" align="center">Không có dữ liệu tồn kho</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!empty($list_saphet)) { ?>
    <div class="admin-content-panel">
        <h3>Danh sách hàng hóa sắp hết</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sản phẩm</th>
                    <th>Đơn vị tính</th>
                    <th>Số lượng</th>
                    <th>Số lượng tối thiểu</th>
                    <th>Vị trí</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($list_saphet as $item) {
                ?>
                    <tr>
                        <td><?php echo $item->idTonKho; ?></td>
                        <td><?php echo $item->tenhanghoa ?? 'N/A'; ?></td>
                        <td><?php echo $item->tenDonViTinh ?? 'N/A'; ?></td>
                        <td><?php echo $item->soLuong; ?></td>
                        <td><?php echo $item->soLuongToiThieu; ?></td>
                        <td><?php echo $item->viTri; ?></td>
                        <td align="center">
                            <!-- Sửa thông tin tồn kho -->
                            <a href="index.php?req=mtonkhoedit&idtk=<?php echo $item->idTonKho; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php if (!empty($list_hethang)) { ?>
    <div class="admin-content-panel">
        <h3>Danh sách hàng hóa hết hàng</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sản phẩm</th>
                    <th>Đơn vị tính</th>
                    <th>Số lượng tối thiểu</th>
                    <th>Vị trí</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($list_hethang as $item) {
                ?>
                    <tr>
                        <td><?php echo $item->idTonKho; ?></td>
                        <td><?php echo $item->tenhanghoa ?? 'N/A'; ?></td>
                        <td><?php echo $item->tenDonViTinh ?? 'N/A'; ?></td>
                        <td><?php echo $item->soLuongToiThieu; ?></td>
                        <td><?php echo $item->viTri; ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<style>
    .admin-content-panel {
        margin-bottom: 30px;
    }

    .content-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .content-table th,
    .content-table td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .content-table th {
        background-color: #f2f2f2;
    }

    .status-out {
        color: #e74c3c;
        font-weight: bold;
    }

    .status-low {
        color: #f39c12;
        font-weight: bold;
    }

    .status-ok {
        color: #27ae60;
        font-weight: bold;
    }

    .btn-edit {
        display: inline-block;
        margin: 2px;
        padding: 5px 10px;
        border-radius: 3px;
        text-decoration: none;
        color: white;
        background-color: #f39c12;
    }
</style>