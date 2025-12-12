<?php
/**
 * Dashboard Tự Động Đánh Dấu Sản Phẩm Nổi Bật
 */

require_once __DIR__ . '/elements_LQA/mod/database.php';
require_once __DIR__ . '/elements_LQA/mod/AutoFeaturedCls.php';
require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    header('Location: userLogin.php');
    exit();
}

$autoFeatured = new AutoFeatured();
$message = '';

// Xử lý action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $limit = intval($_POST['limit'] ?? 20);
    
    switch ($action) {
        case 'best_sellers':
            $autoFeatured->autoMarkBestSellers($limit);
            $message = "✅ Đã đánh dấu $limit sản phẩm bán chạy nhất";
            break;
            
        case 'most_viewed':
            $autoFeatured->autoMarkMostViewed($limit);
            $message = "✅ Đã đánh dấu $limit sản phẩm xem nhiều nhất";
            break;
            
        case 'by_score':
            $autoFeatured->autoMarkByScore($limit);
            $message = "✅ Đã đánh dấu $limit sản phẩm theo điểm tổng hợp";
            break;
            
        case 'trending':
            $autoFeatured->autoMarkTrending($limit);
            $message = "✅ Đã đánh dấu $limit sản phẩm trending";
            break;
            
        case 'high_margin':
            $min_margin = intval($_POST['min_margin'] ?? 30);
            $autoFeatured->autoMarkHighMargin($limit, $min_margin);
            $message = "✅ Đã đánh dấu $limit sản phẩm có margin cao";
            break;
    }
}

