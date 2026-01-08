<?php

require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/AutoFeaturedCls.php';

$db = Database::getInstance()->getConnection();
$autoFeatured = new AutoFeatured();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'toggle_featured':
                $idhanghoa = intval($_POST['idhanghoa']);
                $currentStatus = intval($_POST['current_status']);
                $newStatus = $currentStatus == 1 ? 0 : 1;
                
                $stmt = $db->prepare("UPDATE hanghoa SET is_featured = ? WHERE idhanghoa = ?");
                $stmt->execute([$newStatus, $idhanghoa]);
                
                $message = $newStatus == 1 ? "✅ Đã đánh dấu nổi bật" : "✅ Đã bỏ đánh dấu nổi bật";
                break;
                
            case 'auto_mark_featured':
                $limit = intval($_POST['limit'] ?? 20);
                $criteria = $_POST['criteria'] ?? 'by_score';
                
                switch ($criteria) {
                    case 'best_sellers':
                        $autoFeatured->autoMarkBestSellers($limit);
                        break;
                    case 'most_viewed':
                        $autoFeatured->autoMarkMostViewed($limit);
                        break;
                    case 'by_score':
                        $autoFeatured->autoMarkByScore($limit);
                        break;
                }
                
                $message = "✅ Đã tự động đánh dấu $limit sản phẩm nổi bật";
                break;
        }
    } catch (Exception $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
        $messageType = 'error';
    }
}

$tab = $_GET['tab'] ?? 'featured';

if ($tab == 'featured') {

    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.created_at, h.is_featured,
            th.tenTH as ten_thuonghieu,
            (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ma_san_pham = h.idhanghoa) as total_sold,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.is_featured = 1
            ORDER BY h.created_at DESC";
} elseif ($tab == 'new') {

    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.created_at, h.is_featured,
            th.tenTH as ten_thuonghieu,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY h.created_at DESC";
} else {

    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.created_at, h.is_featured,
            th.tenTH as ten_thuonghieu,
            0 as discount_percent,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE 1=0
            ORDER BY h.created_at DESC";

}

