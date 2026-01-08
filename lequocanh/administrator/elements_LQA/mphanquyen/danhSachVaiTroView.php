<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('danhSachVaiTroView', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mod/roleCls.php';
$roleObj = new Role();

require_once './elements_LQA/mod/userCls.php';
$userObj = new user();
$list_users = $userObj->UserGetAll();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['filter_role']) ? $_GET['filter_role'] : '';

$filtered_users = [];
foreach ($list_users as $user) {

    $userRoles = $roleObj->getUserRoles($user->iduser);
    $primaryRole = $roleObj->getPrimaryRole($user->iduser);

    $user->roles = $userRoles;
    $user->primary_role = $primaryRole;

    $searchMatch = empty($search) ||
        stripos($user->username, $search) !== false ||
        stripos($user->hoten, $search) !== false ||
        stripos($user->dienthoai, $search) !== false ||
        (isset($user->email) && stripos($user->email, $search) !== false);

    $roleMatch = empty($filter_role) || $primaryRole === $filter_role;

    if ($searchMatch && $roleMatch) {
        $filtered_users[] = $user;
    }
}

$all_roles = $roleObj->getAllRoles();
?>

<div class="admin-content">
    <h3 class="admin-title">Danh sách người dùng và vai trò</h3>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Tìm kiếm và lọc</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <input type="hidden" name="req" value="danhSachVaiTroView">

                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên, username, email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </div>

                <div class="col-md-4">
                    <select class="form-control" name="filter_role" onchange="this.form.submit()">
                        <option value="">-- Tất cả vai trò --</option>
                        <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="staff" <?php echo $filter_role === 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                        <option value="customer" <?php echo $filter_role === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                        <option value="unknown" <?php echo $filter_role === 'unknown' ? 'selected' : ''; ?>>Chưa có vai trò</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <a href="?req=danhSachVaiTroView" class="btn btn-secondary w-100">
                        <i class="fas fa-sync-alt"></i> Làm mới
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Danh sách người dùng (<?php echo count($filtered_users); ?>)</h5>
            <a href="?req=nguoiDungVaiTroView" class="btn btn-primary btn-sm">
                <i class="fas fa-user-tag"></i> Quản lý vai trò người dùng
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Họ tên</th>
                            <th>Thông tin liên hệ</th>
                            <th>Vai trò chính</th>
                            <th>Tất cả vai trò</th>
                            <th style="width: 120px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($filtered_users) > 0): ?>
                            <?php foreach ($filtered_users as $user): ?>
                                <tr>
                                    <td><?php echo $user->iduser; ?></td>
                                    <td><?php echo htmlspecialchars($user->username); ?></td>
                                    <td><?php echo htmlspecialchars($user->hoten); ?></td>
                                    <td>
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user->dienthoai); ?></div>
                                        <div><i class="fas fa-envelope"></i> <?php echo isset($user->email) ? htmlspecialchars($user->email) : ''; ?></div>
                                    </td>
                                    <td>
                                        <?php if ($user->primary_role === 'admin'): ?>
                                            <span class="badge badge-danger">Admin</span>
                                        <?php elseif ($user->primary_role === 'staff'): ?>
                                            <span class="badge badge-success">Nhân viên</span>
                                        <?php elseif ($user->primary_role === 'customer'): ?>
                                            <span class="badge badge-info">Khách hàng</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Chưa có vai trò</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (count($user->roles) > 0): ?>
                                            <?php foreach ($user->roles as $role): ?>
                                                <span class="badge badge-primary mr-1"><?php echo htmlspecialchars($role->ten_vai_tro ?? ''); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có vai trò</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="?req=nguoiDungVaiTroView&user_id=<?php echo $user->iduser; ?>"
                                            class="btn btn-primary btn-sm"
                                            style="background-color: #007bff; border-color: #007bff; color: white; font-weight: 500; min-width: 100px;"
                                            title="Quản lý vai trò của <?php echo htmlspecialchars($user->hoten); ?>">
                                            <i class="fas fa-user-cog"></i> Quản lý
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Không tìm thấy người dùng nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Hướng dẫn</h5>
        </div>
        <div class="card-body">
            <p>Trang này hiển thị danh sách người dùng và vai trò của họ trong hệ thống:</p>
            <ul>
                <li><span class="badge badge-danger">Admin</span>: Người dùng có quyền quản trị hệ thống, có thể truy cập tất cả các chức năng.</li>
                <li><span class="badge badge-success">Nhân viên</span>: Người dùng có quyền quản lý sản phẩm, đơn hàng và một số chức năng hạn chế.</li>
                <li><span class="badge badge-info">Khách hàng</span>: Người dùng chỉ có quyền mua hàng và quản lý tài khoản cá nhân.</li>
                <li><span class="badge badge-secondary">Chưa có vai trò</span>: Người dùng chưa được gán vai trò nào.</li>
            </ul>
            <p>Để quản lý vai trò của người dùng, nhấp vào nút "Quản lý vai trò" bên cạnh thông tin người dùng.</p>
        </div>
    </div>
</div>

<style>

    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
        vertical-align: middle;
    }

    .table td {
        vertical-align: middle;
        border-color: #dee2e6;
    }

    .btn-primary {
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
    }

    .btn-primary:hover {
        background-color: #0056b3 !important;
        border-color: #0056b3 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.5rem;
        font-weight: 500;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .table tbody tr:hover td .btn {
        cursor: pointer;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('click', function(e) {

                if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('a') || e.target.closest('button')) {
                    return;
                }

                const userId = this.querySelector('td:first-child').textContent;
                window.location.href = `?req=nguoiDungVaiTroView&user_id=${userId}`;
            });
        });

        const buttons = document.querySelectorAll('.btn[title]');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.opacity = '0.9';
            });
            button.addEventListener('mouseleave', function() {
                this.style.opacity = '1';
            });
        });
    });
</script>