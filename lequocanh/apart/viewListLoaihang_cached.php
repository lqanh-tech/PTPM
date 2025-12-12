<?php
/**
 * viewListLoaihang với Cache - Tối ưu hiệu suất
 */

// Load cache system
require_once __DIR__ . '/../cache/CacheManager.php';
require_once __DIR__ . '/../cache/QueryCache.php';

ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../administrator/elements_LQA/mod/loaihangCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';

$hanghoa = new hanghoa();
$cache = CacheManager::getInstance();

// Check for filter parameters in URL
$hasFilters = isset($_GET['min_price']) || isset($_GET['max_price']) ||
    isset($_GET['colors']) || isset($_GET['sizes']) || isset($_GET['min_rating']);

// Generate cache key based on request
$cacheKey = 'products_' . md5(serialize($_GET) . (isset($_SESSION['USER']) ? '1' : '0'));
$cacheTTL = 180; // 3 phút

// Không cache khi có filters (dynamic content)
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
} else {
    // Sử dụng cache cho danh sách sản phẩm
    $list_hanghoa = $cache->remember($cacheKey, $cacheTTL, function() use ($hanghoa) {
        if (isset($_GET['reqView'])) {
            return $hanghoa->HanghoaGetbyIdloaihang($_GET['reqView']);
        }
        return $hanghoa->HanghoaGetAll();
    });
}

// Cache carousel items
$carousel_items = array_slice($list_hanghoa, 0, 5);
?>

<!-- Rating Styles -->
<link rel="stylesheet" href="public_files/rating_styles.css">
<link rel="stylesheet" href="public_files/product_filter.css">

<!-- Carousel -->
<?php include __DIR__ . '/productBannerCarousel.php'; ?>

<script src="administrator/elements_LQA/js_LQA/jscript.js" defer></script>

<?php
// Cache News và Promotions
require_once __DIR__ . '/../administrator/elements_LQA/mod/NewsManager.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/PromotionManager.php';

$newsManager = new NewsManager();
$promotionManager = new PromotionManager();

// Cache news 10 phút
$latestNews = $cache->remember('latest_news_3', 600, function() use ($newsManager) {
    return $newsManager->getPublishedNews(3);
});

// Cache promotions 5 phút
$activePromotions = $cache->remember('active_promotions', 300, function() use ($promotionManager) {
    return $promotionManager->getActivePromotions();
});
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
                <img src="<?php echo htmlspecialchars($news['featured_image']); ?>" 
                     class="card-img-top" loading="lazy"
                     alt="<?php echo htmlspecialchars($news['title']); ?>" 
                     style="height: 200px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
                    <p class="card-text text-muted small">
                        <?php echo htmlspecialchars(mb_substr(strip_tags($news['content']), 0, 100)) . '...'; ?>
                    </p>
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
                    <p class="card-text text-muted small"><?php echo htmlspecialchars($promo['description']); ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
