<?php
// Session đã được start trong index.php, không cần start lại
// require_once __DIR__ . '/../mod/sessionManager.php';
// SessionManager::start();

// Check access rights using PhanQuyen system
require_once __DIR__ . '/../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

// Kiểm tra quyền truy cập module marketing_content
if (!$phanQuyen->checkAccess('marketing_content', $username)) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập chức năng này. Vui lòng liên hệ Admin để được cấp quyền.</div>';
    echo '<p class="m-3"><a href="../index.php">Quay lại trang chủ</a></p>';
    exit();
}

require_once __DIR__ . '/../mod/BannerManager.php';
require_once __DIR__ . '/../mod/NewsManager.php';
require_once __DIR__ . '/../mod/PromotionManager.php';
require_once __DIR__ . '/../mod/PageManager.php';

$bannerManager = new BannerManager();
$newsManager = new NewsManager();
$promotionManager = new PromotionManager();
$pageManager = new PageManager();

// Get active tab
$activeTab = $_GET['tab'] ?? 'banners';

// Get message from session (set by marketing_content_handler.php)
$message = '';
$messageSuccess = false;
if (isset($_SESSION['marketing_message'])) {
    $message = $_SESSION['marketing_message'];
    $messageSuccess = $_SESSION['marketing_success'] ?? false;
    unset($_SESSION['marketing_message']);
    unset($_SESSION['marketing_success']);
}

// Fetch data
$banners = $bannerManager->getAllBanners();
$news = $newsManager->getAllNews();
$promotions = $promotionManager->getAllPromotions();
$blogs = $pageManager->getAllBlogs();
$staticPages = $pageManager->getAllStaticPages();
?>

