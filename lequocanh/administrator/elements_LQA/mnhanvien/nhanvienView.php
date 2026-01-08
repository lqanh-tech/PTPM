<div class="admin-title">Quản lý nhân viên</div>
<hr>

<?php
require_once './elements_LQA/mod/nhanvienCls.php';
require_once './elements_LQA/mod/userCls.php';
require_once './elements_LQA/mod/roleCls.php';
require_once './elements_LQA/mod/phanHeQuanLyCls.php';

if (isset($_SESSION['ADMIN'])) {
    $phanHeSync = new PhanHeQuanLy();
    $syncResult = $phanHeSync->syncModules();
    if ($syncResult > 0) {
        echo '<div class="alert alert-info">Đã tự động thêm ' . $syncResult . ' module phân quyền mới.</div>';
    }
}

if (isset($_GET['notice']) && $_GET['notice'] == 'duplicate_user') {
    echo '<div class="alert alert-warning">Lưu ý: Người dùng này đã được gán cho một nhân viên khác.</div>';
}

$lhobj = new NhanVien();
$list_lh = $lhobj->nhanvienGetAll();
$l = count($list_lh);

$userObj = new user();
$listUsers = $userObj->UserGetAllExceptAdmin();

$roleObj = new Role();

$existingUserIds = [];
foreach ($list_lh as $employee) {
    if (isset($employee->iduser) && $employee->iduser) {
        $existingUserIds[] = $employee->iduser;
    }
}

$filteredUsers = [];
foreach ($listUsers as $user) {

    $isAdmin = $roleObj->isAdmin($user->iduser);
    $isCustomer = $roleObj->isCustomer($user->iduser);

    $isAssignedToEmployee = in_array($user->iduser, $existingUserIds);

    if (!$isAdmin && !$isCustomer && !$isAssignedToEmployee) {
        $filteredUsers[] = $user;
    }
}
?>

