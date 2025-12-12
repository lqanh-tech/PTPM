<?php
/**
 * Trang hiển thị nội dung tĩnh (Giới thiệu, Chính sách, Hướng dẫn, Blog)
 */
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/PageManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/giohangCls.php';

SessionManager::start();

$pageManager = new PageManager();
$giohang = new GioHang();
$cartItemCount = $giohang->getCartItemCount();

// Lấy slug từ URL
$slug = $_GET['slug'] ?? '';
$page = null;

if ($slug) {
    $page = $pageManager->getPageBySlug($slug);
}

// Nếu không tìm thấy trang
if (!$page) {
    header('HTTP/1.0 404 Not Found');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/lequocanh/">
    <title><?php echo $page ? htmlspecialchars($page['meta_title'] ?: $page['title']) : 'Không tìm thấy trang'; ?> - Cửa Hàng Điện Thoại</title>
    <?php if ($page && $page['meta_description']): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
    <?php endif; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public_files/mycss.css">
    
    <style>
        .page-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .page-content h3, .page-content h4 {
            color: #333;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .page-content h3:first-child, .page-content h4:first-child {
            margin-top: 0;
        }
        .page-content ul, .page-content ol {
            padding-left: 20px;
        }
        .page-content li {
            margin-bottom: 8px;
        }
        .page-content table {
            width: 100%;
            margin: 15px 0;
        }
        .page-content table th, .page-content table td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        .page-content table th {
            background: #f8f9fa;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .page-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .page-meta {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 10px;
        }
        .breadcrumb-item a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .breadcrumb-item.active {
            color: white;
        }
        .sidebar-menu {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .sidebar-menu h5 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .sidebar-menu .nav-link {
            color: #555;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active {
            color: #667eea;
        }
        .sidebar-menu .nav-link i {
            width: 20px;
        }
        .blog-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        .not-found {
            text-align: center;
            padding: 60px 20px;
        }
        .not-found i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>
                Cửa Hàng Điện Thoại
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="page.php?slug=gioi-thieu">Giới thiệu</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Chính sách</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="page.php?slug=chinh-sach-bao-hanh">Chính sách bảo hành</a></li>
                            <li><a class="dropdown-item" href="page.php?slug=chinh-sach-doi-tra">Chính sách đổi trả</a></li>
                            <li><a class="dropdown-item" href="page.php?slug=chinh-sach-van-chuyen">Chính sách vận chuyển</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <?php if (isset($_SESSION['USER'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="administrator/elements_LQA/mgiohang/giohangView.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge bg-danger"><?php echo $cartItemCount; ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($page): ?>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                    <?php if ($page['type'] == 'blog'): ?>
                    <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
                    <?php elseif ($page['type'] == 'policy'): ?>
                    <li class="breadcrumb-item">Chính sách</li>
                    <?php elseif ($page['type'] == 'guide'): ?>
                    <li class="breadcrumb-item">Hướng dẫn</li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($page['title']); ?></li>
                </ol>
            </nav>
            <h1><?php echo htmlspecialchars($page['title']); ?></h1>
            <div class="page-meta">
                <i class="far fa-calendar-alt me-1"></i> <?php echo date('d/m/Y', strtotime($page['created_at'])); ?>
                <span class="mx-2">|</span>
                <i class="far fa-eye me-1"></i> <?php echo number_format($page['view_count']); ?> lượt xem
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($page['thumbnail']): ?>
                <img src="<?php echo htmlspecialchars($page['thumbnail']); ?>" alt="<?php echo htmlspecialchars($page['title']); ?>" class="blog-thumbnail mb-4">
                <?php endif; ?>
                
                <div class="page-content">
                    <?php echo $page['content']; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Sidebar -->
                <div class="sidebar-menu mb-4">
                    <h5><i class="fas fa-info-circle me-2"></i>Giới thiệu</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $slug == 'gioi-thieu' ? 'active' : ''; ?>" href="page.php?slug=gioi-thieu">
                            <i class="fas fa-store me-2"></i>Về chúng tôi
                        </a>
                    </nav>
                </div>
                
                <div class="sidebar-menu mb-4">
                    <h5><i class="fas fa-shield-alt me-2"></i>Chính sách</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $slug == 'chinh-sach-bao-hanh' ? 'active' : ''; ?>" href="page.php?slug=chinh-sach-bao-hanh">
                            <i class="fas fa-tools me-2"></i>Chính sách bảo hành
                        </a>
                        <a class="nav-link <?php echo $slug == 'chinh-sach-doi-tra' ? 'active' : ''; ?>" href="page.php?slug=chinh-sach-doi-tra">
                            <i class="fas fa-exchange-alt me-2"></i>Chính sách đổi trả
                        </a>
                        <a class="nav-link <?php echo $slug == 'chinh-sach-van-chuyen' ? 'active' : ''; ?>" href="page.php?slug=chinh-sach-van-chuyen">
                            <i class="fas fa-truck me-2"></i>Chính sách vận chuyển
                        </a>
                    </nav>
                </div>
                
                <div class="sidebar-menu">
                    <h5><i class="fas fa-book me-2"></i>Hướng dẫn</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $slug == 'huong-dan-mua-hang' ? 'active' : ''; ?>" href="page.php?slug=huong-dan-mua-hang">
                            <i class="fas fa-shopping-bag me-2"></i>Hướng dẫn mua hàng
                        </a>
                        <a class="nav-link <?php echo $slug == 'huong-dan-thanh-toan' ? 'active' : ''; ?>" href="page.php?slug=huong-dan-thanh-toan">
                            <i class="fas fa-credit-card me-2"></i>Hướng dẫn thanh toán
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Not Found -->
    <div class="container mt-5">
        <div class="page-content not-found">
            <i class="fas fa-exclamation-triangle"></i>
            <h2>Không tìm thấy trang</h2>
            <p class="text-muted">Trang bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.</p>
            <a href="index.php" class="btn btn-primary mt-3">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Cửa Hàng Điện Thoại</h5>
                    <p class="text-muted">Chuyên cung cấp điện thoại chính hãng với giá tốt nhất.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 Cửa Hàng Điện Thoại. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
