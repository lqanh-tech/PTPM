<?php

require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/PageManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/giohangCls.php';

SessionManager::start();

$pageManager = new PageManager();
$giohang = new GioHang();
$cartItemCount = $giohang->getCartItemCount();

$blogs = $pageManager->getAllBlogs(true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/lequocanh/">
    <title>Blog - Cửa Hàng Điện Thoại</title>
    <meta name="description" content="Tin tức, bài viết về điện thoại, công nghệ và hướng dẫn sử dụng.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public_files/mycss.css">
    
    <style>
        .blog-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
        }
        .blog-header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        .blog-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .blog-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .blog-card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .blog-card-body {
            padding: 20px;
        }
        .blog-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .blog-card-title a {
            color: inherit;
            text-decoration: none;
        }
        .blog-card-title a:hover {
            color: #667eea;
        }
        .blog-card-excerpt {
            color: #666;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .blog-card-meta {
            font-size: 0.85rem;
            color: #999;
        }
        .blog-card-meta i {
            margin-right: 5px;
        }
        .blog-empty {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 15px;
        }
        .blog-empty i {
            font-size: 4rem;
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
                        <a class="nav-link active" href="blog.php">Blog</a>
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

    <!-- Blog Header -->
    <div class="blog-header">
        <div class="container">
            <h1><i class="fas fa-blog me-3"></i>Blog</h1>
            <p>Tin tức, bài viết về điện thoại và công nghệ</p>
        </div>
    </div>

    <!-- Blog List -->
    <div class="container">
        <?php if (!empty($blogs)): ?>
        <div class="row g-4">
            <?php foreach ($blogs as $blog): ?>
            <div class="col-md-6 col-lg-4">
                <div class="blog-card">
                    <?php if ($blog['thumbnail']): ?>
                    <img src="<?php echo htmlspecialchars($blog['thumbnail']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" class="blog-card-img">
                    <?php else: ?>
                    <div class="blog-card-img bg-secondary d-flex align-items-center justify-content-center">
                        <i class="fas fa-image fa-3x text-white-50"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="blog-card-body">
                        <h3 class="blog-card-title">
                            <a href="page.php?slug=<?php echo htmlspecialchars($blog['slug']); ?>">
                                <?php echo htmlspecialchars($blog['title']); ?>
                            </a>
                        </h3>
                        
                        <?php if ($blog['excerpt']): ?>
                        <p class="blog-card-excerpt"><?php echo htmlspecialchars($blog['excerpt']); ?></p>
                        <?php endif; ?>
                        
                        <div class="blog-card-meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?></span>
                            <span class="ms-3"><i class="far fa-eye"></i> <?php echo number_format($blog['view_count']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="blog-empty">
            <i class="fas fa-newspaper"></i>
            <h3>Chưa có bài viết nào</h3>
            <p class="text-muted">Các bài viết mới sẽ được cập nhật sớm.</p>
            <a href="index.php" class="btn btn-primary mt-3">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
        </div>
        <?php endif; ?>
    </div>

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
