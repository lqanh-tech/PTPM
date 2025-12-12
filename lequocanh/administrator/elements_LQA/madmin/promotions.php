<?php
require_once '../mod/sessionManager.php';
SessionManager::start();

// Check access rights using PhanQuyen system
require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!$phanQuyen->checkAccess('marketing_content', $username)) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập chức năng này.</div>';
    exit();
}

require_once '../mod/PromotionManager.php';
$promotionManager = new PromotionManager();

$action = $_GET['action'] ?? 'list';
$message = '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $discount_percent = floatval($_POST['discount_percent'] ?? 0);
            $start_date = $_POST['start_date'] ?? date('Y-m-d');
            $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'));
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Kiểm tra dữ liệu đầu vào
            if (empty($title)) {
                $message = 'Lỗi: Tiêu đề không được để trống';
                break;
            }
            if ($discount_percent < 0 || $discount_percent > 100) {
                $message = 'Lỗi: Phần trăm giảm giá phải từ 0 đến 100';
                break;
            }
            if (strtotime($end_date) < strtotime($start_date)) {
                $message = 'Lỗi: Ngày kết thúc phải sau ngày bắt đầu';
                break;
            }

            if ($promotionManager->addPromotion($title, $description, $discount_percent, $start_date, $end_date, $is_active)) {
                $message = 'Thêm chương trình ưu đãi thành công';
                header('Location: ?msg=success');
                exit();
            } else {
                $message = 'Lỗi: Không thể thêm chương trình ưu đãi vào database. Kiểm tra bảng promotions đã được tạo chưa.';
            }
        }
        break;

    case 'edit':
        $id = (int)($_GET['id'] ?? 0);
        $promotion = $promotionManager->getPromotionById($id);

        if (!$promotion) {
            header('Location: ?msg=notfound');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $discount_percent = floatval($_POST['discount_percent'] ?? 0);
            $start_date = $_POST['start_date'] ?? date('Y-m-d');
            $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'));
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($promotionManager->updatePromotion($id, $title, $description, $discount_percent, $start_date, $end_date, $is_active)) {
                $message = 'Cập nhật chương trình ưu đãi thành công';
                header('Location: ?msg=success');
            } else {
                $message = 'Lỗi khi cập nhật chương trình ưu đãi';
            }
        }
        break;

    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        
        if ($promotionManager->deletePromotion($id)) {
            $message = 'Xóa chương trình ưu đãi thành công';
            header('Location: ?msg=deleted');
        } else {
            $message = 'Lỗi khi xóa chương trình ưu đãi';
        }
        break;
}

$promotions = $promotionManager->getAllPromotions();
$msg = $_GET['msg'] ?? '';
if ($msg === 'success') $message = 'Thành công';
if ($msg === 'deleted') $message = 'Xóa thành công';
if ($msg === 'notfound') $message = 'Chương trình ưu đãi không tồn tại';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chương trình Ưu đãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Quản lý Chương trình Ưu đãi</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Thêm Ưu đãi</a>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề</th>
                            <th>Giảm giá</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $promotion): ?>
                        <tr>
                            <td><?php echo $promotion['id']; ?></td>
                            <td><?php echo htmlspecialchars($promotion['title']); ?></td>
                            <td><?php echo $promotion['discount_percent']; ?>%</td>
                            <td><?php echo date('d/m/Y', strtotime($promotion['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($promotion['end_date'])); ?></td>
                            <td>
                                <?php 
                                $isActive = $promotion['is_active'] && 
                                          strtotime($promotion['start_date']) <= time() && 
                                          strtotime($promotion['end_date']) >= time();
                                echo $isActive ? '<span class="badge bg-success">Hiệu lực</span>' : '<span class="badge bg-secondary">Không hiệu lực</span>'; 
                                ?>
                            </td>
                            <td>
                                <a href="?action=edit&id=<?php echo $promotion['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $promotion['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($action === 'edit' ? $promotion['title'] : ($_POST['title'] ?? '')); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($action === 'edit' ? $promotion['description'] : ($_POST['description'] ?? '')); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="discount_percent" class="form-label">Phần trăm giảm giá (%)</label>
                            <input type="number" class="form-control" id="discount_percent" name="discount_percent" min="0" max="100" step="0.01" value="<?php echo $action === 'edit' ? $promotion['discount_percent'] : ($_POST['discount_percent'] ?? '0'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Ngày bắt đầu</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $action === 'edit' ? $promotion['start_date'] : ($_POST['start_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Ngày kết thúc</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $action === 'edit' ? $promotion['end_date'] : ($_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'))); ?>" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo ($action === 'edit' && $promotion['is_active']) || (isset($_POST['is_active']) && $_POST['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Kích hoạt</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Cập nhật' : 'Thêm'; ?> Ưu đãi</button>
                <a href="?" class="btn btn-secondary">Hủy</a>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>