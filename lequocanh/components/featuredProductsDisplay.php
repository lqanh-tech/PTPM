<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

class FeaturedProductsDisplay
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getFeaturedProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                       th.tenthuonghieu,
                       lh.tenloaihang,
                       (SELECT hinhanh FROM hinhanh WHERE idhanghoa = h.idhanghoa LIMIT 1) as image
                FROM hanghoa h
                LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
                LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
                WHERE h.is_featured = 1 
                  AND h.trangthai = 1
                ORDER BY h.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getNewProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                       th.tenthuonghieu,
                       lh.tenloaihang,
                       (SELECT hinhanh FROM hinhanh WHERE idhanghoa = h.idhanghoa LIMIT 1) as image
                FROM hanghoa h
                LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
                LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
                WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND h.trangthai = 1
                ORDER BY h.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPromotionProducts($limit = 8)
    {
        $sql = "SELECT h.*, 
                       th.tenthuonghieu,
                       lh.tenloaihang,
                       (SELECT hinhanh FROM hinhanh WHERE idhanghoa = h.idhanghoa LIMIT 1) as image,
                       ROUND(((h.giagoc - h.giakhuyenmai) / h.giagoc * 100), 0) as discount_percent
                FROM hanghoa h
                LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
                LEFT JOIN loaihang lh ON h.idloaihang = lh.idloaihang
                WHERE h.giakhuyenmai IS NOT NULL 
                  AND h.giakhuyenmai > 0
                  AND h.giakhuyenmai < h.giagoc
                  AND h.trangthai = 1
                ORDER BY discount_percent DESC, h.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

$featuredDisplay = new FeaturedProductsDisplay();
$featuredProducts = $featuredDisplay->getFeaturedProducts(8);
$newProducts = $featuredDisplay->getNewProducts(8);
$promotionProducts = $featuredDisplay->getPromotionProducts(8);
?>

<style>
    .featured-section {
        padding: 40px 0;
        background: #f8f9fa;
    }

    .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-header h2 {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
        position: relative;
        display: inline-block;
    }

    .section-header h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .section-header p {
        color: #666;
        font-size: 16px;
        margin-top: 20px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        padding: 0 20px;
    }

    .product-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .product-image {
        position: relative;
        width: 100%;
        height: 250px;
        overflow: hidden;
        background: #f5f5f5;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 10px;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.1);
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

    .product-info {
        padding: 20px;
    }

    .product-brand {
        color: #999;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .product-name {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        height: 40px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .product-price {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .price-current {
        font-size: 20px;
        font-weight: 700;
        color: #e74c3c;
    }

    .price-original {
        font-size: 14px;
        color: #999;
        text-decoration: line-through;
    }

    .product-actions {
        display: flex;
        gap: 10px;
    }

    .btn-view {
        flex: 1;
        padding: 10px;
        background: #667eea;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-view:hover {
        background: #5568d3;
    }

    .btn-cart {
        padding: 10px 15px;
        background: #fff;
        border: 2px solid #667eea;
        color: #667eea;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-cart:hover {
        background: #667eea;
        color: #fff;
    }

    .btn-cart.btn-disabled {
        background: #ddd;
        border-color: #999;
        color: #999;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .btn-cart.btn-disabled:hover {
        background: #ddd;
        color: #999;
    }

    .badge-status {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 8px 12px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: bold;
        z-index: 10;
    }

    .badge-status.product-out-of-stock {
        background: #e74c3c;
        color: #fff;
    }

    .badge-status.product-discontinued {
        background: #f39c12;
        color: #fff;
    }

    .no-products {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .no-products i {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .section-featured .section-header h2 {
        color: #667eea;
    }

    .section-new .section-header h2 {
        color: #f5576c;
    }

    .section-sale .section-header h2 {
        color: #fa709a;
    }
</style>

<!-- SẢN PHẨM NỔI BẬT -->
<div class="featured-section section-featured">
    <div class="section-header">
        <h2><i class="fas fa-star"></i> Sản Phẩm Nổi Bật</h2>
        <p>Những sản phẩm được yêu thích và bán chạy nhất</p>
    </div>

    <?php if (count($featuredProducts) > 0): ?>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card" onclick="window.location.href='?p=chitietsanpham&id=<?= $product->idhanghoa ?>'">
                    <div class="product-image">
                        <span class="product-badge badge-featured">
                            <i class="fas fa-star"></i> Nổi bật
                        </span>
                        <?php

                        $statusClass = '';
                        $statusText = '';
                        if (isset($product->trang_thai)) {
                            if ($product->trang_thai == 2) {
                                $statusClass = 'product-discontinued';
                                $statusText = 'Ngừng bán';
                            } elseif ($product->trang_thai == 3) {
                                $statusClass = 'product-out-of-stock';
                                $statusText = 'Hết hàng';
                            }
                        }
                        ?>
                        <?php if ($statusClass && $statusText): ?>
                            <span class="product-badge badge-status <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($product->image): ?>
                            <img src="<?= htmlspecialchars($product->image) ?>" alt="<?= htmlspecialchars($product->tenhanghoa) ?>">
                        <?php else: ?>
                            <img src="assets/images/no-image.png" alt="No image">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-brand"><?= htmlspecialchars($product->tenthuonghieu ?? 'N/A') ?></div>
                        <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                        <div class="product-price">
                            <?php if ($product->giakhuyenmai && $product->giakhuyenmai < $product->giagoc): ?>
                                <span class="price-current"><?= number_format($product->giakhuyenmai) ?>đ</span>
                                <span class="price-original"><?= number_format($product->giagoc) ?>đ</span>
                            <?php else: ?>
                                <span class="price-current"><?= number_format($product->giagoc) ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="btn-view">Xem chi tiết</button>
                            <?php
                            $isUnavailable = isset($product->trang_thai) && ($product->trang_thai == 2 || $product->trang_thai == 3);
                            ?>
                            <button class="btn-cart <?= $isUnavailable ? 'btn-disabled' : '' ?>"
                                onclick="event.stopPropagation(); <?= $isUnavailable ? "alert('Sản phẩm này không thể mua'); return false;" : "addToCart(" . $product->idhanghoa . ")" ?>"
                                <?= $isUnavailable ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open"></i>
            <p>Chưa có sản phẩm nổi bật</p>
        </div>
    <?php endif; ?>
</div>

<!-- SẢN PHẨM MỚI -->
<div class="featured-section section-new" style="background: #fff;">
    <div class="section-header">
        <h2><i class="fas fa-sparkles"></i> Sản Phẩm Mới</h2>
        <p>Những sản phẩm mới nhất vừa ra mắt</p>
    </div>

    <?php if (count($newProducts) > 0): ?>
        <div class="products-grid">
            <?php foreach ($newProducts as $product): ?>
                <div class="product-card" onclick="window.location.href='?p=chitietsanpham&id=<?= $product->idhanghoa ?>'">
                    <div class="product-image">
                        <span class="product-badge badge-new">
                            <i class="fas fa-sparkles"></i> Mới
                        </span>
                        <?php if ($product->image): ?>
                            <img src="<?= htmlspecialchars($product->image) ?>" alt="<?= htmlspecialchars($product->tenhanghoa) ?>">
                        <?php else: ?>
                            <img src="assets/images/no-image.png" alt="No image">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-brand"><?= htmlspecialchars($product->tenthuonghieu ?? 'N/A') ?></div>
                        <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                        <div class="product-price">
                            <?php if ($product->giakhuyenmai && $product->giakhuyenmai < $product->giagoc): ?>
                                <span class="price-current"><?= number_format($product->giakhuyenmai) ?>đ</span>
                                <span class="price-original"><?= number_format($product->giagoc) ?>đ</span>
                            <?php else: ?>
                                <span class="price-current"><?= number_format($product->giagoc) ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="btn-view">Xem chi tiết</button>
                            <button class="btn-cart" onclick="event.stopPropagation(); addToCart(<?= $product->idhanghoa ?>)">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open"></i>
            <p>Chưa có sản phẩm mới</p>
        </div>
    <?php endif; ?>
</div>

<!-- SẢN PHẨM KHUYẾN MÃI -->
<div class="featured-section section-sale">
    <div class="section-header">
        <h2><i class="fas fa-fire"></i> Khuyến Mãi Hot</h2>
        <p>Giảm giá sốc - Số lượng có hạn</p>
    </div>

    <?php if (count($promotionProducts) > 0): ?>
        <div class="products-grid">
            <?php foreach ($promotionProducts as $product): ?>
                <div class="product-card" onclick="window.location.href='?p=chitietsanpham&id=<?= $product->idhanghoa ?>'">
                    <div class="product-image">
                        <span class="discount-badge">-<?= $product->discount_percent ?>%</span>
                        <span class="product-badge badge-sale">
                            <i class="fas fa-fire"></i> Sale
                        </span>
                        <?php if ($product->image): ?>
                            <img src="<?= htmlspecialchars($product->image) ?>" alt="<?= htmlspecialchars($product->tenhanghoa) ?>">
                        <?php else: ?>
                            <img src="assets/images/no-image.png" alt="No image">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-brand"><?= htmlspecialchars($product->tenthuonghieu ?? 'N/A') ?></div>
                        <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                        <div class="product-price">
                            <span class="price-current"><?= number_format($product->giakhuyenmai) ?>đ</span>
                            <span class="price-original"><?= number_format($product->giagoc) ?>đ</span>
                        </div>
                        <div class="product-actions">
                            <button class="btn-view">Xem chi tiết</button>
                            <button class="btn-cart" onclick="event.stopPropagation(); addToCart(<?= $product->idhanghoa ?>)">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open"></i>
            <p>Chưa có sản phẩm khuyến mãi</p>
        </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="administrator/css_LQA/toast-notification.css">
<script src="administrator/js_LQA/toast-notification.js"></script>
<script>
    function addToCart(productId) {

        fetch('?p=giohang&action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'idhanghoa=' + productId + '&soluong=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toast.success('Đã thêm vào giỏ hàng!');

                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.error('Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('Có lỗi xảy ra!');
            });
    }
</script>