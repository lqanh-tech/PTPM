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

require_once '../mod/NewsManager.php';
$newsManager = new NewsManager();

$action = $_GET['action'] ?? 'list';
$message = '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $author = trim($_POST['author'] ?? 'Admin');
            $is_published = isset($_POST['is_published']) ? 1 : 0;

            if (empty($title)) {
                $message = 'Lỗi: Tiêu đề không được để trống';
                break;
            }
            if (empty($content)) {
                $message = 'Lỗi: Nội dung không được để trống';
                break;
            }

            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_url = $newsManager->uploadNewsImage($_FILES['image']);
                if (!$image_url) {
                    $message = 'Lỗi: Không thể upload ảnh. Kiểm tra định dạng file và quyền thư mục';
                    break;
                }
            }

            if ($newsManager->addNews($title, $content, $image_url, $author, $is_published)) {
                $message = 'Thêm tin tức thành công';
                header('Location: ?msg=success');
                exit();
            } else {
                $message = 'Lỗi: Không thể thêm tin tức vào database. Kiểm tra bảng news đã được tạo chưa.';
            }
        }
        break;

    case 'edit':
        $id = (int)($_GET['id'] ?? 0);
        $news = $newsManager->getNewsById($id);

        if (!$news) {
            header('Location: ?msg=notfound');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $author = trim($_POST['author'] ?? 'Admin');
            $is_published = isset($_POST['is_published']) ? 1 : 0;

            $image_url = $news['featured_image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $new_image_url = $newsManager->uploadNewsImage($_FILES['image']);
                if ($new_image_url) {

                    if ($news['featured_image'] !== $new_image_url && $news['featured_image']) {
                        $oldImagePath = __DIR__ . '/../../..' . $news['featured_image'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $image_url = $new_image_url;
                } else {
                    $message = 'Lỗi upload ảnh mới';
                }
            }

            if ($newsManager->updateNews($id, $title, $content, $image_url, $author, $is_published)) {
                $message = 'Cập nhật tin tức thành công';
                header('Location: ?msg=success');
            } else {
                $message = 'Lỗi khi cập nhật tin tức';
            }
        }
        break;

    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        $news = $newsManager->getNewsById($id);
        
        if ($news && $newsManager->deleteNews($id)) {

            if ($news['featured_image']) {
                $imagePath = __DIR__ . '/../../..' . $news['featured_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $message = 'Xóa tin tức thành công';
            header('Location: ?msg=deleted');
        } else {
            $message = 'Lỗi khi xóa tin tức';
        }
        break;
}

$newsList = $newsManager->getAllNews();
$msg = $_GET['msg'] ?? '';
if ($msg === 'success') $message = 'Thành công';
if ($msg === 'deleted') $message = 'Xóa thành công';
if ($msg === 'notfound') $message = 'Tin tức không tồn tại';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tin tức</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Quản lý Tin tức</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Thêm Tin tức</a>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Tác giả</th>
                            <th>Ngày đăng</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsList as $newsItem): ?>
                        <tr>
                            <td><?php echo $newsItem['id']; ?></td>
                            <td>
                                <?php if ($newsItem['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($newsItem['featured_image']); ?>" alt="News" width="10">
                                <?php else: ?>
                                    <span class="text-muted">Không có ảnh</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($newsItem['title']); ?></td>
                            <td><?php echo htmlspecialchars($newsItem['author']); ?></td>
                            <td><?php echo $newsItem['published_date'] ? date('d/m/Y H:i', strtotime($newsItem['published_date'])) : 'Chưa đăng'; ?></td>
                            <td><?php echo $newsItem['is_published'] ? '<span class="badge bg-success">Đã đăng</span>' : '<span class="badge bg-warning">Chưa đăng</span>'; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $newsItem['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $newsItem['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($action === 'edit' ? $news['title'] : ($_POST['title'] ?? '')); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Nội dung</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($action === 'edit' ? $news['content'] : ($_POST['content'] ?? '')); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="image" class="form-label">Ảnh Tin tức</label>
                            <?php if ($action === 'edit' && $news['featured_image']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($news['featured_image']); ?>" alt="Current News Image" width="200">
                                </div>
                            <?php endif; ?>