// Lấy top products theo các tiêu chí
$topSales = $autoFeatured->getTopProducts('sales', 10);
$topViews = $autoFeatured->getTopProducts('views', 10);
$topRevenue = $autoFeatured->getTopProducts('revenue', 10);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tự Động Đánh Dấu Sản Phẩm Nổi Bật</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .message {
            padding: 15px;
            background: #27ae60;
            color: #fff;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .criteria-card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .criteria-card h3 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .criteria-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .criteria-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            font-size: 13px;
            color: #666;
        }
        
        .form-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stats-card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-card h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            flex: 1;
            font-size: 14px;
            color: #333;
        }
        
        .product-value {
            font-weight: bold;
            color: #667eea;
        }
        
        .icon {
            font-size: 24px;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #555;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-magic"></i> Tự Động Đánh Dấu Sản Phẩm Nổi Bật</h1>
            <p>Sử dụng AI và phân tích dữ liệu để tự động chọn sản phẩm nổi bật</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Sản phẩm nổi bật là gì?</h4>
            <ul>
                <li><strong>Bán chạy:</strong> Sản phẩm có doanh số cao nhất</li>
                <li><strong>Xem nhiều:</strong> Sản phẩm được khách hàng quan tâm nhất</li>
                <li><strong>Điểm tổng hợp:</strong> Kết hợp doanh số (40%) + lượt xem (30%) + mới (20%) + khuyến mãi (10%)</li>
                <li><strong>Trending:</strong> Sản phẩm đang tăng trưởng nhanh (so sánh 7 ngày gần đây vs 7 ngày trước)</li>
                <li><strong>Margin cao:</strong> Sản phẩm có lợi nhuận tốt cho cửa hàng</li>
            </ul>
        </div>
        
        <div class="criteria-grid">
            <!-- Bán chạy nhất -->
            <div class="criteria-card">
                <h3>
                    <i class="fas fa-fire icon" style="color: #e74c3c;"></i>
                    Bán Chạy Nhất
                </h3>
                <p>Đánh dấu sản phẩm có doanh số cao nhất. Phù hợp để khách hàng biết sản phẩm nào được mua nhiều.</p>
                <form method="POST" class="criteria-form">
                    <input type="hidden" name="action" value="best_sellers">
                    <div class="form-group">
                        <label>Số lượng sản phẩm:</label>
                        <input type="number" name="limit" value="20" min="1" max="50">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Áp dụng
                    </button>
                </form>
            </div>
            
            <!-- Xem nhiều nhất -->
            <div class="criteria-card">
                <h3>
                    <i class="fas fa-eye icon" style="color: #3498db;"></i>
                    Xem Nhiều Nhất
                </h3>
                <p>Đánh dấu sản phẩm có lượt xem cao nhất. Sản phẩm được khách hàng quan tâm nhiều.</p>
                <form method="POST" class="criteria-form">
                    <input type="hidden" name="action" value="most_viewed">
                    <div class="form-group">
                        <label>Số lượng sản phẩm:</label>
                        <input type="number" name="limit" value="20" min="1" max="50">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Áp dụng
                    </button>
                </form>
            </div>
            
            <!-- Điểm tổng hợp -->
            <div class="criteria-card">
                <h3>
                    <i class="fas fa-chart-line icon" style="color: #9b59b6;"></i>
                    Điểm Tổng Hợp (Khuyến nghị)
                </h3>
                <p>Kết hợp nhiều yếu tố: doanh số, lượt xem, sản phẩm mới, khuyến mãi. Cân bằng và thông minh nhất.</p>
                <form method="POST" class="criteria-form">
                    <input type="hidden" name="action" value="by_score">
                    <div class="form-group">
                        <label>Số lượng sản phẩm:</label>
                        <input type="number" name="limit" value="20" min="1" max="50">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Áp dụng (Khuyến nghị)
                    </button>
                </form>
            </div>
            
            <!-- Trending -->
            <div class="criteria-card">
                <h3>
                    <i class="fas fa-rocket icon" style="color: #f39c12;"></i>
                    Trending
                </h3>
                <p>Sản phẩm đang tăng trưởng nhanh. So sánh doanh số 7 ngày gần đây với 7 ngày trước.</p>
                <form method="POST" class="criteria-form">
                    <input type="hidden" name="action" value="trending">
                    <div class="form-group">
                        <label>Số lượng sản phẩm:</label>
                        <input type="number" name="limit" value="20" min="1" max="50">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Áp dụng
                    </button>
                </form>
            </div>
            
            <!-- Margin cao -->
            <div class="criteria-card">
                <h3>
                    <i class="fas fa-dollar-sign icon" style="color: #27ae60;"></i>
                    Lợi Nhuận Cao
                </h3>
                <p>Sản phẩm có margin lợi nhuận cao. Tốt cho doanh thu cửa hàng.</p>
                <form method="POST" class="criteria-form">
                    <input type="hidden" name="action" value="high_margin">
                    <div class="form-group">
                        <label>Số lượng sản phẩm:</label>
                        <input type="number" name="limit" value="20" min="1" max="50">
                    </div>
                    <div class="form-group">
                        <label>Margin tối thiểu (%):</label>
                        <input type="number" name="min_margin" value="30" min="0" max="100">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Áp dụng
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Thống kê -->
        <div class="stats-grid">
            <!-- Top bán chạy -->
            <div class="stats-card">
                <h3><i class="fas fa-trophy"></i> Top 10 Bán Chạy</h3>
                <?php foreach ($topSales as $product): ?>
                <div class="product-item">
                    <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                    <div class="product-value"><?= number_format($product->total_sold) ?> đã bán</div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Top xem nhiều -->
            <div class="stats-card">
                <h3><i class="fas fa-eye"></i> Top 10 Xem Nhiều</h3>
                <?php foreach ($topViews as $product): ?>
                <div class="product-item">
                    <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                    <div class="product-value"><?= number_format($product->view_count) ?> lượt xem</div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Top doanh thu -->
            <div class="stats-card">
                <h3><i class="fas fa-chart-bar"></i> Top 10 Doanh Thu</h3>
                <?php foreach ($topRevenue as $product): ?>
                <div class="product-item">
                    <div class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></div>
                    <div class="product-value"><?= number_format($product->total_revenue) ?> đ</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
