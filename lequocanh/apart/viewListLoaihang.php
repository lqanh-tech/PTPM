<?php ob_start(); ?>
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../administrator/elements_LQA/mod/loaihangCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';
require_once __DIR__ . '/../includes/query_builder.php';
require_once __DIR__ . '/../includes/advanced_cache.php';

$hanghoa = new hanghoa();

$hasFilters = isset($_GET['min_price']) || isset($_GET['max_price']) ||
    isset($_GET['colors']) || isset($_GET['sizes']) || isset($_GET['min_rating']);

if ($hasFilters) {
    $filters = [
        'min_price' => isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0,
        'max_price' => isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000,
        'colors' => isset($_GET['colors']) ? explode(',', $_GET['colors']) : [],
        'sizes' => isset($_GET['sizes']) ? explode(',', $_GET['sizes']) : [],
        'category' => isset($_GET['reqView']) ? (int)$_GET['reqView'] : null,
        'min_rating' => isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0
    ];
    $list_hanghoa = $hanghoa->filterProducts($filters);
} elseif (isset($_GET['reqView'])) {
    $idloaihang = $_GET['reqView'];
    
    $list_hanghoa = cache_remember('products_category_' . $idloaihang, 300, function() use ($hanghoa, $idloaihang) {
        return $hanghoa->HanghoaGetbyIdloaihang($idloaihang);
    });
} else {
    $list_hanghoa = cache_remember('all_products', 300, function() use ($hanghoa) {
        return $hanghoa->HanghoaGetAll();
    });
}

$carousel_items = array_slice($list_hanghoa, 0, 5);

?>

<!-- Rating Styles -->
<link rel="stylesheet" href="public_files/rating_styles.css">

<!-- Product Filter Styles -->
<link rel="stylesheet" href="public_files/product_filter.css">

<!-- Carousel kết hợp sản phẩm nổi bật, mới, khuyến mãi và banner quảng cáo -->
<?php include __DIR__ . '/productBannerCarousel.php'; ?>