.section-title { font-weight: 700; color: #333; padding-bottom: 10px; border-bottom: 3px solid #007bff; display: inline-block; }
.promotion-card { transition: transform 0.3s ease; border-left: 4px solid #dc3545 !important; }
.promotion-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(220, 53, 69, 0.2); }
.news-card { transition: transform 0.3s ease; border: 1px solid #e0e0e0; }
.news-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); }
.discount-badge { position: absolute; top: 10px; left: 10px; background: #e74c3c; color: #fff; padding: 8px 12px; border-radius: 8px; font-size: 14px; font-weight: 700; z-index: 10; }
.card-img-top { height: 250px; object-fit: contain; padding: 10px; background-color: #f8f9fa; }
.products-container { display: flex !important; gap: 30px; height: 75vh !important; min-height: 500px; max-height: 800px; margin-bottom: 30px; }
.filter-column { flex: 0 0 280px !important; min-width: 280px; height: 100% !important; }
.products-column { flex: 1 !important; height: 100% !important; overflow-y: auto !important; padding: 15px; background: #fff; border-radius: 8px; }
</style>

<!-- Filter Toggle Button (Mobile) -->
<button class="filter-toggle-btn" id="filterToggleBtn"><i class="fas fa-filter"></i> Bộ lọc</button>
<div class="filter-backdrop" id="filterBackdrop"></div>

<!-- Products Layout -->
<div class="products-container">
    <!-- Filter Sidebar -->
    <div class="filter-column" id="filterColumn">
        <div class="filter-sidebar">
            <h4 class="d-none d-lg-block">Bộ lọc sản phẩm</h4>
            
            <!-- Price Filter -->
            <div class="filter-section price-filter">
                <h5><i class="fas fa-dollar-sign"></i> Khoảng giá</h5>
                <div class="price-range-slider">
                    <input type="range" class="price-range-input" id="minPrice" min="0" max="100000000" step="100000" value="0">
                    <input type="range" class="price-range-input" id="maxPrice" min="0" max="100000000" step="100000" value="100000000">
                </div>
                <div class="price-values">
                    <div class="price-value" id="minPriceDisplay">0 ₫</div>
                    <div class="price-value" id="maxPriceDisplay">100,000,000 ₫</div>
                </div>
            </div>

            <!-- Color Filter -->
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
                    <?php for ($r = 5; $r >= 1; $r--): ?>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="<?php echo $r; ?>" class="rating-checkbox">
                        <span class="rating-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="<?php echo $s <= $r ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </span>
                        <span class="rating-text"><?php echo $r; ?> sao<?php echo $r < 5 ? ' trở lên' : ''; ?></span>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn-clear-filters"><i class="fas fa-redo"></i> Xóa bộ lọc</button>
            </div>
        </div>
    </div>

    <!-- Products Column -->
    <div class="products-column">
        <div class="active-filters empty" id="activeFilters"></div>
        <div class="results-count">Hiển thị <?php echo count($list_hanghoa); ?> sản phẩm</div>

        <h3 class="section-title my-4"><i class="fas fa-mobile-alt text-success"></i> Sản phẩm</h3>

        <div class="row row-cols-1 row-cols-md-3 g-4 product-list-grid">
            <?php foreach ($list_hanghoa as $v):
                $hinhanh = $hanghoa->GetHinhAnhById($v->hinhanh);
                $hasDiscount = isset($v->giakhuyenmai) && $v->giakhuyenmai > 0 && $v->giakhuyenmai < $v->giathamkhao;
                $discountPercent = $hasDiscount ? round((($v->giathamkhao - $v->giakhuyenmai) / $v->giathamkhao) * 100) : 0;
            ?>
            <div class="col">
                <div class="card h-100 position-relative">
                    <?php if ($hasDiscount): ?>
                    <span class="discount-badge">-<?php echo $discountPercent; ?>%</span>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['USER'])): ?>
                    <button class="product-wishlist-btn" data-product-id="<?php echo $v->idhanghoa; ?>" 
                            onclick="event.preventDefault(); Wishlist.toggle(<?php echo $v->idhanghoa; ?>, this)">
                        <i class="far fa-heart"></i>
                    </button>
                    <?php endif; ?>

                    <?php if ($hinhanh && !empty($hinhanh->duong_dan)): ?>
                    <img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $v->hinhanh; ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($v->tenhanghoa); ?>" loading="lazy">
                    <?php else: ?>
                    <img src="./administrator/elements_LQA/img_LQA/no-image.png" class="card-img-top" alt="No image" loading="lazy">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($v->tenhanghoa); ?></h5>
                        
                        <?php
                        // Cache rating per product
                        $ratingKey = 'rating_' . $v->idhanghoa;
                        $ratingInfo = $cache->remember($ratingKey, 600, function() use ($hanghoa, $v) {
                            return $hanghoa->getAverageRating($v->idhanghoa);
                        });
                        ?>
                        
                        <div class="product-rating" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px;">
                            <?php if ($ratingInfo['count'] > 0): ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= floor($ratingInfo['average']) ? 'fas' : 'far'; ?> fa-star" style="color: #ffc107;"></i>
                                <?php endfor; ?>
                                <span style="font-weight: 600;"><?php echo number_format($ratingInfo['average'], 1); ?></span>
                                <span style="color: #6c757d;">(<?php echo $ratingInfo['count']; ?>)</span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 11px;">Chưa có đánh giá</span>
                            <?php endif; ?>
                        </div>

                        <p class="card-text text-danger fw-bold">
                            <?php if ($hasDiscount): ?>
                            <span style="font-size: 20px;"><?php echo number_format($v->giakhuyenmai, 0, ',', '.'); ?> VNĐ</span><br>
                            <span style="font-size: 14px; color: #999; text-decoration: line-through;"><?php echo number_format($v->giathamkhao, 0, ',', '.'); ?> VNĐ</span>
                            <?php else: ?>
                            <?php echo number_format($v->giathamkhao, 0, ',', '.'); ?> VNĐ
                            <?php endif; ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="./index.php?reqHanghoa=<?php echo $v->idhanghoa; ?>" class="btn btn-outline-primary">Xem chi tiết</a>
                            <div class="form-check">
                                <input class="form-check-input compare-checkbox" type="checkbox" value="<?php echo $v->idhanghoa; ?>">
                                <label class="form-check-label">So sánh</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="compareButton" class="position-fixed bottom-0 end-0 mb-4 me-4" style="display: none;">
            <button class="btn btn-primary" onclick="compareProducts()">So sánh (<span id="compareCount">0</span>)</button>
        </div>
    </div>
</div>

<script src="public_files/product_filter.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const compareCheckboxes = document.querySelectorAll('.compare-checkbox');
    const compareButton = document.getElementById('compareButton');
    const compareCount = document.getElementById('compareCount');
    let selectedProducts = [];

    compareCheckboxes.forEach(cb => {
        cb.checked = false;
        cb.addEventListener('change', function() {
            if (this.checked && selectedProducts.length >= 3) {
                alert('Chỉ có thể so sánh tối đa 3 sản phẩm!');
                this.checked = false;
                return;
            }
            selectedProducts = this.checked 
                ? [...selectedProducts, this.value]
                : selectedProducts.filter(id => id !== this.value);
            compareCount.textContent = selectedProducts.length;
            compareButton.style.display = selectedProducts.length > 1 ? 'block' : 'none';
        });
    });
});

function compareProducts() {
    const selected = Array.from(document.querySelectorAll('.compare-checkbox:checked')).map(cb => cb.value);
    if (selected.length < 2) { alert('Vui lòng chọn ít nhất 2 sản phẩm!'); return; }
    window.location.href = 'sosanh.php?products=' + selected.join(',');
}
</script>

<?php ob_end_flush(); ?>
