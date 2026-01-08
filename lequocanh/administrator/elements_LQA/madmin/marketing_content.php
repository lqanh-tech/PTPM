<?php

require_once __DIR__ . '/../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

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

$activeTab = $_GET['tab'] ?? 'banners';

$message = '';
$messageSuccess = false;
if (isset($_SESSION['marketing_message'])) {
    $message = $_SESSION['marketing_message'];
    $messageSuccess = $_SESSION['marketing_success'] ?? false;
    unset($_SESSION['marketing_message']);
    unset($_SESSION['marketing_success']);
}

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