$stmt = $db->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm Đặc Biệt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            color: #666;
            font-weight: 600;
            border: none;
            padding: 15px 30px;
        }
        
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: transparent;
        }
        
        .tab-featured .nav-link.active { border-bottom-color: #667eea; color: #667eea; }
        .tab-new .nav-link.active { border-bottom-color: #f5576c; color: #f5576c; }
        .tab-sale .nav-link.active { border-bottom-color: #fa709a; color: #fa709a; }
        
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .badge-featured { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .badge-new { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .badge-sale { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #333; }
        
        .auto-mark-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-warning-price {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="page-header">
            <h2><i class="fas fa-star"></i> Quản Lý Sản Phẩm Đặc Biệt</h2>
            <p class="mb-0">Quản lý sản phẩm nổi bật, mới và khuyến mãi</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType == 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item tab-featured">
                <a class="nav-link <?= $tab == 'featured' ? 'active' : '' ?>" href="?req=manageFeatured&tab=featured">
                    <i class="fas fa-star"></i> Sản Phẩm Nổi Bật
                    <span class="badge bg-secondary ms-2"><?= $tab == 'featured' ? count($products) : '' ?></span>
                </a>
            </li>
            <li class="nav-item tab-new">
                <a class="nav-link <?= $tab == 'new' ? 'active' : '' ?>" href="?req=manageFeatured&tab=new">
                    <i class="fas fa-sparkles"></i> Sản Phẩm Mới
                    <span class="badge bg-secondary ms-2"><?= $tab == 'new' ? count($products) : '' ?></span>
                </a>
            </li>
            <li class="nav-item tab-sale">
                <a class="nav-link <?= $tab == 'sale' ? 'active' : '' ?>" href="?req=manageFeatured&tab=sale">
                    <i class="fas fa-fire"></i> Khuyến Mãi
                    <span class="badge bg-secondary ms-2"><?= $tab == 'sale' ? count($products) : '' ?></span>
                </a>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content">
            <?php if ($tab == 'featured'): ?>
                <!-- SẢN PHẨM NỔI BẬT -->
                <div class="auto-mark-section">
                    <h5><i class="fas fa-magic"></i> Tự Động Đánh Dấu</h5>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="auto_mark_featured">
                        <div class="col-md-4">
                            <label class="form-label">Tiêu chí</label>
                            <select name="criteria" class="form-select">
                                <option value="by_score">Điểm tổng hợp (Khuyến nghị)</option>
                                <option value="best_sellers">Bán chạy nhất</option>
                                <option value="most_viewed">Xem nhiều nhất</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Số lượng</label>
                            <input type="number" name="limit" class="form-control" value="20" min="1" max="50">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Áp dụng
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6">
                        <div class="product-card d-flex gap-3">
                            <img src="<?= $product->image ? '../mhanghoa/displayImage.php?id=' . $product->image : '../img_LQA/no-image.png' ?>" 
                                 class="product-image" alt="">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product->tenhanghoa) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product->tenthuonghieu ?? 'N/A') ?></small>
                                    </div>
                                    <span class="badge badge-featured">
                                        <i class="fas fa-star"></i> Nổi bật
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <strong class="text-danger"><?= number_format($product->giathamkhao) ?>đ</strong>
                                    <small class="text-muted ms-2">Đã bán: <?= $product->total_sold ?? 0 ?></small>
                                </div>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="action" value="toggle_featured">
                                    <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                                    <input type="hidden" name="current_status" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times"></i> Bỏ đánh dấu
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($tab == 'new'): ?>
                <!-- SẢN PHẨM MỚI -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Tự động:</strong> Sản phẩm được tạo trong 30 ngày gần đây sẽ tự động hiển thị badge "Mới"
                </div>
                
                <div class="row">
                    <?php foreach ($products as $product): 
                        $daysOld = floor((time() - strtotime($product->created_at)) / 86400);
                    ?>
                    <div class="col-md-6">
                        <div class="product-card d-flex gap-3">
                            <img src="<?= $product->image ? '../mhanghoa/displayImage.php?id=' . $product->image : '../img_LQA/no-image.png' ?>" 
                                 class="product-image" alt="">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product->tenhanghoa) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product->tenthuonghieu ?? 'N/A') ?></small>
                                    </div>
                                    <span class="badge badge-new">
                                        <i class="fas fa-sparkles"></i> Mới
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <strong class="text-danger"><?= number_format($product->giathamkhao) ?>đ</strong>
                                    <small class="text-muted ms-2">
                                        <i class="fas fa-clock"></i> <?= $daysOld ?> ngày trước
                                    </small>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Ngày tạo: <?= date('d/m/Y H:i', strtotime($product->created_at)) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <!-- KHUYẾN MÃI -->
                <div class="alert-warning-price">
                    <h6><i class="fas fa-exclamation-triangle"></i> Lưu Ý Quan Trọng</h6>
                    <ul class="mb-0">
                        <li>Giá khuyến mãi được lưu trong cột <code>giakhuyenmai</code></li>
                        <li>Giá gốc được lưu trong cột <code>giagoc</code></li>
                        <li><strong>KHÔNG</strong> thay đổi <code>giagoc</code> khi có khuyến mãi</li>
                        <li>Khi hết khuyến mãi: Set <code>giakhuyenmai = NULL</code></li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <a href="?req=addPromotion" class="btn btn-success">
                        <i class="fas fa-plus"></i> Thêm Khuyến Mãi Mới
                    </a>
                </div>
                
                <?php if (count($products) == 0): ?>
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Chưa Có Sản Phẩm Khuyến Mãi</h6>
                    <p class="mb-2">Để sử dụng chức năng khuyến mãi, bạn cần:</p>
                    <ol class="mb-2">
                        <li>Thêm cột <code>giakhuyenmai</code> vào bảng <code>hanghoa</code></li>
                        <li>Truy cập phpMyAdmin: <a href="http://localhost:28888" target="_blank">http:
                        <li>Chạy SQL:
                            <pre class="bg-dark text-white p-2 mt-2 mb-2" style="font-size: 12px;">ALTER TABLE hanghoa 
ADD COLUMN giakhuyenmai DECIMAL(15,2) NULL 
AFTER giathamkhao;</pre>
                        </li>
                        <li>Reload trang này</li>
                        <li>Click "Thêm Khuyến Mãi Mới" để bắt đầu</li>
                    </ol>
                    <p class="mb-0">
                        <a href="elements_LQA/msanphamnoibat/SETUP_PROMOTION.md" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-book"></i> Xem Hướng Dẫn Chi Tiết
                        </a>
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6">
                        <div class="product-card d-flex gap-3">
                            <div class="position-relative">
                                <img src="<?= $product->image ? '../mhanghoa/displayImage.php?id=' . $product->image : '../img_LQA/no-image.png' ?>" 
                                     class="product-image" alt="">
                                <span class="badge bg-danger position-absolute top-0 start-0 m-1">
                                    -<?= $product->discount_percent ?>%
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product->tenhanghoa) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product->tenthuonghieu ?? 'N/A') ?></small>
                                    </div>
                                    <span class="badge badge-sale">
                                        <i class="fas fa-fire"></i> Sale
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div>
                                        <strong class="text-danger" style="font-size: 18px;">
                                            <?= number_format($product->giathamkhao) ?>đ
                                        </strong>
                                    </div>
                                    <div>
                                        <small class="text-muted">Chưa có khuyến mãi</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="?req=editPromotion&id=<?= $product->idhanghoa ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <button onclick="removePromotion(<?= $product->idhanghoa ?>)" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times"></i> Xóa KM
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function removePromotion(id) {
        if (confirm('Xóa khuyến mãi cho sản phẩm này?\n\nGiá sẽ trở về giá gốc.')) {
            fetch('?req=removePromotion', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'idhanghoa=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Đã xóa khuyến mãi');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>
