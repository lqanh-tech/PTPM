<?php

require_once __DIR__ . '/elements_LQA/mod/database.php';
require_once __DIR__ . '/elements_LQA/mod/FeaturedProductsCls.php';
require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';

SessionManager::start();

if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    header('Location: userLogin.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$featuredMgr = new FeaturedProducts();

$message = '';
$messageType = 'success';
$tab = $_GET['tab'] ?? 'featured';

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
                
            case 'add_promotion':
                $idhanghoa = intval($_POST['idhanghoa']);
                $giakhuyenmai = floatval($_POST['giakhuyenmai']);
                
                $stmt = $db->prepare("SELECT giathamkhao FROM hanghoa WHERE idhanghoa = ?");
                $stmt->execute([$idhanghoa]);
                $product = $stmt->fetch(PDO::FETCH_OBJ);
                
                if (!$product) {
                    throw new Exception("Không tìm thấy sản phẩm");
                }
                
                if ($giakhuyenmai >= $product->giathamkhao) {
                    throw new Exception("Giá khuyến mãi phải nhỏ hơn giá gốc!");
                }
                
                $stmt = $db->prepare("UPDATE hanghoa SET giakhuyenmai = ? WHERE idhanghoa = ?");
                $stmt->execute([$giakhuyenmai, $idhanghoa]);
                
                $discountPercent = round((($product->giathamkhao - $giakhuyenmai) / $product->giathamkhao) * 100);
                $message = "✅ Đã thêm khuyến mãi -{$discountPercent}%. Giá gốc được giữ nguyên.";
                break;
                
            case 'remove_promotion':
                $idhanghoa = intval($_POST['idhanghoa']);
                
                $stmt = $db->prepare("UPDATE hanghoa SET giakhuyenmai = NULL WHERE idhanghoa = ?");
                $stmt->execute([$idhanghoa]);
                
                $message = "✅ Đã xóa khuyến mãi. Giá gốc được giữ nguyên.";
                break;
        }
    } catch (Exception $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
        $messageType = 'error';
    }
}

if ($tab == 'featured' || $tab == 'dashboard') {

    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.created_at, h.is_featured,
            th.tenTH as ten_thuonghieu,
            (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ma_san_pham = h.idhanghoa) as total_sold,
            h.view_count,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.is_featured = 1
            ORDER BY h.created_at DESC";
} elseif ($tab == 'new') {

    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.created_at, h.is_featured,
            th.tenTH as ten_thuonghieu,
            h.view_count,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY h.created_at DESC";
} elseif ($tab == 'promotion') {

    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.created_at, h.is_featured,
            th.tenTH as ten_thuonghieu,
            ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao) * 100) as discount_percent,
            h.hinhanh as image
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.giakhuyenmai IS NOT NULL AND h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
            ORDER BY discount_percent DESC, h.created_at DESC";
}

