<?php

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN'])) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

require_once './elements_LQA/mod/roleCls.php';
$roleObj = new Role();
$list_roles = $roleObj->getAllRoles();

require_once './elements_LQA/mod/userCls.php';
$userObj = new user();
$list_users = $userObj->UserGetAll();

$usersWithRoles = [];
try {

    $db = Database::getInstance()->getConnection();
    $sql = "SELECT DISTINCT user_id FROM user_roles";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $usersWithRoles[] = (int)$row['user_id'];
    }
} catch (PDOException $e) {
    error_log("Lỗi khi lấy danh sách người dùng đã có vai trò: " . $e->getMessage());
}

$result = isset($_GET['result']) ? $_GET['result'] : '';
$message = '';
$messageClass = '';

if ($result == 'ok') {
    $message = 'Thao tác thành công!';
    $messageClass = 'success';
} elseif ($result == 'failed') {
    $message = 'Thao tác thất bại!';
    $messageClass = 'danger';
}

$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selectedUser = null;
$userRoles = [];

if ($selectedUserId > 0) {
    $selectedUser = $userObj->UserGetbyId($selectedUserId);
    if ($selectedUser) {
        $userRoles = $roleObj->getUserRoles($selectedUserId);
    }
}
?>

<div class="admin-content">
    <h3 class="admin-title">Quản lý vai trò người dùng</h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5>Danh sách người dùng</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="searchUser" placeholder="Tìm kiếm người dùng...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="list-group user-list" style="max-height: 500px; overflow-y: auto;">
                        <?php
                        $hasUsers = false;
                        foreach ($list_users as $user):

                            if (!in_array($user->iduser, $usersWithRoles)):
                                $hasUsers = true;
                        ?>
                                <a href="?req=nguoiDungVaiTroView&user_id=<?php echo $user->iduser; ?>"
                                    class="list-group-item list-group-item-action <?php echo ($selectedUserId == $user->iduser) ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user->username); ?></h6>
                                        <small>ID: <?php echo $user->iduser; ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($user->hoten); ?></p>
                                    <small><?php echo htmlspecialchars($user->dienthoai); ?></small>
                                </a>
                            <?php
                            endif;
                        endforeach;

                        if (!$hasUsers):
                            ?>
                            <div class="text-center p-3 text-muted">
                                <i class="fas fa-info-circle mb-2"></i>
                                <p>Không có người dùng nào chưa được gán vai trò.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <?php if ($selectedUser): ?>
                <div class="card">
                    <div class="card-header">
                        <h5>Quản lý vai trò cho: <?php echo htmlspecialchars($selectedUser->hoten); ?>
                            (<?php echo htmlspecialchars($selectedUser->username); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <form action="./elements_LQA/mphanquyen/nguoiDungVaiTroAct.php?reqact=assign" method="post">
                            <input type="hidden" name="user_id" value="<?php echo $selectedUser->iduser; ?>">

                            <div class="mb-3">
                                <label class="form-label">Vai trò hiện tại:</label>
                                <div>
                                    <?php if (count($userRoles) > 0): ?>
                                        <?php foreach ($userRoles as $role): ?>
                                            <span class="badge badge-primary mr-1">
                                                <?php echo htmlspecialchars($role->ten_vai_tro ?? ''); ?>
                                                <?php if (($role->ten_vai_tro ?? '') != 'admin' || $selectedUser->username != 'admin'): ?>
                                                    <a href="./elements_LQA/mphanquyen/nguoiDungVaiTroAct.php?reqact=remove&user_id=<?php echo $selectedUser->iduser; ?>&role_id=<?php echo $role->id; ?>"
                                                        class="text-white ml-1"
                                                        onclick="return confirm('Bạn có chắc chắn muốn xóa vai trò này?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa có vai trò nào</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="role_id" class="form-label">Thêm vai trò mới:</label>
                                <select class="form-control" id="role_id" name="role_id">
                                    <option value="">-- Chọn vai trò --</option>
                                    <?php foreach ($list_roles as $role): ?>
                                        <option value="<?php echo $role->id; ?>">
                                            <?php echo htmlspecialchars($role->ten_vai_tro ?? ''); ?> -
                                            <?php echo htmlspecialchars($role->mo_ta ?? ''); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Thêm vai trò</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <p class="text-center">Vui lòng chọn một người dùng từ danh sách bên trái.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const searchInput = document.getElementById('searchUser');
        const clearButton = document.getElementById('clearSearch');
        const userItems = document.querySelectorAll('.user-list .list-group-item');
        const noResultsMessage = document.createElement('div');

        noResultsMessage.className = 'text-center p-3 text-muted';
        noResultsMessage.innerHTML =
            '<i class="fas fa-search mb-2"></i><p>Không tìm thấy người dùng nào phù hợp.</p>';
        noResultsMessage.style.display = 'none';

        const userList = document.querySelector('.user-list');
        userList.appendChild(noResultsMessage);

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let visibleCount = 0;

            userItems.forEach(item => {
                const username = item.querySelector('h6').textContent.toLowerCase();
                const fullname = item.querySelector('p').textContent.toLowerCase();
                const phone = item.querySelector('small:last-child').textContent.toLowerCase();

                if (username.includes(searchTerm) || fullname.includes(searchTerm) || phone
                    .includes(searchTerm)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0 && searchTerm !== '') {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
        });

        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            userItems.forEach(item => {
                item.style.display = '';
            });
            noResultsMessage.style.display = 'none';
        });
    });
</script>