<!-- Carousel sản phẩm cũ (đã được thay thế) -->
<!--
<div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php
        if (!empty($carousel_items)) {
            foreach ($carousel_items as $index => $item):

                $hinhanh = $hanghoa->GetHinhAnhById($item->hinhanh);
        ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" data-bs-interval="3000">
            <a href="./index.php?reqHanghoa=<?php echo $item->idhanghoa; ?>">
                <?php if ($hinhanh && !empty($hinhanh->duong_dan)): ?>
                <img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $item->hinhanh; ?>"
                    class="d-block" alt="<?php echo $item->tenhanghoa; ?>">
                <?php else: ?>
                <div class="updating-image-container">
                    <img src="./administrator/elements_LQA/img_LQA/no-image.png" alt="Không có hình ảnh">
                </div>
                <?php endif; ?>
            </a>
        </div>
        <?php
            endforeach;
        } else {
            echo '<div class="alert alert-warning">Không có sản phẩm nào để hiển thị</div>';
        }
        ?>
    </div>

    <?php if (count($carousel_items) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
</div>
-->

<!-- Thêm script khởi tạo carousel -->
<script src="administrator/elements_LQA/js_LQA/jscript.js"></script>

<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/NewsManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/PromotionManager.php';

$newsManager = new NewsManager();
$promotionManager = new PromotionManager();

$latestNews = $newsManager->getPublishedNews(3);
$activePromotions = $promotionManager->getActivePromotions();
?>

<!-- News Section -->
<?php if (!empty($latestNews)): ?>
    <div class="news-section my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0">
                <i class="fas fa-newspaper text-primary"></i> Tin tức mới nhất
            </h3>
            <a href="all_news.php" class="btn btn-sm btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-3">
            <?php foreach ($latestNews as $news): ?>
                <div class="col">
                    <div class="card news-card h-100">
                        <?php if ($news['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($news['featured_image']); ?>" class="card-img-top"
                                alt="<?php echo htmlspecialchars($news['title']); ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php
                                $content = strip_tags($news['content']);
                                echo htmlspecialchars(mb_substr($content, 0, 100)) . '...';
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="far fa-user"></i> <?php echo htmlspecialchars($news['author_id'] ?? 'N/A'); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('d/m/Y', strtotime($news['published_date'])); ?>
                                </small>
                            </div>
                            <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-outline-primary">
                                Đọc thêm <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Promotions Section -->
<?php if (!empty($activePromotions)): ?>
    <div class="promotions-section my-4">
        <h3 class="section-title mb-3">
            <i class="fas fa-tags text-danger"></i> Chương trình Ưu đãi
        </h3>
        <div class="row row-cols-1 row-cols-md-3 g-3">
            <?php foreach ($activePromotions as $promo): ?>
                <div class="col">
                    <div class="card promotion-card h-100 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-danger me-2" style="font-size: 18px;">
                                    -<?php echo number_format($promo['discount_percent'], 0); ?>%
                                </span>
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($promo['title']); ?></h5>
                            </div>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars($promo['description']); ?>
                            </p>
                            <div class="text-muted small">
                                <i class="far fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?> -
                                <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<h3 class="section-title my-4">
    <i class="fas fa-mobile-alt text-success"></i> Sản phẩm
</h3>

<style>

    .section-title {
        font-weight: 700;
        color: #333;
        padding-bottom: 10px;
        border-bottom: 3px solid #007bff;
        display: inline-block;
    }

    .promotion-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid #dc3545 !important;
    }

    .promotion-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(220, 53, 69, 0.2);
    }

    .news-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #e0e0e0;
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .news-card .card-img-top {
        transition: transform 0.3s ease;
    }

    .news-card:hover .card-img-top {
        transform: scale(1.05);
        overflow: hidden;
    }

    .product-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .badge-featured {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .badge-new {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .badge-sale {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: #333;
    }

    .discount-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #e74c3c;
        color: #fff;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        z-index: 10;
    }

    .card {
        position: relative;
    }

    .card-img-top {
        height: 250px;
        object-fit: contain;
        padding: 10px;
        background-color: #f8f9fa;
    }
    
    .products-container {
        display: flex !important;
        gap: 30px;
        align-items: stretch !important;
        height: 75vh !important;
        min-height: 500px;
        max-height: 800px;
        margin-bottom: 30px;
    }
    
    .filter-column {
        flex: 0 0 280px !important;
        min-width: 280px;
        height: 100% !important;
        overflow: hidden;
    }
    
    .filter-sidebar {
        height: 100% !important;
        overflow-y: auto !important;
    }
    
    .products-column {
        flex: 1 !important;
        height: 100% !important;
        overflow-y: auto !important;
        padding: 15px;
        background: #fff;
        border-radius: 8px;
    }
    
    .products-column::-webkit-scrollbar,
    .filter-sidebar::-webkit-scrollbar {
        width: 8px;
    }
    
    .products-column::-webkit-scrollbar-thumb,
    .filter-sidebar::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 4px;
    }
    
    .products-column::-webkit-scrollbar-track,
    .filter-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
</style>

<!-- Filter Toggle Button (Mobile) -->
<button class="filter-toggle-btn" id="filterToggleBtn">
    <i class="fas fa-filter"></i>
    Bộ lọc
</button>

<!-- Filter Backdrop (Mobile) -->
<div class="filter-backdrop" id="filterBackdrop"></div>

<!-- Products Layout with Filters -->
<div class="products-container">
    <!-- Filter Sidebar -->
    <div class="filter-column" id="filterColumn">
        <div class="filter-sidebar">
            <!-- Mobile Header -->
            <div class="filter-mobile-header d-lg-none">
                <h4>Bộ lọc sản phẩm</h4>
                <button class="filter-close-btn" id="filterCloseBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <h4 class="d-none d-lg-block">Bộ lọc sản phẩm</h4>

            <!-- Price Filter -->
            <div class="filter-section price-filter">
                <h5><i class="fas fa-dollar-sign"></i> Khoảng giá</h5>
                <div class="price-range-slider">
                    <input type="range" class="price-range-input" id="minPrice"
                        min="0" max="100000000" step="100000" value="0">
                    <input type="range" class="price-range-input" id="maxPrice"
                        min="0" max="100000000" step="100000" value="100000000">
                </div>
                <div class="price-values">
                    <div class="price-value" id="minPriceDisplay">0 ₫</div>
                    <div class="price-value" id="maxPriceDisplay">100,000,000 ₫</div>
                </div>
            </div>

            <!-- Color Filter - Server-side Rendered -->
            <div class="filter-section color-filter">
                <h5><i class="fas fa-palette"></i> Màu sắc</h5>
                <div class="color-options" id="colorFilterContainer">
                    <?php include __DIR__ . '/render_color_filter.php'; ?>
                </div>
            </div>

            <!-- Rating Filter -->
            <div class="filter-section rating-filter">
                <h5><i class="fas fa-star"></i> Đánh giá</h5>
                <div class="rating-options" id="ratingFilterContainer">
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="5" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </span>
                        <span class="rating-text">5 sao</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="4" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">4 sao trở lên</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="3" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">3 sao trở lên</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="2" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">2 sao trở lên</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="1" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">1 sao trở lên</span>
                    </label>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
                <button class="btn-clear-filters">
                    <i class="fas fa-redo"></i> Xóa bộ lọc
                </button>
                <button class="btn-apply-filters d-lg-none">
                    <i class="fas fa-check"></i> Áp dụng
                </button>
            </div>
        </div>
    </div>

    <!-- Products Column -->
    <div class="products-column">
        <!-- Active Filters Display -->
        <div class="active-filters empty" id="activeFilters"></div>

        <!-- Results Count -->
        <div class="results-count">
            Hiển thị <?php echo count($list_hanghoa); ?> sản phẩm
        </div>

        <h3 class="section-title my-4">
            <i class="fas fa-mobile-alt text-success"></i> Sản phẩm
        </h3>

        <div class="row row-cols-1 row-cols-md-3 g-4 product-list-grid">
            <?php foreach ($list_hanghoa as $v):

                $hinhanh = $hanghoa->GetHinhAnhById($v->hinhanh);

                $hasDiscount = false;
                $discountPercent = 0;
                if (isset($v->giakhuyenmai) && $v->giakhuyenmai > 0 && $v->giakhuyenmai < $v->giathamkhao) {
                    $hasDiscount = true;
                    $discountPercent = round((($v->giathamkhao - $v->giakhuyenmai) / $v->giathamkhao) * 100);
                }
            ?>
                <div class="col">
                    <div class="card h-100 position-relative">
                        <?php

                        if ($hasDiscount): ?>
                            <span class="discount-badge">-<?php echo $discountPercent; ?>%</span>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['USER'])): ?>
                        <!-- Nút yêu thích -->
                        <button class="product-wishlist-btn" data-product-id="<?php echo $v->idhanghoa; ?>" 
                                onclick="event.preventDefault(); event.stopPropagation(); Wishlist.toggle(<?php echo $v->idhanghoa; ?>, this)" 
                                title="Thêm vào yêu thích">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>

                        <?php if ($hinhanh && !empty($hinhanh->duong_dan)): ?>
                            <img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $v->hinhanh; ?>"
                                class="card-img-top" alt="<?php echo $v->tenhanghoa; ?>">
                        <?php else: ?>
                            <div class="updating-image-container">
                                <img src="./administrator/elements_LQA/img_LQA/no-image.png" alt="Không có hình ảnh">
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $v->tenhanghoa; ?></h5>

                            <?php

                            ?>
                            <div class="product-code" style="font-size: 11px; color: #6c757d; margin: 4px 0 8px 0;">
                                Mã: SP-<?php echo str_pad($v->idhanghoa, 5, '0', STR_PAD_LEFT); ?>
                            </div>

                            <?php

                            $ratingInfo = $hanghoa->getAverageRating($v->idhanghoa);
                            $avgRating = $ratingInfo['average'];
                            $reviewCount = $ratingInfo['count'];

                            if ($reviewCount > 0):
                                $highRating = $avgRating >= 4.5 ? 'high-rating' : '';
                            ?>
                                <div class="product-rating <?php echo $highRating; ?>" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px;">
                                    <?php

                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= floor($avgRating)):
                                    ?>
                                            <i class="fas fa-star" style="color: #ffc107;"></i>
                                        <?php elseif ($i == ceil($avgRating) && ($avgRating - floor($avgRating) >= 0.5)): ?>
                                            <i class="fas fa-star-half-alt" style="color: #ffc107;"></i>
                                        <?php else: ?>
                                            <i class="far fa-star" style="color: #ffc107;"></i>
                                    <?php endif;
                                    endfor; ?>
                                    <span style="font-weight: 600; color: #333; margin-left: 4px;"><?php echo number_format($avgRating, 1); ?></span>
                                    <span style="color: #6c757d; font-size: 12px;">(<?php echo $reviewCount; ?>)</span>
                                </div>
                            <?php else: ?>
                                <div class="product-rating" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px; opacity: 0.5;">
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <span style="color: #999; font-size: 11px; margin-left: 4px;">Chưa có đánh giá</span>
                                </div>
                            <?php endif; ?>

                            <p class="card-text text-danger fw-bold">
                                <?php
                                if ($hasDiscount) {
                                    echo '<span style="font-size: 20px;">' . number_format($v->giakhuyenmai, 0, ',', '.') . ' VNĐ</span><br>';
                                    echo '<span style="font-size: 14px; color: #999; text-decoration: line-through;">' . number_format($v->giathamkhao, 0, ',', '.') . ' VNĐ</span>';
                                } else {
                                    echo number_format($v->giathamkhao, 0, ',', '.') . ' VNĐ';
                                }
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="./index.php?reqHanghoa=<?php echo $v->idhanghoa; ?>" class="btn btn-outline-primary">
                                    Xem chi tiết
                                </a>
                                <!-- Thêm checkbox để so sánh -->
                                <div class="form-check">
                                    <input class="form-check-input compare-checkbox" type="checkbox"
                                        value="<?php echo $v->idhanghoa; ?>" id="compare_<?php echo $v->idhanghoa; ?>">
                                    <label class="form-check-label" for="compare_<?php echo $v->idhanghoa; ?>">
                                        So sánh
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Thêm nút so sánh cố định ở góc màn hình -->
        <div id="compareButton" class="position-fixed bottom-0 end-0 mb-4 me-4" style="display: none;">
            <button class="btn btn-primary" onclick="compareProducts()">
                So sánh (<span id="compareCount">0</span>)
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const compareCheckboxes = document.querySelectorAll('.compare-checkbox');
                const compareButton = document.getElementById('compareButton');
                const compareCount = document.getElementById('compareCount');
                let selectedProducts = [];

                compareCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                compareButton.style.display = 'none';
                compareCount.textContent = '0';

                compareCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            if (selectedProducts.length >= 3) {
                                alert('Chỉ có thể so sánh tối đa 3 sản phẩm!');
                                this.checked = false;
                                return;
                            }
                            selectedProducts.push(this.value);
                        } else {
                            selectedProducts = selectedProducts.filter(id => id !== this.value);
                        }

                        compareCount.textContent = selectedProducts.length;
                        compareButton.style.display = selectedProducts.length > 1 ? 'block' : 'none';
                    });
                });
            });

            function compareProducts() {
                const selectedProducts = Array.from(document.querySelectorAll('.compare-checkbox:checked'))
                    .map(checkbox => checkbox.value);

                if (selectedProducts.length < 2) {
                    alert('Vui lòng chọn ít nhất 2 sản phẩm để so sánh!');
                    return;
                }

                window.location.href = `sosanh.php?products=${selectedProducts.join(',')}`;
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('cartAdded')) {

                alert('Đã thêm sản phẩm vào giỏ hàng!');

                const newUrl = window.location.href.replace(/[&?]cartAdded=1/, '');
                window.history.replaceState({}, document.title, newUrl);
            }

        </script>

        <!-- Product Filter Script -->
        <script src="public_files/product_filter.js"></script>

    </div> <!-- End products-column -->
</div> <!-- End products-container -->

<?php ob_end_flush(); ?>