<div class="admin-form">
    <h3>Thêm nhân viên mới</h3>
    <form name="newnhanvien" id="formaddnhanvien" method="post"
        action='./elements_LQA/mnhanvien/nhanvienAct.php?reqact=addnew' enctype="multipart/form-data">
        <table>
            <tr>
                <td>Người dùng</td>
                <td>
                    <select name="iduser" id="iduser" class="form-control">
                        <option value="">-- Chọn người dùng --</option>
                        <?php if (count($filteredUsers) > 0): ?>
                            <?php foreach ($filteredUsers as $user): ?>
                                <option value="<?php echo $user->iduser; ?>">
                                    <?php echo htmlspecialchars($user->username) . ' (' . htmlspecialchars($user->hoten) . ') - ' . htmlspecialchars($user->dienthoai); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Không có người dùng phù hợp</option>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Tên nhân viên</td>
                <td><input type="text" name="tenNV" id="tenNV" required /></td>
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
                <td>Lương cơ bản</td>
                <td><input type="number" name="luongCB" id="luongCB" max="9999999999" required /></td>
            </tr>
            <tr>
                <td>Phụ cấp</td>
                <td><input type="number" name="phuCap" id="phuCap" max="9999999999" required /></td>
            </tr>
            <tr>
                <td>Chức vụ</td>
                <td><input type="text" name="chucVu" id="chucVu" required /></td>
            </tr>
            <tr>
                <td>Phân quyền quản lý</td>
                <td>
                    <div class="phan-quyen-container">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="selectAllPhanHe">
                            <label class="form-check-label" for="selectAllPhanHe"><strong>Chọn tất cả</strong></label>
                        </div>
                        <div class="phan-he-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;">
                            <?php

                            require_once './elements_LQA/mod/phanHeQuanLyCls.php';
                            $phanHeObj = new PhanHeQuanLy();
                            $listPhanHe = $phanHeObj->getAllPhanHe();

                            if (count($listPhanHe) > 0) {
                                foreach ($listPhanHe as $phanHe) {
                            ?>
                                    <div class="form-check">
                                        <input class="form-check-input phan-he-checkbox" type="checkbox"
                                            name="phanHe[]" id="phanHe<?php echo $phanHe->idPhanHe; ?>"
                                            value="<?php echo $phanHe->idPhanHe; ?>">
                                        <label class="form-check-label" for="phanHe<?php echo $phanHe->idPhanHe; ?>">
                                            <?php echo htmlspecialchars($phanHe->tenPhanHe); ?>
                                            <small class="text-muted">(<?php echo htmlspecialchars($phanHe->maPhanHe); ?>)</small>
                                        </label>
                                    </div>
                            <?php
                                }
                            } else {
                                echo '<p class="text-muted">Không có phần hệ quản lý nào.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td><input type="submit" id="btnsubmit" value="Tạo mới" /></td>
                <td><input type="reset" value="Làm lại" /><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>

<hr />
<div class="content_nhanvien">
    <div class="admin-info">
        Tổng số nhân viên: <b><?php echo $l; ?></b>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên nhân viên</th>
                <th>Số điện thoại</th>
                <th>Email</th>
                <th>Lương cơ bản</th>
                <th>Phụ cấp</th>
                <th>Chức vụ</th>
                <th>Người dùng</th>
                <th>Phần hệ quản lý</th>
                <th>Chức năng</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($l > 0) {
                foreach ($list_lh as $u) {
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u->idNhanVien); ?></td>
                        <td><?php echo htmlspecialchars($u->tenNV); ?></td>
                        <td><?php echo htmlspecialchars($u->SDT); ?></td>
                        <td><?php echo htmlspecialchars($u->email); ?></td>
                        <td><?php echo number_format($u->luongCB, 0, ',', '.'); ?> đ</td>
                        <td><?php echo number_format($u->phuCap, 0, ',', '.'); ?> đ</td>
                        <td><?php echo htmlspecialchars($u->chucVu); ?></td>
                        <td><?php echo isset($u->username_user) ? htmlspecialchars($u->username_user) : ''; ?></td>
                        <td>
                            <?php

                            require_once './elements_LQA/mod/phanHeQuanLyCls.php';
                            $phanHeObj = new PhanHeQuanLy();
                            $listPhanHe = $phanHeObj->getPhanHeByNhanVienId($u->idNhanVien);

                            if (count($listPhanHe) > 0) {
                                echo '<div style="max-height: 100px; overflow-y: auto;">';
                                foreach ($listPhanHe as $phanHe) {
                                    echo '<span class="badge bg-info me-1 mb-1" style="display: inline-block;">' .
                                        htmlspecialchars($phanHe->tenPhanHe) . '</span>';
                                }
                                echo '</div>';
                            } else {
                                echo '<span class="text-muted">Chưa được phân quyền</span>';
                            }
                            ?>
                        </td>
                        <td align="center">
                            <?php if (isset($_SESSION['ADMIN'])) { ?>
                                <a href="./elements_LQA/mnhanvien/nhanvienAct.php?reqact=deletenhanvien&idNhanVien=<?php echo htmlspecialchars($u->idNhanVien); ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa không?');">
                                    <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                </a>
                            <?php } else { ?>
                                <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg" />
                            <?php } ?>
                            <img src="./elements_LQA/img_LQA/Update.png" class="iconimg generic-update-btn"
                                data-module="mnhanvien" data-update-url="./elements_LQA/mnhanvien/nhanvienUpdate.php"
                                data-id-param="idNhanVien" data-title="Cập nhật Nhân viên"
                                data-id="<?php echo htmlspecialchars($u->idNhanVien); ?>" alt="Update">
                        </td>
                    </tr>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<div id="w_update_nv">
    <div id="w_update_form_nv"></div>
    <button type="button" id="w_close_btn_nv">X</button>
</div>

<!-- Thêm JavaScript để xử lý chọn user và phân quyền -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {

        var filteredUsers = <?php echo json_encode(array_map(function ($user) {
                                return $user->iduser;
                            }, $filteredUsers)); ?>;

        $('#iduser').change(function() {
            var userId = $(this).val();
            $('#noteForm').text('');

            if (userId) {

                $.ajax({
                    url: './elements_LQA/mUser/getUserInfo.php',
                    type: 'GET',
                    data: {
                        iduser: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {

                            var userData = response.data;
                            $('#tenNV').val(userData.hoten);
                            $('#SDT').val(userData.dienthoai);
                            if (userData.email) {
                                $('#email').val(userData.email);
                            }
                        } else {
                            alert('Không thể lấy thông tin người dùng: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Đã xảy ra lỗi khi kết nối đến máy chủ');
                    }
                });
            } else {

                $('#tenNV').val('');
                $('#SDT').val('');
                $('#email').val('');
            }
        });

        $('#selectAllPhanHe').change(function() {
            var isChecked = $(this).prop('checked');
            $('.phan-he-checkbox').prop('checked', isChecked);
        });

        $('.phan-he-checkbox').change(function() {
            var totalCheckboxes = $('.phan-he-checkbox').length;
            var checkedCheckboxes = $('.phan-he-checkbox:checked').length;

            $('#selectAllPhanHe').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    });
</script>