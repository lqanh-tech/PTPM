<?php
/**
 * Admin Page - Quản lý sản phẩm đặc biệt
 */

require_once __DIR__ . '/elements_LQA/mod/database.php';
require_once __DIR__ . '/elements_LQA/mod/FeaturedProductsCls.php';
require_once __DIR__ . '/elements_LQA/mod/sessionManager.php';

SessionManager::start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Location: userLogin.php');
    exit();
}

$featuredMgr = new FeaturedProducts();
$db = Database::getInstance()->getConnection();

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idhanghoa = $_POST['idhanghoa'] ?? 0;
    
    switch ($action) {
        case 'set_featured':
            $featuredMgr->setFeatured($idhanghoa, 1);
            $message = "Đã đánh dấu sản phẩm nổi bật";
            break;
            
        case 'unset_featured':
            $featuredMgr->setFeatured($idhanghoa, 0);
            $message = "Đã bỏ đánh dấu sản phẩm nổi bật";
            break;
            
        case 'set_new':
            $featuredMgr->setNew($idhanghoa, 1);
            $message = "Đã đánh dấu sản phẩm mới";
            break;
            
        case 'unset_new':
            $featuredMgr->setNew($idhanghoa, 0);
            $message = "Đã bỏ đánh dấu sản phẩm mới";
            break;
            
        case 'set_sale':
            $sale_price = $_POST['sale_price'] ?? 0;
            $sale_end_date = $_POST['sale_end_date'] ?? null;
            $featuredMgr->setSale($idhanghoa, $sale_price, $sale_end_date);
            $message = "Đã thiết lập khuyến mãi";
            break;
            
        case 'remove_sale':
            $featuredMgr->removeSale($idhanghoa);
            $message = "Đã hủy khuyến mãi";
            break;
    }
}

// Lấy danh sách sản phẩm
$sql = "SELECT h.*, 
        t.tenTH AS ten_thuonghieu,
        CASE 
            WHEN h.is_sale = 1 AND h.sale_price IS NOT NULL 
            THEN h.sale_price
            ELSE h.giathamkhao
        END as gia_hien_tai
        FROM hanghoa h
        LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
        ORDER BY h.tenhanghoa ASC";
$stmt = $db->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm Đặc Biệt</title>
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
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #e74c3c;
        }
        
        .message {
            padding: 15px;
            background: #27ae60;
            color: #fff;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e74c3c;
            background: #fff;
            color: #e74c3c;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #e74c3c;
            color: #fff;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .products-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .products-table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .badge-featured {
            background: #f39c12;
            color: #fff;
        }
        
        .badge-new {
            background: #27ae60;
            color: #fff;
        }
        
        .badge-sale {
            background: #e74c3c;
            color: #fff;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: #fff;
        }
        
        .btn-success {
            background: #27ae60;
            color: #fff;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        
        .btn-warning {
            background: #f39c12;
            color: #fff;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            min-width: 400px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .price {
            font-weight: bold;
            color: #e74c3c;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-star"></i> Quản Lý Sản Phẩm Đặc Biệt</h1>
        
        <?php if (isset($message)): ?>
        <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <button class="filter-btn active" onclick="filterProducts('all')">
                <i class="fas fa-list"></i> Tất cả
            </button>
            <button class="filter-btn" onclick="filterProducts('featured')">
                <i class="fas fa-star"></i> Nổi bật
            </button>
            <button class="filter-btn" onclick="filterProducts('new')">
                <i class="fas fa-sparkles"></i> Mới
            </button>
            <button class="filter-btn" onclick="filterProducts('sale')">
                <i class="fas fa-tag"></i> Khuyến mãi
            </button>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên sản phẩm</th>
                    <th>Thương hiệu</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Lượt xem</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr data-featured="<?= $product->is_featured ?>" 
                    data-new="<?= $product->is_new ?>" 
                    data-sale="<?= $product->is_sale ?>">
                    <td><?= $product->idhanghoa ?></td>
                    <td><?= htmlspecialchars($product->tenhanghoa) ?></td>
                    <td><?= htmlspecialchars($product->ten_thuonghieu ?? '-') ?></td>
                    <td>
                        <span class="price"><?= number_format($product->gia_hien_tai, 0, ',', '.') ?> đ</span>
                        <?php if ($product->is_sale && $product->sale_price): ?>
                        <br><span class="original-price"><?= number_format($product->giathamkhao, 0, ',', '.') ?> đ</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product->is_featured): ?>
                        <span class="badge badge-featured">Nổi bật</span>
                        <?php endif; ?>
                        <?php if ($product->is_new): ?>
                        <span class="badge badge-new">Mới</span>
                        <?php endif; ?>
                        <?php if ($product->is_sale): ?>
                        <span class="badge badge-sale">Khuyến mãi</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $product->view_count ?? 0 ?></td>
                    <td>
                        <?php if ($product->is_featured): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="unset_featured">
                            <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                            <button type="submit" class="btn btn-warning" title="Bỏ nổi bật">
                                <i class="fas fa-star-half-alt"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="set_featured">
                            <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                            <button type="submit" class="btn btn-warning" title="Đánh dấu nổi bật">
                                <i class="fas fa-star"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($product->is_new): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="unset_new">
                            <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                            <button type="submit" class="btn btn-success" title="Bỏ mới">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="set_new">
                            <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                            <button type="submit" class="btn btn-success" title="Đánh dấu mới">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($product->is_sale): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="remove_sale">
                            <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                            <button type="submit" class="btn btn-danger" title="Hủy khuyến mãi">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <button class="btn btn-danger" onclick="openSaleModal(<?= $product->idhanghoa ?>, <?= $product->giathamkhao ?>)" title="Thiết lập khuyến mãi">
                            <i class="fas fa-tag"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal thiết lập khuyến mãi -->
    <div id="saleModal" class="modal">
        <div class="modal-content">
            <h2>Thiết Lập Khuyến Mãi</h2>
            <form method="POST">
                <input type="hidden" name="action" value="set_sale">
                <input type="hidden" name="idhanghoa" id="sale_idhanghoa">
                
                <div class="form-group">
                    <label>Giá gốc:</label>
                    <input type="text" id="original_price" readonly>
                </div>
                
                <div class="form-group">
                    <label>Giá khuyến mãi:</label>
                    <input type="number" name="sale_price" id="sale_price" required>
                </div>
                
                <div class="form-group">
                    <label>Ngày kết thúc (tùy chọn):</label>
                    <input type="datetime-local" name="sale_end_date">
                </div>
                
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn btn-success">Lưu</button>
                    <button type="button" class="btn btn-danger" onclick="closeSaleModal()">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function filterProducts(type) {
            const rows = document.querySelectorAll('.products-table tbody tr');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.filter-btn').classList.add('active');
            
            rows.forEach(row => {
                if (type === 'all') {
                    row.style.display = '';
                } else if (type === 'featured' && row.dataset.featured === '1') {
                    row.style.display = '';
                } else if (type === 'new' && row.dataset.new === '1') {
                    row.style.display = '';
                } else if (type === 'sale' && row.dataset.sale === '1') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function openSaleModal(id, originalPrice) {
            document.getElementById('sale_idhanghoa').value = id;
            document.getElementById('original_price').value = new Intl.NumberFormat('vi-VN').format(originalPrice) + ' đ';
            document.getElementById('sale_price').value = '';
            document.getElementById('saleModal').style.display = 'block';
        }
        
        function closeSaleModal() {
            document.getElementById('saleModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('saleModal');
            if (event.target === modal) {
                closeSaleModal();
            }
        }
    </script>
</body>
</html>