<!-- Marketing Content Styles -->
<style>
    #center_div {
        background: #fff !important;
    }
    
    .marketing-content-wrapper {
        background: #fff;
        padding: 20px;
    }
    
    .marketing-content-wrapper h2 {
        color: #333;
        margin-bottom: 20px;
    }
    
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 20px;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-right: 5px;
    }
    
    .nav-tabs .nav-link.active {
        color: #fff;
        background: #0d6efd;
        border-color: #0d6efd;
    }
    
    .tab-content {
        background: #fff;
        padding: 20px;
        border: 1px solid #dee2e6;
        border-top: none;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
    }
    
    .table {
        background: #fff;
    }
    
    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
    }
    
    .btn-primary {
        background: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-warning {
        background: #ffc107;
        border-color: #ffc107;
        color: #000;
    }
    
    .btn-danger {
        background: #dc3545;
        border-color: #dc3545;
    }
</style>

<div class="marketing-content-wrapper">
        <h2>Quản lý Nội dung Marketing</h2>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="marketingTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'banners' ? 'active' : ''; ?>"
                    id="banners-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#banners"
                    type="button"
                    role="tab">Banners</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'news' ? 'active' : ''; ?>"
                    id="news-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#news"
                    type="button"
                    role="tab">Tin tức</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'promotions' ? 'active' : ''; ?>"
                    id="promotions-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#promotions"
                    type="button"
                    role="tab">Chương trình ưu đãi</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'blogs' ? 'active' : ''; ?>"
                    id="blogs-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#blogs"
                    type="button"
                    role="tab">Blog</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'pages' ? 'active' : ''; ?>"
                    id="pages-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#pages"
                    type="button"
                    role="tab">Trang tĩnh</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="marketingTabContent">
            <!-- Banners Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'banners' ? 'show active' : ''; ?>"
                id="banners"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Banner mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="tab" value="banners">
                            <input type="hidden" name="action" value="add_banner">
                            <div class="mb-3">
                                <label for="banner_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="banner_title" name="banner_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="banner_description" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="banner_description" name="banner_description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="banner_link_url" class="form-label">Liên kết</label>
                                <input type="url" class="form-control" id="banner_link_url" name="banner_link_url">
                            </div>
                            <div class="mb-3">
                                <label for="banner_image" class="form-label">Ảnh Banner</label>
                                <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="banner_position" class="form-label">Vị trí</label>
                                <input type="number" class="form-control" id="banner_position" name="banner_position" value="0" min="0">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="banner_is_active" name="banner_is_active" checked>
                                <label class="form-check-label" for="banner_is_active">Hiển thị</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Banner</button>
                        </form>
                    </div>

                    <div class="col-md-6">
                        <h4>Danh sách Banner</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh</th>
                                        <th>Tiêu đề</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banners as $banner):
                                        $isHighlighted = isset($_SESSION['highlight_banner_id']) && $_SESSION['highlight_banner_id'] == $banner['id'];
                                        $rowClass = $isHighlighted ? 'table-success' : '';
                                    ?>
                                        <tr class="<?php echo $rowClass; ?>" id="banner-row-<?php echo $banner['id']; ?>">
                                            <td><?php echo $banner['id']; ?></td>
                                            <td><img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="Banner" width="50"></td>
                                            <td><?php echo htmlspecialchars($banner['title']); ?></td>
                                            <td><?php echo $banner['is_active'] ? '<span class="badge bg-success">Hiển thị</span>' : '<span class="badge bg-secondary">Ẩn</span>'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-banner" data-bs-toggle="modal" data-bs-target="#bannerModal" data-id="<?php echo $banner['id']; ?>">Sửa</button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                                    <input type="hidden" name="_marketing_handler" value="1">
                                                    <input type="hidden" name="tab" value="banners">
                                                    <input type="hidden" name="action" value="delete_banner">
                                                    <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                    <input type="hidden" name="tab" value="banners">
                                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php
                                        if ($isHighlighted) {
                                            unset($_SESSION['highlight_banner_id']);
                                        }
                                    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- News Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'news' ? 'show active' : ''; ?>"
                id="news"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Tin tức mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="action" value="add_news">
                            <input type="hidden" name="tab" value="news">
                            <div class="mb-3">
                                <label for="news_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="news_title" name="news_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="news_content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="news_content" name="news_content" rows="4"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="news_author" class="form-label">Tác giả</label>
                                <input type="text" class="form-control" id="news_author" name="news_author" value="Admin">
                            </div>
                            <div class="mb-3">
                                <label for="news_image" class="form-label">Ảnh tin tức</label>
                                <input type="file" class="form-control" id="news_image" name="news_image" accept="image/*">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="news_is_published" name="news_is_published">
                                <label class="form-check-label" for="news_is_published">Xuất bản</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Tin tức</button>
                        </form>
                    </div>

                    <div class="col-md-6">
                        <h4>Danh sách Tin tức</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh</th>
                                        <th>Tiêu đề</th>
                                        <th>Tác giả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($news as $newsItem):
                                        $isHighlighted = isset($_SESSION['highlight_news_id']) && $_SESSION['highlight_news_id'] == $newsItem['id'];
                                        $rowClass = $isHighlighted ? 'table-success' : '';
                                    ?>
                                        <tr class="<?php echo $rowClass; ?>" id="news-row-<?php echo $newsItem['id']; ?>">
                                            <td><?php echo $newsItem['id']; ?></td>
                                            <td>
                                                <?php if ($newsItem['featured_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($newsItem['featured_image']); ?>" alt="News" width="50">
                                                <?php else: ?>
                                                    <span>Không có ảnh</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($newsItem['title']); ?></td>
                                            <td><?php echo htmlspecialchars($newsItem['author_id'] ?? 'N/A'); ?></td>
                                            <td><?php echo $newsItem['is_published'] ? '<span class="badge bg-success">Đã xuất bản</span>' : '<span class="badge bg-warning">Chưa xuất bản</span>'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-news" data-bs-toggle="modal" data-bs-target="#newsModal" data-id="<?php echo $newsItem['id']; ?>">Sửa</button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                                    <input type="hidden" name="_marketing_handler" value="1">
                                                    <input type="hidden" name="action" value="delete_news">
                                                    <input type="hidden" name="news_id" value="<?php echo $newsItem['id']; ?>">
                                                    <input type="hidden" name="tab" value="news">
                                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php
                                        if ($isHighlighted) {
                                            unset($_SESSION['highlight_news_id']);
                                        }
                                    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Promotions Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'promotions' ? 'show active' : ''; ?>"
                id="promotions"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Chương trình ưu đãi mới</h4>
                        <form method="POST">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="action" value="add_promotion">
                            <input type="hidden" name="tab" value="promotions">
                            <div class="mb-3">
                                <label for="promotion_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="promotion_title" name="promotion_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="promotion_description" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="promotion_description" name="promotion_description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="promotion_discount_percent" class="form-label">Phần trăm giảm giá (%)</label>
                                <input type="number" class="form-control" id="promotion_discount_percent" name="promotion_discount_percent" min="0" max="100" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="promotion_start_date" class="form-label">Ngày bắt đầu</label>
                                        <input type="date" class="form-control" id="promotion_start_date" name="promotion_start_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="promotion_end_date" class="form-label">Ngày kết thúc</label>
                                        <input type="date" class="form-control" id="promotion_end_date" name="promotion_end_date" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="promotion_is_active" name="promotion_is_active" checked>
                                <label class="form-check-label" for="promotion_is_active">Kích hoạt</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Ưu đãi</button>
                        </form>
                    </div>

                    <div class="col-md-6">
                        <h4>Danh sách Chương trình ưu đãi</h4>
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
                                    <?php foreach ($promotions as $promotion):
                                        $isHighlighted = isset($_SESSION['highlight_promotion_id']) && $_SESSION['highlight_promotion_id'] == $promotion['id'];
                                        $rowClass = $isHighlighted ? 'table-success' : '';
                                    ?>
                                        <tr class="<?php echo $rowClass; ?>" id="promotion-row-<?php echo $promotion['id']; ?>">
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
                                                <button class="btn btn-sm btn-warning edit-promotion" data-bs-toggle="modal" data-bs-target="#promotionModal" data-id="<?php echo $promotion['id']; ?>">Sửa</button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                                    <input type="hidden" name="_marketing_handler" value="1">
                                                    <input type="hidden" name="action" value="delete_promotion">
                                                    <input type="hidden" name="promotion_id" value="<?php echo $promotion['id']; ?>">
                                                    <input type="hidden" name="tab" value="promotions">
                                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php
                                        if ($isHighlighted) {
                                            unset($_SESSION['highlight_promotion_id']);
                                        }
                                    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blog Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'blogs' ? 'show active' : ''; ?>"
                id="blogs"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-5">
                        <h4>Thêm bài viết Blog mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="action" value="add_blog">
                            <input type="hidden" name="tab" value="blogs">
                            <div class="mb-3">
                                <label for="blog_title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="blog_title" name="blog_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="blog_excerpt" class="form-label">Tóm tắt</label>
                                <textarea class="form-control" id="blog_excerpt" name="blog_excerpt" rows="2" placeholder="Mô tả ngắn về bài viết..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="blog_content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="blog_content" name="blog_content" rows="6"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="blog_thumbnail" class="form-label">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="blog_thumbnail" name="blog_thumbnail" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="blog_status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="blog_status" name="blog_status">
                                    <option value="draft">Nháp</option>
                                    <option value="published">Xuất bản</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm bài viết</button>
                        </form>
                    </div>

                    <div class="col-md-7">
                        <h4>Danh sách bài viết Blog</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh</th>
                                        <th>Tiêu đề</th>
                                        <th>Lượt xem</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blogs as $blog): ?>
                                        <tr>
                                            <td><?php echo $blog['id']; ?></td>
                                            <td>
                                                <?php if ($blog['thumbnail']): ?>
                                                    <img src="../<?php echo htmlspecialchars($blog['thumbnail']); ?>" alt="Thumbnail" width="50">
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($blog['title']); ?></strong>
                                                <br><small class="text-muted">/<?php echo $blog['slug']; ?></small>
                                            </td>
                                            <td><?php echo number_format($blog['view_count']); ?></td>
                                            <td><?php echo PageManager::getStatusBadge($blog['status']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-blog" data-bs-toggle="modal" data-bs-target="#blogModal" data-id="<?php echo $blog['id']; ?>">Sửa</button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                                    <input type="hidden" name="_marketing_handler" value="1">
                                                    <input type="hidden" name="action" value="delete_page">
                                                    <input type="hidden" name="page_id" value="<?php echo $blog['id']; ?>">
                                                    <input type="hidden" name="tab" value="blogs">
                                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($blogs)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">Chưa có bài viết nào</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Static Pages Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'pages' ? 'show active' : ''; ?>"
                id="pages"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-5">
                        <h4>Thêm trang tĩnh mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="action" value="add_page">
                            <input type="hidden" name="tab" value="pages">
                            <div class="mb-3">
                                <label for="page_type" class="form-label">Loại trang <span class="text-danger">*</span></label>
                                <select class="form-select" id="page_type" name="page_type" required>
                                    <option value="about">Giới thiệu</option>
                                    <option value="policy">Chính sách</option>
                                    <option value="guide">Hướng dẫn</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="page_title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="page_title" name="page_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="page_content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="page_content" name="page_content" rows="8"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="page_position" class="form-label">Thứ tự hiển thị</label>
                                <input type="number" class="form-control" id="page_position" name="page_position" value="0" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="page_status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="page_status" name="page_status">
                                    <option value="draft">Nháp</option>
                                    <option value="published">Xuất bản</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm trang</button>
                        </form>
                    </div>

                    <div class="col-md-7">
                        <h4>Danh sách trang tĩnh</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Loại</th>
                                        <th>Tiêu đề</th>
                                        <th>Slug</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staticPages as $page): ?>
                                        <tr>
                                            <td><?php echo $page['id']; ?></td>
                                            <td><span class="badge bg-info"><?php echo PageManager::getTypeLabel($page['type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($page['title']); ?></td>
                                            <td><code>/<?php echo $page['slug']; ?></code></td>
                                            <td><?php echo PageManager::getStatusBadge($page['status']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-page" data-bs-toggle="modal" data-bs-target="#pageModal" data-id="<?php echo $page['id']; ?>">Sửa</button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                                    <input type="hidden" name="_marketing_handler" value="1">
                                                    <input type="hidden" name="action" value="delete_page">
                                                    <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                    <input type="hidden" name="tab" value="pages">
                                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($staticPages)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">Chưa có trang nào</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End marketing-content-wrapper -->

    <!-- Modals for editing -->
    <!-- Banner Edit Modal -->
    <div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="editBannerForm">
                        <input type="hidden" name="_marketing_handler" value="1">
                        <input type="hidden" name="tab" value="banners">
                        <input type="hidden" name="action" value="edit_banner">
                        <input type="hidden" name="banner_id" id="edit_banner_id">
                        <div class="mb-3">
                            <label for="edit_banner_title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="edit_banner_title" name="banner_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_banner_description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="edit_banner_description" name="banner_description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_banner_link_url" class="form-label">Liên kết</label>
                            <input type="url" class="form-control" id="edit_banner_link_url" name="banner_link_url">
                        </div>
                        <div class="mb-3">
                            <label for="edit_banner_image" class="form-label">Ảnh Banner hiện tại</label>
                            <div id="current_banner_image"></div>
                        </div>
                        <div class="mb-3">
                            <label for="new_banner_image" class="form-label">Tải ảnh mới (nếu muốn thay đổi)</label>
                            <input type="file" class="form-control" id="new_banner_image" name="banner_image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="edit_banner_position" class="form-label">Vị trí</label>
                            <input type="number" class="form-control" id="edit_banner_position" name="banner_position" value="0" min="0">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_banner_is_active" name="banner_is_active">
                            <label class="form-check-label" for="edit_banner_is_active">Hiển thị</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật Banner</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- News Edit Modal -->
    <div class="modal fade" id="newsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa Tin tức</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="editNewsForm">
                        <input type="hidden" name="_marketing_handler" value="1">
                        <input type="hidden" name="tab" value="news">
                        <input type="hidden" name="action" value="edit_news">
                        <input type="hidden" name="news_id" id="edit_news_id">
                        <div class="mb-3">
                            <label for="edit_news_title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="edit_news_title" name="news_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_news_content" class="form-label">Nội dung</label>
                            <textarea class="form-control" id="edit_news_content" name="news_content" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_news_author" class="form-label">Tác giả</label>
                            <input type="text" class="form-control" id="edit_news_author" name="news_author">
                        </div>
                        <div class="mb-3">
                            <label for="edit_news_image" class="form-label">Ảnh tin tức hiện tại</label>
                            <div id="current_news_image"></div>
                        </div>
                        <div class="mb-3">
                            <label for="new_news_image" class="form-label">Tải ảnh mới (nếu muốn thay đổi)</label>
                            <input type="file" class="form-control" id="new_news_image" name="news_image" accept="image/*">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_news_is_published" name="news_is_published">
                            <label class="form-check-label" for="edit_news_is_published">Xuất bản</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật Tin tức</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotion Edit Modal -->
    <div class="modal fade" id="promotionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa Chương trình ưu đãi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editPromotionForm">
                        <input type="hidden" name="_marketing_handler" value="1">
                        <input type="hidden" name="tab" value="promotions">
                        <input type="hidden" name="action" value="edit_promotion">
                        <input type="hidden" name="promotion_id" id="edit_promotion_id">
                        <div class="mb-3">
                            <label for="edit_promotion_title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="edit_promotion_title" name="promotion_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_promotion_description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="edit_promotion_description" name="promotion_description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_promotion_discount_percent" class="form-label">Phần trăm giảm giá (%)</label>
                            <input type="number" class="form-control" id="edit_promotion_discount_percent" name="promotion_discount_percent" min="0" max="100" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_promotion_start_date" class="form-label">Ngày bắt đầu</label>
                                    <input type="date" class="form-control" id="edit_promotion_start_date" name="promotion_start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_promotion_end_date" class="form-label">Ngày kết thúc</label>
                                    <input type="date" class="form-control" id="edit_promotion_end_date" name="promotion_end_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_promotion_is_active" name="promotion_is_active">
                            <label class="form-check-label" for="edit_promotion_is_active">Kích hoạt</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật Ưu đãi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Blog Edit Modal -->
    <div class="modal fade" id="blogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa bài viết Blog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="editBlogForm">
                        <input type="hidden" name="_marketing_handler" value="1">
                        <input type="hidden" name="tab" value="blogs">
                        <input type="hidden" name="action" value="edit_blog">
                        <input type="hidden" name="page_id" id="edit_blog_id">
                        <div class="mb-3">
                            <label for="edit_blog_title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="edit_blog_title" name="blog_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_blog_slug" class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" id="edit_blog_slug" name="blog_slug">
                        </div>
                        <div class="mb-3">
                            <label for="edit_blog_excerpt" class="form-label">Tóm tắt</label>
                            <textarea class="form-control" id="edit_blog_excerpt" name="blog_excerpt" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_blog_content" class="form-label">Nội dung</label>
                            <textarea class="form-control" id="edit_blog_content" name="blog_content" rows="8"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện hiện tại</label>
                            <div id="current_blog_thumbnail"></div>
                        </div>
                        <div class="mb-3">
                            <label for="new_blog_thumbnail" class="form-label">Tải ảnh mới</label>
                            <input type="file" class="form-control" id="new_blog_thumbnail" name="blog_thumbnail" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="edit_blog_status" class="form-label">Trạng thái</label>
                            <select class="form-select" id="edit_blog_status" name="blog_status">
                                <option value="draft">Nháp</option>
                                <option value="published">Xuất bản</option>
                                <option value="hidden">Ẩn</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật bài viết</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Edit Modal -->
    <div class="modal fade" id="pageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa trang tĩnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editPageForm">
                        <input type="hidden" name="_marketing_handler" value="1">
                        <input type="hidden" name="tab" value="pages">
                        <input type="hidden" name="action" value="edit_page">
                        <input type="hidden" name="page_id" id="edit_page_id">
                        <div class="mb-3">
                            <label for="edit_page_type" class="form-label">Loại trang</label>
                            <select class="form-select" id="edit_page_type" name="page_type">
                                <option value="about">Giới thiệu</option>
                                <option value="policy">Chính sách</option>
                                <option value="guide">Hướng dẫn</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_page_title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="edit_page_title" name="page_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_page_slug" class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" id="edit_page_slug" name="page_slug">
                        </div>
                        <div class="mb-3">
                            <label for="edit_page_content" class="form-label">Nội dung</label>
                            <textarea class="form-control" id="edit_page_content" name="page_content" rows="10"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_page_position" class="form-label">Thứ tự hiển thị</label>
                            <input type="number" class="form-control" id="edit_page_position" name="page_position" value="0" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit_page_status" class="form-label">Trạng thái</label>
                            <select class="form-select" id="edit_page_status" name="page_status">
                                <option value="draft">Nháp</option>
                                <option value="published">Xuất bản</option>
                                <option value="hidden">Ẩn</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật trang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Banner edit modal - Load old data via AJAX
        document.querySelectorAll('.edit-banner').forEach(button => {
            button.addEventListener('click', function() {
                const bannerId = this.getAttribute('data-id');
                document.getElementById('edit_banner_id').value = bannerId;

                // Fetch banner data
                fetch('./elements_LQA/madmin/api_get_banner.php?id=' + bannerId)
                    .then(response => response.json())
                    .then(data => {
                        // Fill form with old data
                        document.getElementById('edit_banner_title').value = data.title || '';
                        document.getElementById('edit_banner_description').value = data.description || '';
                        document.getElementById('edit_banner_link_url').value = data.link_url || '';
                        document.getElementById('edit_banner_position').value = data.position || 0;
                        document.getElementById('edit_banner_is_active').checked = data.is_active == 1;

                        // Show current image
                        if (data.image_url) {
                            document.getElementById('current_banner_image').innerHTML =
                                '<img src="' + data.image_url + '" alt="Current Banner" style="max-width: 200px; max-height: 100px;">';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading banner data:', error);
                        alert('Không thể tải dữ liệu banner');
                    });
            });
        });

        // News edit modal - Load old data via AJAX
        document.querySelectorAll('.edit-news').forEach(button => {
            button.addEventListener('click', function() {
                const newsId = this.getAttribute('data-id');
                document.getElementById('edit_news_id').value = newsId;

                // Fetch news data
                fetch('./elements_LQA/madmin/api_get_news.php?id=' + newsId)
                    .then(response => response.json())
                    .then(data => {
                        // Fill form with old data
                        document.getElementById('edit_news_title').value = data.title || '';
                        document.getElementById('edit_news_content').value = data.content || '';
                        document.getElementById('edit_news_author').value = data.author || 'Admin';
                        document.getElementById('edit_news_is_published').checked = data.is_published == 1;
                    })
                    .catch(error => {
                        console.error('Error loading news data:', error);
                        alert('Không thể tải dữ liệu tin tức');
                    });
            });
        });

        // Promotion edit modal - Load old data via AJAX
        document.querySelectorAll('.edit-promotion').forEach(button => {
            button.addEventListener('click', function() {
                const promotionId = this.getAttribute('data-id');
                document.getElementById('edit_promotion_id').value = promotionId;

                // Fetch promotion data
                fetch('./elements_LQA/madmin/api_get_promotion.php?id=' + promotionId)
                    .then(response => response.json())
                    .then(data => {
                        // Fill form with old data
                        document.getElementById('edit_promotion_title').value = data.title || '';
                        document.getElementById('edit_promotion_description').value = data.description || '';
                        document.getElementById('edit_promotion_discount_percent').value = data.discount_percent || 0;
                        document.getElementById('edit_promotion_start_date').value = data.start_date || '';
                        document.getElementById('edit_promotion_end_date').value = data.end_date || '';
                        document.getElementById('edit_promotion_is_active').checked = data.is_active == 1;
                    })
                    .catch(error => {
                        console.error('Error loading promotion data:', error);
                        alert('Không thể tải dữ liệu ưu đãi');
                    });
            });
        });

        // Blog edit modal - Load old data via AJAX
        document.querySelectorAll('.edit-blog').forEach(button => {
            button.addEventListener('click', function() {
                const blogId = this.getAttribute('data-id');
                document.getElementById('edit_blog_id').value = blogId;

                fetch('./elements_LQA/madmin/api_get_page.php?id=' + blogId)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('edit_blog_title').value = data.title || '';
                        document.getElementById('edit_blog_slug').value = data.slug || '';
                        document.getElementById('edit_blog_excerpt').value = data.excerpt || '';
                        document.getElementById('edit_blog_content').value = data.content || '';
                        document.getElementById('edit_blog_status').value = data.status || 'draft';
                        
                        if (data.thumbnail) {
                            document.getElementById('current_blog_thumbnail').innerHTML =
                                '<img src="../' + data.thumbnail + '" alt="Thumbnail" style="max-width: 200px; max-height: 100px;">';
                        } else {
                            document.getElementById('current_blog_thumbnail').innerHTML = '<span class="text-muted">Chưa có ảnh</span>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading blog data:', error);
                        alert('Không thể tải dữ liệu bài viết');
                    });
            });
        });

        // Page edit modal - Load old data via AJAX
        document.querySelectorAll('.edit-page').forEach(button => {
            button.addEventListener('click', function() {
                const pageId = this.getAttribute('data-id');
                document.getElementById('edit_page_id').value = pageId;

                fetch('./elements_LQA/madmin/api_get_page.php?id=' + pageId)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('edit_page_type').value = data.type || 'about';
                        document.getElementById('edit_page_title').value = data.title || '';
                        document.getElementById('edit_page_slug').value = data.slug || '';
                        document.getElementById('edit_page_content').value = data.content || '';
                        document.getElementById('edit_page_position').value = data.position || 0;
                        document.getElementById('edit_page_status').value = data.status || 'draft';
                    })
                    .catch(error => {
                        console.error('Error loading page data:', error);
                        alert('Không thể tải dữ liệu trang');
                    });
            });
        });

        // Auto scroll to highlighted banner row when page loads
        window.addEventListener('load', function() {
            // Tìm tất cả row highlight (table-success)
            const highlightedRows = document.querySelectorAll('tr.table-success');
            if (highlightedRows.length > 0) {
                const firstHighlighted = highlightedRows[0];
                // Scroll vào view
                firstHighlighted.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Animate highlight effect
                firstHighlighted.style.transition = 'background-color 0.3s ease';
                setTimeout(() => {
                    firstHighlighted.style.backgroundColor = '#d4edda';
                }, 100);

                // Remove highlight sau 3 giây
                setTimeout(() => {
                    firstHighlighted.style.backgroundColor = '';
                    firstHighlighted.classList.remove('table-success');
                }, 3000);
            }
        });
    </script>
    
    <!-- Bootstrap JS (nếu chưa có trong layout chính) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>