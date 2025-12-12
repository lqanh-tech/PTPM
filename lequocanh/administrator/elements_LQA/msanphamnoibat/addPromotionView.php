<?php
/**
 * Thêm/Sửa Khuyến Mãi
 */

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = 'success';
$editMode = false;
$product = null;

// Kiểm tra mode sửa
if (isset($_GET['id'])) {
    $editMode = true;
    $idhanghoa = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT * FROM hanghoa WHERE idhanghoa = ?");
    $stmt->execute([$idhanghoa]);
    $product = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$product) {
        header('Location: ?req=manageFeatured&tab=sale');
        exit();
    }
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $idhanghoa = intval($_POST['idhanghoa']);
        $giakhuyenmai = floatval($_POST['giakhuyenmai']);
        
        // Lấy thông tin sản phẩm
        $stmt = $db->prepare("SELECT giathamkhao FROM hanghoa WHERE idhanghoa = ?");
        $stmt->execute([$idhanghoa]);
        $currentProduct = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$currentProduct) {
            throw new Exception("Sản phẩm không tồn tại");
        }
        
        // Validate
        if ($giakhuyenmai <= 0) {
            throw new Exception("Giá khuyến mãi phải lớn hơn 0");
        }
        
        if ($giakhuyenmai >= $currentProduct->giathamkhao) {
            throw new Exception("Giá khuyến mãi phải nhỏ hơn giá hiện tại (" . number_format($currentProduct->giathamkhao) . "đ)");
        }
        
        // Cập nhật
        $stmt = $db->prepare("UPDATE hanghoa SET giakhuyenmai = ? WHERE idhanghoa = ?");
        $stmt->execute([$giakhuyenmai, $idhanghoa]);
        
        $message = "✅ Đã cập nhật khuyến mãi thành công";
        $redirectUrl = "?req=manageFeatured&tab=sale";
    } catch (Exception $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Lấy danh sách sản phẩm (nếu không phải edit mode)
if (!$editMode) {
    $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai,
            th.tenTH as ten_thuonghieu
            FROM hanghoa h
            LEFT JOIN thuonghieu th ON h.idthuonghieu = th.idthuonghieu
            WHERE h.giathamkhao > 0
            ORDER BY h.tenhanghoa ASC";
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_OBJ);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editMode ? 'Sửa' : 'Thêm' ?> Khuyến Mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #333;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .product-select-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .product-select-card:hover {
            border-color: #fa709a;
            box-shadow: 0 2px 8px rgba(250, 112, 154, 0.2);
        }
        
        .product-select-card.selected {
            border-color: #fa709a;
            background: #fff5f8;
        }
        
        .price-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .discount-badge {
            background: #e74c3c;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="page-header">
            <h2><i class="fas fa-fire"></i> <?= $editMode ? 'Sửa' : 'Thêm' ?> Khuyến Mãi</h2>
            <p class="mb-0">Thiết lập giá khuyến mãi cho sản phẩm</p>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType == 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                    <?= $message ?>
                    <?php if (isset($redirectUrl)): ?>
                        <script>
                            setTimeout(function() {
                                window.location.href = '<?= $redirectUrl ?>';
                            }, 2000);
                        </script>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="warning-box">
                    <h6><i class="fas fa-exclamation-triangle"></i> Lưu Ý Quan Trọng</h6>
                    <ul class="mb-0">
                        <li>Giá khuyến mãi phải <strong>nhỏ hơn</strong> giá hiện tại</li>
                        <li>Giá khuyến mãi phải <strong>lớn hơn 0</strong></li>
                        <li>Giá hiện tại <strong>KHÔNG</strong> bị thay đổi</li>
                    </ul>
                </div>
                
                <?php if ($editMode): ?>
                    <!-- Form sửa khuyến mãi -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Sản phẩm: <?= htmlspecialchars($product->tenhanghoa) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($product->ten_thuonghieu ?? 'N/A') ?></p>
                            
                            <form method="POST" id="promotionForm">
                                <input type="hidden" name="idhanghoa" value="<?= $product->idhanghoa ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Giá Hiện Tại</label>
                                    <input type="text" class="form-control" value="<?= number_format($product->giathamkhao) ?>đ" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Giá Khuyến Mãi <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           name="giakhuyenmai" 
                                           id="giakhuyenmai"
                                           class="form-control" 
                                           value="<?= $product->giakhuyenmai ?? '' ?>"
                                           min="1"
                                           max="<?= $product->giathamkhao - 1 ?>"
                                           step="1000"
                                           required
                                           oninput="updatePreview()">
                                    <small class="text-muted">Nhập giá khuyến mãi (VNĐ)</small>
                                </div>
                                
                                <div class="price-preview" id="pricePreview" style="display: none;">
                                    <h6>Xem Trước:</h6>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="discount-badge" id="discountBadge">-0%</span>
                                        <div>
                                            <div>
                                                <strong class="text-danger" style="font-size: 24px;" id="salePrice">0đ</strong>
                                            </div>
                                            <div>
                                                <small class="text-muted text-decoration-line-through" id="originalPrice">
                                                    <?= number_format($product->giathamkhao) ?>đ
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu Khuyến Mãi
                                    </button>
                                    <a href="?req=manageFeatured&tab=sale" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Hủy
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Chọn sản phẩm -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Chọn Sản Phẩm</h5>
                            <div class="mb-3">
                                <input type="text" 
                                       class="form-control" 
                                       id="searchProduct" 
                                       placeholder="Tìm kiếm sản phẩm...">
                            </div>
                            
                            <div id="productList" style="max-height: 500px; overflow-y: auto;">
                                <?php foreach ($products as $p): ?>
                                <div class="product-select-card" onclick="selectProduct(<?= $p->idhanghoa ?>)">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($p->tenhanghoa) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($p->ten_thuonghieu ?? 'N/A') ?></small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-danger"><?= number_format($p->giathamkhao) ?>đ</strong>
                                            <?php if ($p->giakhuyenmai): ?>
                                            <br><span class="badge bg-success">Đang có KM</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle"></i> Hướng Dẫn</h6>
                        <ol class="small">
                            <li>Chọn sản phẩm cần thêm khuyến mãi</li>
                            <li>Nhập giá khuyến mãi</li>
                            <li>Xem trước % giảm giá</li>
                            <li>Lưu lại</li>
                        </ol>
                        
                        <hr>
                        
                        <h6><i class="fas fa-calculator"></i> Tính % Giảm Giá</h6>
                        <p class="small mb-0">
                            % = ((Giá hiện tại - Giá KM) / Giá hiện tại) × 100
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const originalPrice = <?= $editMode ? $product->giathamkhao : 0 ?>;
    
    function updatePreview() {
        const salePrice = parseFloat(document.getElementById('giakhuyenmai').value) || 0;
        
        if (salePrice > 0 && salePrice < originalPrice) {
            const discount = Math.round(((originalPrice - salePrice) / originalPrice) * 100);
            
            document.getElementById('pricePreview').style.display = 'block';
            document.getElementById('discountBadge').textContent = '-' + discount + '%';
            document.getElementById('salePrice').textContent = salePrice.toLocaleString('vi-VN') + 'đ';
        } else {
            document.getElementById('pricePreview').style.display = 'none';
        }
    }
    
    function selectProduct(id) {
        window.location.href = '?req=addPromotion&id=' + id;
    }
    
    // Search products
    <?php if (!$editMode): ?>
    document.getElementById('searchProduct').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.product-select-card');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(search) ? 'block' : 'none';
        });
    });
    <?php endif; ?>
    
    // Auto update preview on load
    <?php if ($editMode && $product->giakhuyenmai): ?>
    updatePreview();
    <?php endif; ?>
    </script>
</body>
</html>
