<?php

/**
 * Widget hiển thị carousel kết hợp sản phẩm và banner quảng cáo
 * Mục đích: Tích hợp banner quảng cáo vào carousel như các sản phẩm khác
 */

require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/FeaturedProductsCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/BannerManager.php';

$hanghoa = new hanghoa();
$featuredProducts = new FeaturedProducts();
$bannerManager = new BannerManager();

// Lấy sản phẩm nổi bật, sản phẩm mới và sản phẩm khuyến mãi (ưu tiên hiển thị)
$featuredProductsList = $featuredProducts->getFeaturedProducts(3); // Lấy 3 sản phẩm nổi bật
$newProductsList = $featuredProducts->getNewProducts(3); // Lấy 3 sản phẩm mới
$saleProductsList = $featuredProducts->getSaleProducts(3); // Lấy 3 sản phẩm khuyến mãi
$activeBanners = $bannerManager->getActiveBanners();

// Xác định thời điểm hiện tại để quyết định loại nội dung nào được ưu tiên
$currentTime = time();
$cycleTime = 120; // Mỗi chu kỳ 120 giây (2 phút)
$timeSlot = floor(($currentTime % $cycleTime) / ($cycleTime / 4)); // Chia thành 4 slot: 0=featured, 1=new, 2=sale, 3=banner

// Kết hợp sản phẩm và banner vào một mảng chung để hiển thị trong carousel
$carouselItems = [];

