<?php

require_once __DIR__ . '/includes/performance_bootstrap.php';

perf_init([
    'page_cache' => true,
    'page_cache_ttl' => 300,
    'html_minify' => true,
    'lazy_images' => true,
    'critical_css' => true,
    'debug_bar' => true
])->start();

require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/includes/query_builder.php';

$products = DB('hanghoa')
    ->select('idhanghoa', 'tenhanghoa', 'giathamkhao', 'giakhuyenmai', 'hinhanh')
    ->where('trang_thai', 1)
    ->orderBy('idhanghoa', 'DESC')
    ->limit(12)
    ->cache(300)
    ->get();

$categories = cache_remember('all_categories', 600, function() {
    return DB('loaihang')
        ->select('idloaihang', 'tenloaihang')
        ->orderBy('tenloaihang')
        ->get();
});

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang mẫu đã tối ưu - Performance Demo</title>
    
    <?php echo perf_head(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            object-fit: contain;
            background: #f8f9fa;
        }
        .price-old {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
        }
        .price-new {
            color: #e74c3c;
            font-weight: bold;
            font-size: 1.2em;
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php echo Preloader::getProgressBar(); ?>
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-mobile-alt text-primary"></i> Shop Demo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Trang chủ</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Danh mục</a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $cat): ?>
                            <li><a class="dropdown-item" href="#"><?php echo htmlspecialchars($cat->tenloaihang); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="#" class="btn btn-outline-primary me-2">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge bg-danger">0</span>
                    </a>
                    <a href="#" class="btn btn-primary">Đăng nhập</a>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">
                    <i class="fas fa-fire text-danger"></i>
                    Sản phẩm nổi bật
                </h1>
                <p class="text-muted">Trang demo với tất cả tối ưu hóa hiệu suất</p>
            </div>
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
            <?php
                $hasDiscount = $product->giakhuyenmai > 0 && $product->giakhuyenmai < $product->giathamkhao;
                $discountPercent = $hasDiscount ? round((($product->giathamkhao - $product->giakhuyenmai) / $product->giathamkhao) * 100) : 0;
                $imageUrl = './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh;
            ?>
            <div class="col">
                <div class="card product-card h-100 position-relative">
                    <?php if ($hasDiscount): ?>
                    <span class="discount-badge">-<?php echo $discountPercent; ?>%</span>
                    <?php endif; ?>
                    
                    <?php echo lazy_img($imageUrl, $product->tenhanghoa, 'card-img-top product-image', 'medium'); ?>
                    
                    <div class="card-body">
                        <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($product->tenhanghoa); ?>">
                            <?php echo htmlspecialchars($product->tenhanghoa); ?>
                        </h5>
                        
                        <div class="mb-2">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="far fa-star text-warning"></i>
                            <small class="text-muted">(4.0)</small>
                        </div>
                        
                        <div class="mb-3">
                            <?php if ($hasDiscount): ?>
                            <span class="price-old"><?php echo number_format($product->giathamkhao, 0, ',', '.'); ?>₫</span>
                            <br>
                            <span class="price-new"><?php echo number_format($product->giakhuyenmai, 0, ',', '.'); ?>₫</span>
                            <?php else: ?>
                            <span class="price-new"><?php echo number_format($product->giathamkhao, 0, ',', '.'); ?>₫</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white border-top-0">
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-5">
            <div class="col">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tachometer-alt text-success"></i>
                            Performance Metrics
                        </h5>
                        <?php $metrics = perf()->getMetrics(); ?>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="h4 text-primary"><?php echo $metrics['execution_time_ms']; ?>ms</div>
                                <small class="text-muted">Execution Time</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-success"><?php echo $metrics['memory_usage_mb']; ?>MB</div>
                                <small class="text-muted">Memory Usage</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-info"><?php echo $metrics['peak_memory_mb']; ?>MB</div>
                                <small class="text-muted">Peak Memory</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-warning"><?php echo $metrics['cache_stats']['hit_rate']; ?>%</div>
                                <small class="text-muted">Cache Hit Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">© 2024 Shop Demo - Performance Optimized</p>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js" defer></script>
    
    <?php echo perf_footer(); ?>
</body>
</html>
<?php

perf()->end();
