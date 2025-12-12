<?php
/**
 * Dashboard Sản Phẩm: Nổi Bật, Mới, Khuyến Mãi
 * Hiển thị 3 danh sách chính cho trang chủ
 */

require_once __DIR__ . '/elements_LQA/mod/database.php';
require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    header('Location: userLogin.php');
    exit();
}

$db = Database::getInstance()->getConnection();

/**
 * LOGIC PHÂN LOẠI SẢN PHẨM:
 * 
 * 1. SẢN PHẨM NỔI BẬT (is_featured = 1):
 *    - Được đánh dấu bởi admin hoặc tự động
 *    - Dựa trên: doanh số cao + lượt xem nhiều + đánh giá tốt
 *    - Điểm tổng hợp: 40% doanh số + 30% lượt xem + 20% mới + 10% khuyến mãi
 * 
 * 2. SẢN PHẨM MỚI:
 *    - Sản phẩm được thêm trong vòng 30 ngày gần đây
 *    - Sắp xếp theo ngày tạo mới nhất
 * 
 * 3. SẢN PHẨM KHUYẾN MÃI:
 *    - is_sale = 1 (đang giảm giá)
 *    - Có giá khuyến mãi < giá gốc
 *    - Sắp xếp theo % giảm giá cao nhất
 */

// 1. Lấy sản phẩm nổi bật
$sqlFeatured = "SELECT 
                h.*,
                t.tenTH as ten_thuonghieu,
                l.tenloaihang as ten_loai,
                COALESCE(SUM(ct.so_luong), 0) as total_sold,
                COALESCE(h.view_count, 0) as view_count,
                -- Tính điểm tổng hợp
                (
                    COALESCE(SUM(ct.so_luong), 0) * 0.4 +
                    (COALESCE(h.view_count, 0) * 0.3) +
                    CASE WHEN h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 100 * 0.2 ELSE 0 END +
                    CASE WHEN h.is_sale = 1 THEN 50 * 0.1 ELSE 0 END
                ) as featured_score
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN loaihang l ON h.idloaihang = l.idloaihang
                LEFT JOIN chi_tiet_don_hang ct ON h.idhanghoa = ct.ma_san_pham
                LEFT JOIN don_hang dh ON ct.ma_don_hang = dh.id 
                    AND dh.trang_thai_thanh_toan IN ('paid', 'completed')
                WHERE h.is_featured = 1 AND h.trangthai = 1
                GROUP BY h.idhanghoa
                ORDER BY featured_score DESC
                LIMIT 20";

$stmtFeatured = $db->query($sqlFeatured);
$featuredProducts = $stmtFeatured->fetchAll(PDO::FETCH_OBJ);

// 2. Lấy sản phẩm mới (30 ngày gần đây)
$sqlNew = "SELECT 
            h.*,
            t.tenTH as ten_thuonghieu,
            l.tenloaihang as ten_loai,
            DATEDIFF(NOW(), h.created_at) as days_old
            FROM hanghoa h
            LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
            LEFT JOIN loaihang l ON h.idloaihang = l.idloaihang
            WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND h.trangthai = 1
            ORDER BY h.created_at DESC
            LIMIT 20";

$stmtNew = $db->query($sqlNew);
$newProducts = $stmtNew->fetchAll(PDO::FETCH_OBJ);

// 3. Lấy sản phẩm khuyến mãi
$sqlSale = "SELECT 
            h.*,
            t.tenTH as ten_thuonghieu,
            l.tenloaihang as ten_loai,
            -- Tính % giảm giá
            CASE 
                WHEN h.giakhuyenmai > 0 AND h.giakhuyenmai < h.giathamkhao
                THEN ROUND(((h.giathamkhao - h.giakhuyenmai) / h.giathamkhao) * 100, 0)
                ELSE 0
            END as discount_percent,
            h.giathamkhao - h.giakhuyenmai as discount_amount
            FROM hanghoa h
            LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
            LEFT JOIN loaihang l ON h.idloaihang = l.idloaihang
            WHERE h.is_sale = 1 
            AND h.giakhuyenmai > 0 
            AND h.giakhuyenmai < h.giathamkhao
            AND h.trangthai = 1
            ORDER BY discount_percent DESC
            LIMIT 20";