$stmt = $db->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý & Khuyến Mãi Sản Phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; padding: 30px; border-radius: 10px; margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .page-header h2 { font-size: 32px; margin-bottom: 10px; font-weight: 700; }
        .page-header p { opacity: 0.95; font-size: 15px; }
        
        .nav-tabs { border-bottom: 2px solid #e0e0e0; margin-bottom: 30px; }
        .nav-tabs .nav-link {
            color: #666; font-weight: 600; border: none; padding: 15px 25px;
            transition: all 0.3s; border-radius: 8px 8px 0 0;
        }
        .nav-tabs .nav-link:hover { background: #f8f9fa; color: #667eea; }
        .nav-tabs .nav-link.active {
            color: #667eea; background: #fff; border-bottom: 3px solid #667eea;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        
        .product-card {
            background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); transition: all 0.3s;
            border: 1px solid #e8e8e8;
        }
        .product-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12); transform: translateY(-2px);
        }
        .product-image {
            width: 100px; height: 100px; object-fit: cover; border-radius: 10px;
            border: 2px solid #f0f0f0;
        }
        
        .badge-featured { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .badge-new { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .badge-sale { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #333; font-weight: 700; }
        
        .alert-price-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
            border-left: 5px solid #ffc107; padding: 20px; margin-bottom: 25px;
            border-radius: 8px; box-shadow: 0 2px 10px rgba(255, 193, 7, 0.2);
        }
        .alert-price-warning h6 {
            color: #856404; font-weight: 700; margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-price-warning ul { margin-bottom: 0; color: #856404; }
        .alert-price-warning code {
            background: #fff; padding: 2px 6px; border-radius: 4px;
            color: #d63384; font-weight: 600;
        }
        
        .btn { border-radius: 8px; font-weight: 600; padding: 10px 20px; transition: all 0.3s; }
        .btn-primary { background: #667eea; border: none; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-success { background: #27ae60; border: none; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; border: none; }
        .btn-danger:hover { background: #c0392b; }
        
        .form-control, .form-select {
            border-radius: 8px; border: 2px solid #e0e0e0; padding: 10px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 5px solid #2196f3; padding: 20px; margin-bottom: 25px;
            border-radius: 8px; box-shadow: 0 2px 10px rgba(33, 150, 243, 0.2);
        }
        .info-box h6 {
            color: #1976d2; font-weight: 700; margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .info-box ul { margin-left: 20px; color: #0d47a1; margin-bottom: 0; }
        .info-box li { margin-bottom: 8px; line-height: 1.6; }
        
        .modal-content { border-radius: 12px; border: none; }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; border-radius: 12px 12px 0 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="page-header">
            <h2><i class="fas fa-gem"></i> Quản Lý & Khuyến Mãi Sản Phẩm</h2>
            <p>Quản lý sản phẩm nổi bật, mới, khuyến mãi và thống kê thông minh - Tất cả trong một</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType == 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'featured' || $tab == 'dashboard' ? 'active' : '' ?>" href="?req=quanLySanPhamDacBiet&tab=featured">
                    <i class="fas fa-star"></i> Sản Phẩm Nổi Bật
                    <span class="badge bg-secondary ms-2"><?= ($tab == 'featured' || $tab == 'dashboard') ? count($products) : '' ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'new' ? 'active' : '' ?>" href="?req=quanLySanPhamDacBiet&tab=new">
                    <i class="fas fa-sparkles"></i> Sản Phẩm Mới
                    <span class="badge bg-secondary ms-2"><?= $tab == 'new' ? count($products) : '' ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'promotion' ? 'active' : '' ?>" href="?req=quanLySanPhamDacBiet&tab=promotion">
                    <i class="fas fa-tags"></i> Khuyến Mãi
                    <span class="badge bg-secondary ms-2"><?= $tab == 'promotion' ? count($products) : '' ?></span>
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <?php if ($tab == 'featured' || $tab == 'dashboard'): ?>
                <!-- SẢN PHẨM NỔI BẬT -->
                
                <!-- Ghi chú điều kiện sản phẩm nổi bật -->
                <div class="info-box" style="background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); border-left-color: #ff9800;">
                    <h6 style="color: #e65100;"><i class="fas fa-info-circle"></i> Điều Kiện Sản Phẩm Nổi Bật Hiện Tại</h6>
                    <p style="color: #bf360c; margin-bottom: 10px;">Sản phẩm được đánh dấu nổi bật (<code>is_featured = 1</code>) sẽ hiển thị ưu tiên trên trang chủ.</p>
                    <ul style="color: #bf360c;">
                        <li><strong>Cách đánh dấu:</strong> Admin đánh dấu thủ công bằng cách click nút "Đánh dấu nổi bật" ở trang chi tiết sản phẩm hoặc danh sách sản phẩm</li>
                        <li><strong>Hiển thị:</strong> Sản phẩm nổi bật sẽ xuất hiện trong section "Sản Phẩm Nổi Bật" trên trang chủ</li>
                        <li><strong>Số lượng:</strong> Không giới hạn số lượng sản phẩm nổi bật</li>
                        <li><strong>Bỏ đánh dấu:</strong> Click nút "Bỏ đánh dấu" bên dưới để xóa sản phẩm khỏi danh sách nổi bật</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb"></i> 
                    <strong>Mẹo:</strong> Sản phẩm nổi bật sẽ hiển thị ưu tiên trên trang chủ. Bạn có thể đánh dấu thủ công từ trang quản lý sản phẩm.
                </div>
                
                <div class="row">
                    <?php if (count($products) == 0): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Chưa có sản phẩm nổi bật. Vui lòng vào tab <strong>Dashboard</strong> để tự động đánh dấu.
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($products as $product): 
                        $hasDiscount = $product->giakhuyenmai && $product->giakhuyenmai > 0 && $product->giakhuyenmai < $product->giathamkhao;
                        $discountPercent = $hasDiscount ? round((($product->giathamkhao - $product->giakhuyenmai) / $product->giathamkhao) * 100) : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="product-card d-flex gap-3">
                            <img src="<?= $product->image ? '../mhanghoa/displayImage.php?id=' . $product->image : '../img_LQA/no-image.png' ?>" 
                                 class="product-image" alt="">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product->tenhanghoa) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></small>
                                    </div>
                                    <span class="badge badge-featured">
                                        <i class="fas fa-star"></i> Nổi bật
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <?php if ($hasDiscount): ?>
                                        <strong class="text-danger" style="font-size: 18px;"><?= number_format($product->giakhuyenmai) ?>đ</strong>
                                        <small class="text-muted text-decoration-line-through ms-2"><?= number_format($product->giathamkhao) ?>đ</small>
                                        <span class="badge badge-sale ms-2">-<?= $discountPercent ?>%</span>
                                    <?php else: ?>
                                        <strong class="text-danger"><?= number_format($product->giathamkhao) ?>đ</strong>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2 text-muted small mb-2">
                                    <span><i class="fas fa-shopping-cart"></i> Đã bán: <?= $product->total_sold ?? 0 ?></span>
                                    <span><i class="fas fa-eye"></i> Xem: <?= $product->view_count ?? 0 ?></span>
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
                    <strong>Tự động:</strong> Sản phẩm được tạo trong 30 ngày gần đây sẽ tự động hiển thị badge "Mới" trên trang mua hàng.
                </div>
                
                <div class="row">
                    <?php if (count($products) == 0): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Không có sản phẩm mới trong 30 ngày gần đây.
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($products as $product): 
                        $daysOld = floor((time() - strtotime($product->created_at)) / 86400);
                        $hasDiscount = $product->giakhuyenmai && $product->giakhuyenmai > 0 && $product->giakhuyenmai < $product->giathamkhao;
                        $discountPercent = $hasDiscount ? round((($product->giathamkhao - $product->giakhuyenmai) / $product->giathamkhao) * 100) : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="product-card d-flex gap-3">
                            <img src="<?= $product->image ? '../mhanghoa/displayImage.php?id=' . $product->image : '../img_LQA/no-image.png' ?>" 
                                 class="product-image" alt="">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product->tenhanghoa) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></small>
                                    </div>
                                    <span class="badge badge-new">
                                        <i class="fas fa-sparkles"></i> Mới
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <?php if ($hasDiscount): ?>
                                        <strong class="text-danger" style="font-size: 18px;"><?= number_format($product->giakhuyenmai) ?>đ</strong>
                                        <small class="text-muted text-decoration-line-through ms-2"><?= number_format($product->giathamkhao) ?>đ</small>
                                        <span class="badge badge-sale ms-2">-<?= $discountPercent ?>%</span>
                                    <?php else: ?>
                                        <strong class="text-danger"><?= number_format($product->giathamkhao) ?>đ</strong>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-clock"></i> <?= $daysOld ?> ngày trước
                                    <span class="ms-2">(<?= date('d/m/Y', strtotime($product->created_at)) ?>)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- KHUYẾN MÃI -->
                <div class="alert-price-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Lưu Ý Quan Trọng Về Giá</h6>
                    <ul>
                        <li><strong>Giá gốc</strong> (<code>giathamkhao</code>): KHÔNG BAO GIỜ thay đổi khi có khuyến mãi</li>
                        <li><strong>Giá khuyến mãi</strong> (<code>giakhuyenmai</code>): Giá sau khi giảm, phải nhỏ hơn giá gốc</li>
                        <li><strong>Hiển thị:</strong> Nếu có <code>giakhuyenmai</code>, trang mua hàng sẽ hiển thị giá KM + gạch ngang giá gốc</li>
                        <li><strong>Xóa KM:</strong> Set <code>giakhuyenmai = NULL</code>, giá trở về <code>giathamkhao</code></li>
                        <li><strong>Ảnh hưởng:</strong> Giá KM ảnh hưởng đến giỏ hàng, thanh toán, thống kê doanh thu</li>
                    </ul>
                </div>
                
                <div class="mb-4 d-flex gap-2">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
                        <i class="fas fa-plus"></i> Thêm Khuyến Mãi Mới
                    </button>
                    <a href="index.php?req=coupon" class="btn btn-outline-success">
                        <i class="fas fa-ticket-alt"></i> Quản lý mã Coupon
                    </a>
                </div>
                
                <!-- Thông tin về Coupon -->
                <div class="info-box mb-4" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left-color: #28a745;">
                    <h6 style="color: #155724;"><i class="fas fa-ticket-alt"></i> Mã Giảm Giá (Coupon)</h6>
                    <p style="color: #155724; margin-bottom: 10px;">
                        Ngoài khuyến mãi trực tiếp trên sản phẩm, bạn có thể tạo <strong>mã giảm giá (coupon)</strong> để khách hàng nhập khi thanh toán.
                    </p>
                    <ul style="color: #155724; margin-bottom: 0;">
                        <li><strong>Giảm %:</strong> Giảm theo phần trăm đơn hàng (có thể giới hạn tối đa)</li>
                        <li><strong>Giảm tiền:</strong> Giảm số tiền cố định</li>
                        <li><strong>Điều kiện:</strong> Đơn tối thiểu, số lượt sử dụng, thời gian hiệu lực</li>
                    </ul>
                </div>
                
                <div class="row">
                    <?php if (count($products) == 0): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Chưa Có Sản Phẩm Khuyến Mãi</h6>
                                <p class="mb-2">Để thêm khuyến mãi:</p>
                                <ol class="mb-0">
                                    <li>Click nút <strong>"Thêm Khuyến Mãi Mới"</strong> ở trên</li>
                                    <li>Chọn sản phẩm và nhập giá khuyến mãi</li>
                                    <li>Giá khuyến mãi phải nhỏ hơn giá gốc</li>
                                    <li>Sản phẩm sẽ hiển thị badge giảm giá trên trang mua hàng</li>
                                </ol>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6">
                        <div class="product-card d-flex gap-3">
                            <div class="position-relative">
                                <img src="<?= $product->image ? '../mhanghoa/displayImage.php?id=' . $product->image : '../img_LQA/no-image.png' ?>" 
                                     class="product-image" alt="">
                                <span class="badge bg-danger position-absolute top-0 start-0 m-1" style="font-size: 14px;">
                                    -<?= $product->discount_percent ?>%
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($product->tenhanghoa) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></small>
                                    </div>
                                    <span class="badge badge-sale">
                                        <i class="fas fa-fire"></i> Sale
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <div>
                                        <strong class="text-danger" style="font-size: 20px;">
                                            <?= number_format($product->giakhuyenmai) ?>đ
                                        </strong>
                                    </div>
                                    <div>
                                        <small class="text-muted text-decoration-line-through">
                                            Giá gốc: <?= number_format($product->giathamkhao) ?>đ
                                        </small>
                                    </div>
                                    <div>
                                        <small class="text-success fw-bold">
                                            Tiết kiệm: <?= number_format($product->giathamkhao - $product->giakhuyenmai) ?>đ
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editPromotion(<?= $product->idhanghoa ?>, '<?= htmlspecialchars($product->tenhanghoa) ?>', <?= $product->giathamkhao ?>, <?= $product->giakhuyenmai ?>)">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa khuyến mãi?\n\nGiá sẽ trở về giá gốc: <?= number_format($product->giathamkhao) ?>đ')">
                                        <input type="hidden" name="action" value="remove_promotion">
                                        <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i> Xóa KM
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Thêm Khuyến Mãi -->
    <div class="modal fade" id="addPromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tags"></i> Thêm Khuyến Mãi Mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addPromotionForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_promotion">
                        
                        <div class="mb-3">
                            <label class="form-label">Chọn sản phẩm:</label>
                            <select name="idhanghoa" class="form-select" required onchange="updatePriceInfo(this)">
                                <option value="">-- Chọn sản phẩm --</option>
                                <?php
                                $allProducts = $db->query("SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa ORDER BY tenhanghoa")->fetchAll(PDO::FETCH_OBJ);
                                foreach ($allProducts as $p):
                                ?>
                                <option value="<?= $p->idhanghoa ?>" data-price="<?= $p->giathamkhao ?>">
                                    <?= htmlspecialchars($p->tenhanghoa) ?> - <?= number_format($p->giathamkhao) ?>đ
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Giá gốc hiện tại:</label>
                            <input type="text" class="form-control" id="currentPrice" readonly value="Chọn sản phẩm để xem giá">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Giá khuyến mãi (VNĐ):</label>
                            <input type="number" name="giakhuyenmai" class="form-control" required 
                                   placeholder="Nhập giá sau khi giảm" min="1000" step="1000" id="salePrice" oninput="calculateDiscount()">
                            <small class="text-muted">Phải nhỏ hơn giá gốc</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">% Giảm giá:</label>
                            <input type="text" class="form-control" id="discountPercent" readonly value="0%">
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Lưu ý:</strong> Giá gốc sẽ KHÔNG thay đổi. Chỉ thêm giá khuyến mãi.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Thêm Khuyến Mãi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Sửa Khuyến Mãi -->
    <div class="modal fade" id="editPromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa Khuyến Mãi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPromotionForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_promotion">
                        <input type="hidden" name="idhanghoa" id="editProductId">
                        
                        <div class="mb-3">
                            <label class="form-label">Sản phẩm:</label>
                            <input type="text" class="form-control" id="editProductName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Giá gốc:</label>
                            <input type="text" class="form-control" id="editCurrentPrice" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Giá khuyến mãi mới (VNĐ):</label>
                            <input type="number" name="giakhuyenmai" class="form-control" required 
                                   id="editSalePrice" min="1000" step="1000" oninput="calculateEditDiscount()">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">% Giảm giá:</label>
                            <input type="text" class="form-control" id="editDiscountPercent" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu Thay Đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updatePriceInfo(select) {
        const option = select.options[select.selectedIndex];
        const price = option.getAttribute('data-price');
        if (price) {
            document.getElementById('currentPrice').value = new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
            document.getElementById('salePrice').setAttribute('max', price);
        }
    }
    
    function calculateDiscount() {
        const select = document.querySelector('select[name="idhanghoa"]');
        const option = select.options[select.selectedIndex];
        const originalPrice = parseFloat(option.getAttribute('data-price'));
        const salePrice = parseFloat(document.getElementById('salePrice').value);
        
        if (originalPrice && salePrice && salePrice < originalPrice) {
            const discount = Math.round(((originalPrice - salePrice) / originalPrice) * 100);
            document.getElementById('discountPercent').value = discount + '%';
        } else {
            document.getElementById('discountPercent').value = '0%';
        }
    }
    
    function editPromotion(id, name, originalPrice, currentSalePrice) {
        document.getElementById('editProductId').value = id;
        document.getElementById('editProductName').value = name;
        document.getElementById('editCurrentPrice').value = new Intl.NumberFormat('vi-VN').format(originalPrice) + ' VNĐ';
        document.getElementById('editSalePrice').value = currentSalePrice;
        document.getElementById('editSalePrice').setAttribute('data-original', originalPrice);
        calculateEditDiscount();
        
        const modal = new bootstrap.Modal(document.getElementById('editPromotionModal'));
        modal.show();
    }
    
    function calculateEditDiscount() {
        const originalPrice = parseFloat(document.getElementById('editSalePrice').getAttribute('data-original'));
        const salePrice = parseFloat(document.getElementById('editSalePrice').value);
        
        if (originalPrice && salePrice && salePrice < originalPrice) {
            const discount = Math.round(((originalPrice - salePrice) / originalPrice) * 100);
            document.getElementById('editDiscountPercent').value = discount + '%';
        } else {
            document.getElementById('editDiscountPercent').value = '0%';
        }
    }
    </script>
</body>
</html>
