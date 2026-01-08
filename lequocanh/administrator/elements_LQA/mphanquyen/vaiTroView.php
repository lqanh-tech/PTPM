<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->isNhanVien($username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mod/roleCls.php';
$roleObj = new Role();
$list_roles = $roleObj->getAllRoles();
$l = count($list_roles);

$result = isset($_GET['result']) ? $_GET['result'] : '';
$message = '';
$messageClass = '';

if ($result == 'ok') {
    $message = 'Thao tác thành công!';
    $messageClass = 'success';
} elseif ($result == 'failed') {
    $message = 'Thao tác thất bại!';
    $messageClass = 'danger';
} elseif ($result == 'exists') {
    $message = 'Tên vai trò đã tồn tại!';
    $messageClass = 'warning';
} elseif ($result == 'in_use') {
    $message = 'Không thể xóa vai trò đang được sử dụng!';
    $messageClass = 'warning';
}
?>

<div class="admin-content">
    <h3 class="admin-title">Quản lý vai trò người dùng</h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5>Thêm vai trò mới</h5>
                </div>
                <div class="card-body">
                    <form name="newrole" id="formrole" method="post" action="./elements_LQA/mphanquyen/vaiTroAct.php?reqact=addnew">
                        <div class="mb-3">
                            <label for="role_name" class="form-label">Tên vai trò:</label>
                            <input type="text" class="form-control" id="role_name" name="role_name" required>
                            <small class="form-text text-muted">Ví dụ: manager, editor, viewer...</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả:</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Thêm vai trò</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5>Danh sách vai trò</h5>
                </div>
                <div class="card-body">
                    <?php if ($l > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên vai trò</th>
                                        <th>Mô tả</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($list_roles as $role): ?>
                                        <tr>
                                            <td><?php echo $role->id; ?></td>
                                            <td><?php echo htmlspecialchars($role->ten_vai_tro ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($role->mo_ta ?? ''); ?></td>
                                            <td><?php echo isset($role->ngay_tao) ? date('d/m/Y H:i', strtotime($role->ngay_tao)) : ''; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-role"
                                                        data-id="<?php echo $role->id; ?>"
                                                        data-name="<?php echo htmlspecialchars($role->ten_vai_tro ?? ''); ?>"
                                                        data-description="<?php echo htmlspecialchars($role->mo_ta ?? ''); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (!in_array($role->ten_vai_tro ?? '', ['admin', 'staff', 'customer'])): ?>
                                                        <a href="./elements_LQA/mphanquyen/vaiTroAct.php?reqact=delete&id=<?php echo $role->id; ?>"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Bạn có chắc chắn muốn xóa vai trò này?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Chưa có vai trò nào.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chỉnh sửa vai trò -->
<div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel">Chỉnh sửa vai trò</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="./elements_LQA/mphanquyen/vaiTroAct.php?reqact=update" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label">Tên vai trò:</label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Mô tả:</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {

        $('.edit-role').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const description = $(this).data('description');

            $('#edit_id').val(id);
            $('#edit_role_name').val(name);
            $('#edit_description').val(description);

            $('#editRoleModal').modal('show');
        });
    });
</script>