$stmtSale = $db->query($sqlSale);
$saleProducts = $stmtSale->fetchAll(PDO::FETCH_OBJ);

// Thống kê tổng quan
$stats = [
    'total_featured' => count($featuredProducts),
    'total_new' => count($newProducts),
    'total_sale' => count($saleProducts)
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sản Phẩm - Nổi Bật, Mới, Khuyến Mãi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .stat-icon.featured {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }
        
        .stat-icon.new {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
        }
        
        .stat-icon.sale {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #fff;
        }
        
        .stat-info h3 {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .stat-info .number {
            color: #667eea;
            font-size: 32px;
            font-weight: bold;
        }
        
        .section {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .section-header .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .section-header.featured .icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }
        
        .section-header.new .icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
        }
        
        .section-header.sale .icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #fff;
        }
        
        .section-header h2 {
            color: #333;
            font-size: 24px;
            flex: 1;
        }
        
        .section-header .count {
            background: #667eea;
            color: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background: #fff;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f8f8;
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
        }
        
        .badge-featured {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .badge-new {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .badge-sale {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 48px;
        }
        
        .product-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 12px;
            color: #999;
        }
        
        .product-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .price-current {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .price-original {
            font-size: 14px;
            color: #999;
            text-decoration: line-through;
        }
        
        .discount-badge {
            background: #e74c3c;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .product-stats {
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: #666;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-item i {
            color: #667eea;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state p {
            font-size: 18px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .info-box h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-box ul {
            list-style: none;
        }
        
        .info-box li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> Dashboard Sản Phẩm Trang Chủ</h1>
            <p>Quản lý 3 danh sách chính: Sản phẩm nổi bật, Sản phẩm mới, Sản phẩm khuyến mãi</p>
        </div>
        
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Logic Phân Loại Sản Phẩm</h3>
            <ul>
                <li><strong>Sản phẩm nổi bật:</strong> Điểm tổng hợp = 40% doanh số + 30% lượt xem + 20% mới + 10% khuyến mãi</li>
                <li><strong>Sản phẩm mới:</strong> Được thêm trong vòng 30 ngày gần đây</li>
                <li><strong>Sản phẩm khuyến mãi:</strong> Đang giảm giá (is_sale = 1) và có giá khuyến mãi < giá gốc</li>
            </ul>
        </div>
        
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon featured">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3>Sản Phẩm Nổi Bật</h3>
                    <div class="number"><?= $stats['total_featured'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon new">
                    <i class="fas fa-sparkles"></i>
                </div>
                <div class="stat-info">
                    <h3>Sản Phẩm Mới</h3>
                    <div class="number"><?= $stats['total_new'] ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon sale">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-info">
                    <h3>Sản Phẩm Khuyến Mãi</h3>
                    <div class="number"><?= $stats['total_sale'] ?></div>
                </div>
            </div>
        </div>
        
        <!-- 1. SẢN PHẨM NỔI BẬT -->
        <div class="section">
            <div class="section-header featured">
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
                <h2>Sản Phẩm Nổi Bật</h2>
                <span class="count"><?= count($featuredProducts) ?> sản phẩm</span>
            </div>
            
            <?php if (empty($featuredProducts)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <p>Chưa có sản phẩm nổi bật. Hãy đánh dấu sản phẩm từ trang quản lý.</p>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card">
                    <span class="product-badge badge-featured">
                        <i class="fas fa-star"></i> Nổi bật
                    </span>
                    <img src="<?= htmlspecialchars($product->hinhanh ?? 'placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($product->tenhanghoa) ?>" 
                         class="product-image">
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                        <div class="product-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($product->ten_loai ?? 'N/A') ?></span>
                            <span><i class="fas fa-copyright"></i> <?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></span>
                        </div>
                        <div class="product-price">
                            <span class="price-current"><?= number_format($product->giathamkhao) ?>đ</span>
                            <?php if ($product->is_sale == 1 && $product->giakhuyenmai > 0): ?>
                            <span class="price-original"><?= number_format($product->giathamkhao) ?>đ</span>
                            <span class="discount-badge">
                                -<?= round((($product->giathamkhao - $product->giakhuyenmai) / $product->giathamkhao) * 100) ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="product-stats">
                            <span class="stat-item">
                                <i class="fas fa-shopping-cart"></i>
                                <?= number_format($product->total_sold) ?> đã bán
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-eye"></i>
                                <?= number_format($product->view_count) ?> lượt xem
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 2. SẢN PHẨM MỚI -->
        <div class="section">
            <div class="section-header new">
                <div class="icon">
                    <i class="fas fa-sparkles"></i>
                </div>
                <h2>Sản Phẩm Mới</h2>
                <span class="count"><?= count($newProducts) ?> sản phẩm</span>
            </div>
            
            <?php if (empty($newProducts)): ?>
            <div class="empty-state">
                <i class="fas fa-sparkles"></i>
                <p>Chưa có sản phẩm mới trong 30 ngày gần đây.</p>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($newProducts as $product): ?>
                <div class="product-card">
                    <span class="product-badge badge-new">
                        <i class="fas fa-sparkles"></i> Mới <?= $product->days_old ?> ngày
                    </span>
                    <img src="<?= htmlspecialchars($product->hinhanh ?? 'placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($product->tenhanghoa) ?>" 
                         class="product-image">
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                        <div class="product-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($product->ten_loai ?? 'N/A') ?></span>
                            <span><i class="fas fa-copyright"></i> <?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></span>
                        </div>
                        <div class="product-price">
                            <?php if ($product->is_sale == 1 && $product->giakhuyenmai > 0): ?>
                            <span class="price-current"><?= number_format($product->giakhuyenmai) ?>đ</span>
                            <span class="price-original"><?= number_format($product->giathamkhao) ?>đ</span>
                            <?php else: ?>
                            <span class="price-current"><?= number_format($product->giathamkhao) ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-stats">
                            <span class="stat-item">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y', strtotime($product->created_at)) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 3. SẢN PHẨM KHUYẾN MÃI -->
        <div class="section">
            <div class="section-header sale">
                <div class="icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h2>Sản Phẩm Khuyến Mãi</h2>
                <span class="count"><?= count($saleProducts) ?> sản phẩm</span>
            </div>
            
            <?php if (empty($saleProducts)): ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <p>Chưa có sản phẩm khuyến mãi.</p>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($saleProducts as $product): ?>
                <div class="product-card">
                    <span class="product-badge badge-sale">
                        <i class="fas fa-tags"></i> Giảm <?= $product->discount_percent ?>%
                    </span>
                    <img src="<?= htmlspecialchars($product->hinhanh ?? 'placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($product->tenhanghoa) ?>" 
                         class="product-image">
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                        <div class="product-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($product->ten_loai ?? 'N/A') ?></span>
                            <span><i class="fas fa-copyright"></i> <?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></span>
                        </div>
                        <div class="product-price">
                            <span class="price-current"><?= number_format($product->giakhuyenmai) ?>đ</span>
                            <span class="price-original"><?= number_format($product->giathamkhao) ?>đ</span>
                            <span class="discount-badge">-<?= $product->discount_percent ?>%</span>
                        </div>
                        <div class="product-stats">
                            <span class="stat-item">
                                <i class="fas fa-piggy-bank"></i>
                                Tiết kiệm <?= number_format($product->discount_amount) ?>đ
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
