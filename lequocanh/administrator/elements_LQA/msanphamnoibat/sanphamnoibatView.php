<?php

require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/FeaturedProductsCls.php';

$featuredMgr = new FeaturedProducts();
$db = Database::getInstance()->getConnection();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idhanghoa = $_POST['idhanghoa'] ?? 0;
    
    try {
        switch ($action) {
            case 'set_featured':
                $featuredMgr->setFeatured($idhanghoa, 1);
                $message = "✅ Đã đánh dấu sản phẩm nổi bật";
                break;
                
            case 'unset_featured':
                $featuredMgr->setFeatured($idhanghoa, 0);
                $message = "✅ Đã bỏ đánh dấu sản phẩm nổi bật";
                break;
                
            case 'set_new':
                $featuredMgr->setNew($idhanghoa, 1);
                $message = "✅ Đã đánh dấu sản phẩm mới";
                break;
                
            case 'unset_new':
                $featuredMgr->setNew($idhanghoa, 0);
                $message = "✅ Đã bỏ đánh dấu sản phẩm mới";
                break;
                
            case 'set_sale':
                $sale_price = $_POST['sale_price'] ?? 0;
                $sale_end_date = $_POST['sale_end_date'] ?? null;
                $featuredMgr->setSale($idhanghoa, $sale_price, $sale_end_date);
                $message = "✅ Đã thiết lập khuyến mãi";
                break;
                
            case 'remove_sale':
                $featuredMgr->removeSale($idhanghoa);
                $message = "✅ Đã hủy khuyến mãi";
                break;
        }
    } catch (Exception $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
        $messageType = 'error';
    }
}

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

<style>
    .featured-container {
        padding: 20px;
        background: #fff;
        border-radius: 8px;
    }
    
    .page-header {
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 3px solid #e74c3c;
    }
    
    .page-header h2 {
        color: #333;
        font-size: 24px;
        margin: 0;
    }
    
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    
    .alert-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
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
        font-size: 14px;
    }
    
    .filter-btn:hover, .filter-btn.active {
        background: #e74c3c;
        color: #fff;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 14px;
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
    
    .btn-action {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        margin-right: 5px;
        transition: all 0.3s;
    }
    
    .btn-warning {
        background: #f39c12;
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
    
    .btn-action:hover {
        opacity: 0.8;
    }
    
    .price {
        font-weight: bold;
        color: #e74c3c;
    }
    
    .original-price {
        text-decoration: line-through;
        color: #999;
        font-size: 12px;
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
        max-width: 90%;
    }
    
    .modal-header {
        margin-bottom: 20px;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #333;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #555;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-sizing: border-box;
    }
    
    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
</style>

<div class="featured-container">
    <div class="page-header">
        <h2><i class="fas fa-star"></i> Quản Lý Sản Phẩm Nổi Bật</h2>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
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
            <tr data-featured="<?= $product->is_featured ?? 0 ?>" 
                data-new="<?= $product->is_new ?? 0 ?>" 
                data-sale="<?= $product->is_sale ?? 0 ?>">
                <td><?= $product->idhanghoa ?></td>
                <td><?= htmlspecialchars($product->tenhanghoa) ?></td>
                <td><?= htmlspecialchars($product->ten_thuonghieu ?? '-') ?></td>
                <td>
                    <span class="price"><?= number_format($product->gia_hien_tai, 0, ',', '.') ?> đ</span>
                    <?php if (($product->is_sale ?? 0) && ($product->sale_price ?? 0)): ?>
                    <br><span class="original-price"><?= number_format($product->giathamkhao, 0, ',', '.') ?> đ</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($product->is_featured ?? 0): ?>
                    <span class="badge badge-featured">Nổi bật</span>
                    <?php endif; ?>
                    <?php if ($product->is_new ?? 0): ?>
                    <span class="badge badge-new">Mới</span>
                    <?php endif; ?>
                    <?php if ($product->is_sale ?? 0): ?>
                    <span class="badge badge-sale">Khuyến mãi</span>
                    <?php endif; ?>
                </td>
                <td><?= $product->view_count ?? 0 ?></td>
                <td>
                    <?php if ($product->is_featured ?? 0): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="unset_featured">
                        <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                        <button type="submit" class="btn-action btn-warning" title="Bỏ nổi bật">
                            <i class="fas fa-star-half-alt"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="set_featured">
                        <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                        <button type="submit" class="btn-action btn-warning" title="Đánh dấu nổi bật">
                            <i class="fas fa-star"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($product->is_new ?? 0): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="unset_new">
                        <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                        <button type="submit" class="btn-action btn-success" title="Bỏ mới">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="set_new">
                        <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                        <button type="submit" class="btn-action btn-success" title="Đánh dấu mới">
                            <i class="fas fa-plus"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($product->is_sale ?? 0): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="remove_sale">
                        <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                        <button type="submit" class="btn-action btn-danger" title="Hủy khuyến mãi">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <button class="btn-action btn-danger" onclick="openSaleModal(<?= $product->idhanghoa ?>, <?= $product->giathamkhao ?>)" title="Thiết lập khuyến mãi">
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
        <div class="modal-header">
            <h3>Thiết Lập Khuyến Mãi</h3>
        </div>
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
            
            <div class="modal-actions">
                <button type="submit" class="btn-action btn-success">Lưu</button>
                <button type="button" class="btn-action btn-danger" onclick="closeSaleModal()">Hủy</button>
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

window.onclick = function(event) {
    const modal = document.getElementById('saleModal');
    if (event.target === modal) {
        closeSaleModal();
    }
}
</script>
