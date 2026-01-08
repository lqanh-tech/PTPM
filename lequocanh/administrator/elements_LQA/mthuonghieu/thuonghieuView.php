<div class="admin-title">Quản lý thương hiệu</div>
<hr>

<?php
require_once './elements_LQA/mod/thuonghieuCls.php';
$lhobj = new ThuongHieu();
$list_lh = $lhobj->thuonghieuGetAll();
$l = count($list_lh);
?>

<div class="admin-form">
    <h3>Thêm thương hiệu mới</h3>
    <form name="newthuonghieu" id="formaddthuonghieu" method="post"
        action='./elements_LQA/mthuonghieu/thuonghieuAct.php?reqact=addnew' enctype="multipart/form-data">
        <table>
            <tr>
                <td>Tên thương hiệu</td>
                <td><input type="text" name="tenTH" id="tenTH" required /></td>
            </tr>
            <tr>
                <td>Số điện thoại</td>
                <td><input type="tel" name="SDT" id="SDT" pattern="[0-9]{10}" required /></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><input type="email" name="email" id="email" required /></td>
            </tr>
            <tr>
                <td>Địa chỉ</td>
                <td><input type="text" name="diaChi" id="diaChi" required /></td>
            </tr>
            <tr>
                <td>Hình ảnh</td>
                <td><input type="file" name="fileimage" required></td>
            </tr>
            <tr>
                <td><input type="submit" id="btnsubmit" value="Tạo mới" /></td>
                <td><input type="reset" value="Làm lại" /><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>

<hr />
<div class="content_thuonghieu">
    <div class="admin-info">
        Tổng số thương hiệu: <b><?php echo $l; ?></b>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên thương hiệu</th>
                <th>Số điện thoại</th>
                <th>Email</th>
                <th>Địa chỉ</th>
                <th>Hình ảnh</th>
                <th>Chức năng</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($l > 0) {
                foreach ($list_lh as $u) {
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u->idThuongHieu); ?></td>
                        <td><?php echo htmlspecialchars($u->tenTH); ?></td>
                        <td><?php echo htmlspecialchars($u->SDT); ?></td>
                        <td><?php echo htmlspecialchars($u->email); ?></td>
                        <td><?php echo htmlspecialchars($u->diaChi); ?></td>
                        <td align="center">
                            <img class="iconbutton" src="data:image/png;base64,<?php echo $u->hinhanh; ?>">
                        </td>
                        <td align="center">
                            <?php if (isset($_SESSION['ADMIN'])) { ?>
                                <a href="./elements_LQA/mthuonghieu/thuonghieuAct.php?reqact=deletethuonghieu&idThuongHieu=<?php echo htmlspecialchars($u->idThuongHieu); ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa không?');">
                                    <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                </a>
                            <?php } else { ?>
                                <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                            <?php } ?>
                            <img src="./elements_LQA/img_LQA/Update.png"
                                class="iconimg w_update_btn_open_th"
                                value="<?php echo htmlspecialchars($u->idThuongHieu); ?>"
                                alt="Update">
                        </td>
                    </tr>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Container cho popup cập nhật thương hiệu -->
<div id="w_update_th" style="display: none;">
    <div id="w_close_btn_th" class="close-btn">X</div>
    <div id="w_update_form_th"></div>
</div>

<script>

</script>