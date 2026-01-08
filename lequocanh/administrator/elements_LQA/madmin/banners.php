<?php
require_once '../mod/sessionManager.php';
SessionManager::start();

require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!$phanQuyen->checkAccess('marketing_content', $username)) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập chức năng này.</div>';
    exit();
}

require_once '../mod/BannerManager.php';
$bannerManager = new BannerManager();

$action = $_GET['action'] ?? 'list';
$message = '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $link_url = trim($_POST['link_url'] ?? '');
            $position = (int)($_POST['position'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($title)) {
                $message = 'Lỗi: Tiêu đề không được để trống';
                break;
            }

            $image_url = '';
            if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
                $message = 'Lỗi: Vui lòng chọn ảnh banner';
                break;
            }

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File quá lớn (vượt quá upload_max_filesize)',
                    UPLOAD_ERR_FORM_SIZE => 'File quá lớn (vượt quá MAX_FILE_SIZE)',
                    UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                    UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                    UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào đĩa',
                    UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension'
                ];
                $message = 'Lỗi upload: ' . ($uploadErrors[$_FILES['image']['error']] ?? 'Lỗi không xác định');
                break;
            }

            $image_url = $bannerManager->uploadBannerImage($_FILES['image']);
            if (!$image_url) {
                $message = 'Lỗi: Không thể upload ảnh. Kiểm tra định dạng file (JPG, PNG, GIF) và quyền thư mục uploads/';
                break;
            }

            if ($bannerManager->addBanner($title, $description, $image_url, $link_url, $position, $is_active)) {
                $message = 'Thêm banner thành công';
                header('Location: ?msg=success');
                exit();
            } else {
                $message = 'Lỗi: Không thể thêm banner vào database. Kiểm tra bảng banners đã được tạo chưa.';
            }
        }
        break;

    case 'edit':
        $id = (int)($_GET['id'] ?? 0);
        $banner = $bannerManager->getBannerById($id);

        if (!$banner) {
            header('Location: ?msg=notfound');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $link_url = trim($_POST['link_url'] ?? '');
            $position = (int)($_POST['position'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $image_url = $banner['image_url'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $new_image_url = $bannerManager->uploadBannerImage($_FILES['image']);
                if ($new_image_url) {

                    if ($banner['image_url'] !== $new_image_url) {
                        $oldImagePath = __DIR__ . '/../../..' . $banner['image_url'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $image_url = $new_image_url;
                } else {
                    $message = 'Lỗi upload ảnh mới';
                }
            }

            if ($bannerManager->updateBanner($id, $title, $description, $image_url, $link_url, $position, $is_active)) {
                $message = 'Cập nhật banner thành công';
                header('Location: ?msg=success');
            } else {
                $message = 'Lỗi khi cập nhật banner';
            }
        }
        break;

    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        $banner = $bannerManager->getBannerById($id);
        
        if ($banner && $bannerManager->deleteBanner($id)) {

            if ($banner['image_url']) {
                $imagePath = __DIR__ . '/../../..' . $banner['image_url'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $message = 'Xóa banner thành công';
            header('Location: ?msg=deleted');
        } else {
            $message = 'Lỗi khi xóa banner';
        }
        break;
}

$banners = $bannerManager->getAllBanners();
$msg = $_GET['msg'] ?? '';
if ($msg === 'success') $message = 'Thành công';
if ($msg === 'deleted') $message = 'Xóa thành công';
if ($msg === 'notfound') $message = 'Banner không tồn tại';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Banner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Quản lý Banner</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Thêm Banner</a>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Mô tả</th>
                            <th>Vị trí</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td><?php echo $banner['id']; ?></td>
                            <td><img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="Banner" width="100"></td>
                            <td><?php echo htmlspecialchars($banner['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($banner['description'], 0, 50)) . (strlen($banner['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $banner['position']; ?></td>
                            <td><?php echo $banner['is_active'] ? '<span class="badge bg-success">Hiển thị</span>' : '<span class="badge bg-secondary">Ẩn</span>'; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $banner['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $banner['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($action === 'edit' ? $banner['title'] : ($_POST['title'] ?? '')); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($action === 'edit' ? $banner['description'] : ($_POST['description'] ?? '')); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="link_url" class="form-label">Liên kết</label>
                            <input type="url" class="form-control" id="link_url" name="link_url" value="<?php echo htmlspecialchars($action === 'edit' ? $banner['link_url'] : ($_POST['link_url'] ?? '')); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image" class="form-label">Ảnh Banner</label>
                            <?php if ($action === 'edit' && $banner['image_url']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="Current Banner" width="200">
                                </div>
                            <?php endif; ?>