// Dựa trên thời điểm hiện tại, ưu tiên loại nội dung tương ứng
if ($timeSlot == 0) { // Ưu tiên sản phẩm nổi bật (0-30s)
    // Thêm sản phẩm nổi bật vào mảng carousel (ưu tiên đầu tiên)
    foreach ($featuredProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 1, // Đánh dấu là sản phẩm nổi bật
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm mới vào mảng carousel (thứ 2)
    foreach ($newProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 1, // Đánh dấu là sản phẩm mới
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm khuyến mãi vào mảng carousel (thứ 3)
    foreach ($saleProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 1, // Đánh dấu là sản phẩm khuyến mãi
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm banner vào mảng carousel (cuối cùng)
    foreach ($activeBanners as $banner) {
        $carouselItems[] = [
            'type' => 'banner',
            'id' => $banner['id'],
            'title' => $banner['title'],
            'image' => $banner['image_url'],
            'link' => $banner['link_url'],
            'description' => $banner['description'],
            'position' => $banner['position']
        ];
    }
} elseif ($timeSlot == 1) { // Ưu tiên sản phẩm mới (30-60s)
    // Thêm sản phẩm mới vào mảng carousel (ưu tiên đầu tiên)
    foreach ($newProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 1, // Đánh dấu là sản phẩm mới
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm nổi bật vào mảng carousel (thứ 2)
    foreach ($featuredProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 1, // Đánh dấu là sản phẩm nổi bật
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm khuyến mãi vào mảng carousel (thứ 3)
    foreach ($saleProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 1, // Đánh dấu là sản phẩm khuyến mãi
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm banner vào mảng carousel (cuối cùng)
    foreach ($activeBanners as $banner) {
        $carouselItems[] = [
            'type' => 'banner',
            'id' => $banner['id'],
            'title' => $banner['title'],
            'image' => $banner['image_url'],
            'link' => $banner['link_url'],
            'description' => $banner['description'],
            'position' => $banner['position']
        ];
    }
} elseif ($timeSlot == 2) { // Ưu tiên sản phẩm khuyến mãi (60-90s)
    // Thêm sản phẩm khuyến mãi vào mảng carousel (ưu tiên đầu tiên)
    foreach ($saleProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 1, // Đánh dấu là sản phẩm khuyến mãi
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm nổi bật vào mảng carousel (thứ 2)
    foreach ($featuredProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 1, // Đánh dấu là sản phẩm nổi bật
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm mới vào mảng carousel (thứ 3)
    foreach ($newProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 1, // Đánh dấu là sản phẩm mới
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm banner vào mảng carousel (cuối cùng)
    foreach ($activeBanners as $banner) {
        $carouselItems[] = [
            'type' => 'banner',
            'id' => $banner['id'],
            'title' => $banner['title'],
            'image' => $banner['image_url'],
            'link' => $banner['link_url'],
            'description' => $banner['description'],
            'position' => $banner['position']
        ];
    }
} else { // Ưu tiên banner quảng cáo (90-120s)
    // Thêm banner vào mảng carousel (ưu tiên đầu tiên)
    foreach ($activeBanners as $banner) {
        $carouselItems[] = [
            'type' => 'banner',
            'id' => $banner['id'],
            'title' => $banner['title'],
            'image' => $banner['image_url'],
            'link' => $banner['link_url'],
            'description' => $banner['description'],
            'position' => $banner['position']
        ];
    }
    // Thêm sản phẩm nổi bật vào mảng carousel (thứ 2)
    foreach ($featuredProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 1, // Đánh dấu là sản phẩm nổi bật
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm mới vào mảng carousel (thứ 3)
    foreach ($newProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 1, // Đánh dấu là sản phẩm mới
            'is_sale' => $product->is_sale ?? 0,
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
    // Thêm sản phẩm khuyến mãi vào mảng carousel (cuối cùng)
    foreach ($saleProductsList as $product) {
        $hinhanh = $hanghoa->GetHinhAnhById($product->hinhanh);
        $imageUrl = ($hinhanh && !empty($hinhanh->duong_dan))
            ? './administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $product->hinhanh
            : './administrator/elements_LQA/img_LQA/no-image.png';

        $carouselItems[] = [
            'type' => 'product',
            'id' => $product->idhanghoa,
            'title' => $product->tenhanghoa,
            'image' => $imageUrl,
            'price' => $product->gia_hien_tai ?? $product->giathamkhao,
            'discount_price' => $product->giakhuyenmai ?? null,
            'description' => $product->mota ?? '',
            'is_featured' => $product->is_featured ?? 0,
            'is_new' => $product->is_new ?? 0,
            'is_sale' => $product->is_sale ?? 1, // Đánh dấu là sản phẩm khuyến mãi
            'discount_percent' => $product->discount_percent ?? 0
        ];
    }
}

// Trộn ngẫu nhiên các mục trong carousel để banner và sản phẩm xen kẽ
shuffle($carouselItems);

// Giới hạn số lượng mục trong carousel
$carouselItems = array_slice($carouselItems, 0, 12);
?>

<div id="productBannerCarousel" class="carousel slide mb-4" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
        <?php for ($i = 0; $i < count($carouselItems); $i++): ?>
            <button type="button" data-bs-target="#productBannerCarousel" data-bs-slide-to="<?php echo $i; ?>"
                <?php echo $i == 0 ? 'class="active"' : ''; ?> aria-label="Slide <?php echo $i + 1; ?>"></button>
        <?php endfor; ?>
    </div>

    <div class="carousel-inner">
        <?php foreach ($carouselItems as $index => $item): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <?php if ($item['type'] === 'product'): ?>
                    <!-- Sản phẩm -->
                    <div
                        style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; border-radius: 10px; height: 400px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; position: relative;">
                        <a href="./index.php?reqHanghoa=<?php echo $item['id']; ?>"
                            style="text-decoration: none; color: inherit; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>"
                                style="max-height: 250px; max-width: 100%; object-fit: contain; margin-bottom: 15px;">
                            <h5 style="font-weight: bold; margin: 10px 0 5px; color: #333; text-align: center;">
                                <?php echo htmlspecialchars($item['title']); ?></h5>

                            <?php if ($item['discount_price'] && $item['discount_price'] < $item['price']): ?>
                                <div>
                                    <span class="text-danger fw-bold" style="font-size: 18px;">
                                        <?php echo number_format($item['discount_price'], 0, ',', '.') . ' ₫'; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted text-decoration-line-through">
                                        <?php echo number_format($item['price'], 0, ',', '.') . ' ₫'; ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="text-danger fw-bold" style="font-size: 18px;">
                                    <?php echo number_format($item['price'], 0, ',', '.') . ' ₫'; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php elseif ($item['type'] === 'banner'): ?>
                    <!-- Banner quảng cáo -->
                    <a href="<?php echo $item['link'] ?: '#'; ?>" style="display: block;">
                        <img src="<?php echo $item['image']; ?>" class="d-block w-100"
                            alt="<?php echo htmlspecialchars($item['title']); ?>" style="max-height: 400px; object-fit: cover;">
                    </a>
                    <div class="carousel-caption d-none d-md-block">
                        <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#productBannerCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#productBannerCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<style>
    #productBannerCarousel {
        margin: 20px 0;
    }

    .carousel-item img {
        width: 100%;
        height: 400px;
        object-fit: cover;
    }

    .carousel-caption {
        background: rgba(0, 0, 0, 0.5);
        border-radius: 8px;
        padding: 15px;
        bottom: 30px;
    }

    .carousel-indicators [data-bs-target] {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo carousel
        var carousel = document.getElementById('productBannerCarousel');
        if (carousel) {
            var bsCarousel = new bootstrap.Carousel(carousel, {
                interval: 5000,
                wrap: true
            });
        }
    });
</script>