<?php
/**
 * Trang Wishlist - Danh sách sản phẩm yêu thích
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

use App\Models\Wishlist;
use App\Models\ProductImage;

if (!isset($_SESSION['USER'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../../userLogin.php');
    exit();
}

$userId = (int) $_SESSION['USER'];
$items = Wishlist::getByUser($userId);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <?= csrf_meta() ?>
    <title>Sản phẩm yêu thích</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../../public_files/mycss.css">
    <link rel="stylesheet" href="../../../public_files/toast-notification.css">
    <link rel="stylesheet" href="../../../public_files/notification.css">
    <style>
        body { background: #f1f3f5; }
        .wishlist-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .wishlist-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }
        .wishlist-header h2 { margin: 0; font-size: 22px; font-weight: 700; }
        .wishlist-header .count-badge {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .wishlist-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
            position: relative;
        }
        .wishlist-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .wishlist-card .card-image {
            position: relative;
            padding-top: 100%;
            overflow: hidden;
            background: #f8f9fa;
        }
        .wishlist-card .card-image img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: contain;
            padding: 15px;
            transition: transform 0.3s;
        }
        .wishlist-card:hover .card-image img {
            transform: scale(1.05);
        }
        
        .wishlist-card .remove-btn {
            position: absolute;
            top: 10px; right: 10px;
            width: 36px; height: 36px;
            background: white;
            border: none;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: all 0.2s;
            z-index: 2;
        }
        .wishlist-card .remove-btn:hover {
            background: #e74c3c;
            color: white;
        }
        .wishlist-card .remove-btn i { color: #e74c3c; font-size: 14px; }
        .wishlist-card .remove-btn:hover i { color: white; }
        
        .wishlist-card .card-body {
            padding: 15px;
        }
        .wishlist-card .product-name {
            font-weight: 600;
            color: #212529;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 1.4;
        }
        .wishlist-card .product-name:hover { color: #3498db; }
        
        .wishlist-card .price-section { margin-top: 8px; }
        .wishlist-card .price-current {
            color: #e74c3c;
            font-weight: 700;
            font-size: 16px;
        }
        .wishlist-card .price-original {
            color: #adb5bd;
            text-decoration: line-through;
            font-size: 13px;
            margin-left: 8px;
        }
        
        .wishlist-card .card-actions {
            padding: 0 15px 15px;
        }
        .wishlist-card .btn-add-cart {
            width: 100%;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .wishlist-card .btn-add-cart:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(52,152,219,0.3);
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }
        .empty-wishlist i {
            font-size: 60px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .empty-wishlist h3 { color: #495057; margin-bottom: 10px; }
        .empty-wishlist p { color: #adb5bd; margin-bottom: 20px; }
        .empty-wishlist .btn-shop {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .empty-wishlist .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52,152,219,0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .wishlist-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .wishlist-card .card-body { padding: 10px; }
            .wishlist-card .product-name { font-size: 12px; }
            .wishlist-card .price-current { font-size: 14px; }
        }
        @media (max-width: 480px) {
            .wishlist-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../components/navbar.php'; ?>
    
    <div class="wishlist-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../../index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active">Sản phẩm yêu thích</li>
            </ol>
        </nav>
        
        <!-- Header -->
        <div class="wishlist-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-heart text-danger me-2"></i>Sản phẩm yêu thích</h2>
                <span class="count-badge"><?= count($items) ?> sản phẩm</span>
            </div>
        </div>
        
        <?php if (empty($items)): ?>
            <!-- Empty State -->
            <div class="empty-wishlist">
                <i class="far fa-heart"></i>
                <h3>Chưa có sản phẩm yêu thích</h3>
                <p>Hãy thêm sản phẩm vào danh sách yêu thích để dễ dàng tìm lại sau!</p>
                <a href="../../../index.php" class="btn-shop">
                    <i class="fas fa-shopping-bag me-2"></i>Khám phá sản phẩm
                </a>
            </div>
        <?php else: ?>
            <!-- Wishlist Grid -->
            <div class="wishlist-grid">
                <?php foreach ($items as $item): ?>
                    <?php
                    $productId = $item['product_id'];
                    $imageSrc = (!empty($item['hinhanh']) && $item['hinhanh'] > 0)
                        ? "../../elements_LQA/mhanghoa/displayImage.php?id=" . $item['hinhanh']
                        : "../../elements_LQA/img_LQA/no-image.png";
                    
                    $hasDiscount = !empty($item['giakhuyenmai']) && $item['giakhuyenmai'] > 0 && $item['giakhuyenmai'] < $item['giathamkhao'];
                    $currentPrice = $hasDiscount ? $item['giakhuyenmai'] : $item['giathamkhao'];
                    ?>
                    <div class="wishlist-card" id="wishlist-item-<?= $productId ?>">
                        <div class="card-image">
                            <a href="../../../index.php?reqHanghoa=<?= $productId ?>">
                                <img src="<?= $imageSrc ?>" 
                                     alt="<?= htmlspecialchars($item['tenhanghoa']) ?>"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='../../elements_LQA/img_LQA/no-image.png';">
                            </a>
                            <button class="remove-btn" onclick="removeFromWishlist(<?= $productId ?>)" title="Xóa khỏi yêu thích">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <a href="../../../index.php?reqHanghoa=<?= $productId ?>" class="product-name">
                                <?= htmlspecialchars($item['tenhanghoa']) ?>
                            </a>
                            <div class="price-section">
                                <span class="price-current"><?= number_format($currentPrice, 0, ',', '.') ?>₫</span>
                                <?php if ($hasDiscount): ?>
                                    <span class="price-original"><?= number_format($item['giathamkhao'], 0, ',', '.') ?>₫</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn-add-cart" onclick="addToCart(<?= $productId ?>)">
                                <i class="fas fa-cart-plus me-1"></i>Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../../../components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public_files/bundle.min.js"></script>
    <script src="../../../public_files/toast-notification.js"></script>
    <script>
        function removeFromWishlist(productId) {
            if (!confirm('Xóa sản phẩm này khỏi danh sách yêu thích?')) return;
            
            fetch('../../../api/wishlist.php?action=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById('wishlist-item-' + productId);
                    if (card) {
                        card.style.transition = 'all 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            card.remove();
                            // Check if empty
                            const grid = document.querySelector('.wishlist-grid');
                            if (grid && grid.children.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                    toast.success('Đã xóa khỏi yêu thích');
                } else {
                    toast.error(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(() => toast.error('Lỗi kết nối'));
        }
        
        function addToCart(productId) {
            toast.info('⏳ Đang thêm vào giỏ hàng...');
            
            fetch('../../../administrator/elements_LQA/mgiohang/giohangAct.php?action=add&productId=' + productId + '&quantity=1', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toast.success('✅ ' + data.message);
                } else {
                    toast.error('❌ ' + data.message);
                }
            })
            .catch(() => toast.error('❌ Có lỗi xảy ra'));
        }
    </script>
<script src="../../../public_files/notification.js"></script>
</body>
</html>