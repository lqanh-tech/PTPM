<link rel="stylesheet" href="public_files/toast-notification.css">
<script src="public_files/toast-notification.js"></script>
<script>
    function goBack() {
        window.history.back();
    }

    // Function để thêm sản phẩm vào giỏ hàng bằng AJAX
    function addToCart(productId) {
        // Hiển thị loading
        toast.info('⏳ Đang thêm vào giỏ hàng...');

        // Sử dụng fetch API với header AJAX
        fetch('administrator/elements_LQA/mgiohang/giohangAct.php?action=add&productId=' + productId + '&quantity=1', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Thành công
                toast.success('✅ ' + data.message);
                
                // Cập nhật số lượng trong giỏ hàng trên navbar (nếu có)
                updateCartCount();
            } else {
                // Lỗi
                toast.error('❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toast.error('❌ Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng!');
        });
    }

    // Function để cập nhật số lượng giỏ hàng
    function updateCartCount() {
        fetch('administrator/elements_LQA/mgiohang/getCartCount.php', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật badge số lượng giỏ hàng
                const cartBadges = document.querySelectorAll('.cart-count, .badge');
                cartBadges.forEach(badge => {
                    badge.textContent = data.count;
                });
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
        });
    }

    // Function để mua ngay (thêm vào giỏ và chuyển đến trang giỏ hàng)
    function buyNow(productId) {
        // Hiển thị loading
        toast.info('⏳ Đang xử lý...');

        // Thêm sản phẩm vào giỏ hàng với header AJAX
        fetch('administrator/elements_LQA/mgiohang/giohangAct.php?action=add&productId=' + productId + '&quantity=1', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Thành công - chuyển đến trang giỏ hàng
                toast.success('✅ ' + data.message);
                setTimeout(() => {
                    window.location.href = 'administrator/elements_LQA/mgiohang/giohangView.php';
                }, 500);
            } else {
                // Lỗi
                toast.error('❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toast.error('❌ Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng!');
        });
    }

    // Xử lý thông báo khi thêm giỏ hàng thành công hoặc có lỗi
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra xem URL có chứa tham số cartAdded không
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('cartAdded')) {
            // Hiển thị thông báo
            toast.success('Đã thêm sản phẩm vào giỏ hàng!');

            // Xóa tham số cartAdded khỏi URL để tránh hiển thị lại thông báo khi refresh
            const newUrl = window.location.href.replace(/[&?]cartAdded=1/, '');
            window.history.replaceState({}, document.title, newUrl);
        }

        // Kiểm tra xem có thông báo lỗi từ giỏ hàng không
        <?php if (isset($_SESSION['cart_error'])): ?>
            // Hiển thị thông báo lỗi
            toast.error('<?php echo $_SESSION['cart_error']; ?>');
            <?php
            // Xóa thông báo lỗi sau khi hiển thị
            unset($_SESSION['cart_error']);
            ?>
        <?php endif; ?>
    });
</script>

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/thuoctinhhhCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/thuoctinhCls.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/mtonkhoCls.php';
$hanghoa = new hanghoa();
$tonkho = new MTonKho();

if (isset($_GET['reqHanghoa'])) {
    $idhanghoa = $_GET['reqHanghoa'];
    $obj = $hanghoa->HanghoaGetbyId($idhanghoa);

    // Thêm truy vấn để lấy thông tin thuộc tính hàng hóa
    $thuocTinhHHObj = new ThuocTinhHH();
    $listThuocTinh = $thuocTinhHHObj->thuoctinhhhGetbyIdHanghoa($idhanghoa);

    // Lấy thông tin tồn kho của sản phẩm
    $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($idhanghoa);
}
?>
<link rel="stylesheet" href="public_files/mycss.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="administrator/elements_LQA/js_LQA/jscript.js"></script>

<div class="card mb-3">
    <div class="row g-0">
        <div class="col-md-4">
            <?php
            // Get the image data from the hinhanh table
            $hinhanh = $hanghoa->GetHinhAnhById($obj->hinhanh);

            if ($hinhanh && !empty($hinhanh->duong_dan)) {
                // Sử dụng displayImage.php để hiển thị hình ảnh
                echo '<img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $obj->hinhanh . '"
                    class="img-fluid rounded-start" alt="' . htmlspecialchars($obj->tenhanghoa) . '">';
            } else {
                // Hiển thị ảnh "no-image" thay vì cố gắng tải hình ảnh không tồn tại
                echo '<div class="text-center p-3 border rounded" style="height: 100%;">
                        <img src="./administrator/elements_LQA/img_LQA/no-image.png" class="img-fluid rounded-start" style="max-height: 200px"
                            alt="Không có hình ảnh">
                      </div>';
            }
            ?>
        </div>
        <div class="col-md-8">
            <div class="card-body">
                <h5 class="card-title"><?php echo $obj->tenhanghoa; ?></h5>
                <p class="card-text"><?php echo $obj->mota; ?></p>
                <p class="card-text">
                    <?php
                    // Kiểm tra có khuyến mãi không
                    $hasPromotion = isset($obj->giakhuyenmai) && $obj->giakhuyenmai > 0 && $obj->giakhuyenmai < $obj->giathamkhao;

                    if ($hasPromotion) {
                        $discountPercent = round((($obj->giathamkhao - $obj->giakhuyenmai) / $obj->giathamkhao) * 100);
                    ?>
                        <span class="badge bg-danger mb-2">
                            <i class="fas fa-fire"></i> Giảm <?php echo $discountPercent; ?>%
                        </span>
                        <br>
                        <small class="text-muted">Giá bán:</small>
                <div>
                    <span class="text-danger fw-bold" style="font-size: 24px;">
                        <?php echo number_format($obj->giakhuyenmai, 0, ',', '.') . ' VNĐ'; ?>
                    </span>
                </div>
                <div>
                    <small class="text-muted text-decoration-line-through">
                        Giá gốc: <?php echo number_format($obj->giathamkhao, 0, ',', '.') . ' VNĐ'; ?>
                    </small>
                    <span class="badge bg-success ms-2">
                        Tiết kiệm <?php echo number_format($obj->giathamkhao - $obj->giakhuyenmai, 0, ',', '.'); ?> VNĐ
                    </span>
                </div>
            <?php } else { ?>
                <small class="text-muted">Giá bán:
                    <span class="text-danger fw-bold">
                        <?php echo number_format($obj->giathamkhao, 0, ',', '.') . ' VNĐ'; ?>
                    </span>
                </small>
            <?php } ?>
            </p>
            
            <!-- Rating sản phẩm -->
            <p class="card-text">
                <strong>Đánh giá: </strong>
                <?php
                $productRating = $hanghoa->getAverageRating($obj->idhanghoa);
                ?>
                <?php if ($productRating['count'] > 0): ?>
                    <span class="me-2">
                        <?php
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= floor($productRating['average'])):
                                echo '<i class="fas fa-star text-warning"></i>';
                            elseif ($i == ceil($productRating['average']) && ($productRating['average'] - floor($productRating['average']) >= 0.5)):
                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                            else:
                                echo '<i class="far fa-star text-secondary" style="opacity: 0.3;"></i>';
                            endif;
                        endfor;
                        ?>
                    </span>
                    <span class="text-warning fw-bold"><?php echo $productRating['average']; ?></span>
                    <span class="text-muted">({<?php echo $productRating['count']; ?> đánh giá)</span>
                <?php else: ?>
                    <span class="me-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="far fa-star text-secondary" style="opacity: 0.3;"></i>
                        <?php endfor; ?>
                    </span>
                    <span class="text-muted">Chưa có đánh giá</span>
                <?php endif; ?>
            </p>
            
            <p class="card-text"><strong>Thương hiệu:
                </strong><?php echo $obj->idThuongHieu ? $hanghoa->GetThuongHieuById($obj->idThuongHieu)->tenTH : 'Chưa chọn'; ?>
            </p>

            <!-- Hiển thị thông tin tồn kho -->
            <p class="card-text">
                <strong>Tình trạng: </strong>
                <?php
                // Kiểm tra trạng thái sản phẩm
                $statusMessage = '';
                $statusClass = '';

                if (isset($obj->trang_thai)) {
                    if ($obj->trang_thai == 2) {
                        $statusMessage = 'Ngừng bán';
                        $statusClass = 'text-warning';
                    } elseif ($obj->trang_thai == 3) {
                        $statusMessage = 'Hết hàng';
                        $statusClass = 'text-danger';
                    } elseif ($obj->trang_thai == 1) {
                        // Kiểm tra tồn kho thực tế
                        if ($tonkhoInfo && $tonkhoInfo->soLuong > 0) {
                            $statusMessage = 'Còn hàng (' . $tonkhoInfo->soLuong . ' sản phẩm)';
                            $statusClass = 'text-success';
                        } else {
                            $statusMessage = 'Hết hàng';
                            $statusClass = 'text-danger';
                        }
                    }
                } else {
                    // Fallback nếu không có trang_thai
                    if ($tonkhoInfo && $tonkhoInfo->soLuong > 0) {
                        $statusMessage = 'Còn hàng (' . $tonkhoInfo->soLuong . ' sản phẩm)';
                        $statusClass = 'text-success';
                    } else {
                        $statusMessage = 'Hết hàng';
                        $statusClass = 'text-danger';
                    }
                }
                ?>
                <span class="<?php echo $statusClass; ?>"><?php echo $statusMessage; ?></span>
            </p>
            <!-- Hiển thị thông tin thuộc tính hàng hóa -->
            <?php if (!empty($listThuocTinh)): ?>
                <div class="specs-container">
                    <h6>Thông số kỹ thuật:</h6>
                    <ul class="specs-list">
                        <?php foreach ($listThuocTinh as $tt): ?>
                            <?php
                            // Lấy tên thuộc tính từ bảng thuoctinh
                            $thuocTinhObj = new ThuocTinh();
                            $thuocTinh = $thuocTinhObj->thuoctinhGetbyId($tt->idThuocTinh);
                            ?>
                            <li>
                                <strong><?php echo htmlspecialchars($thuocTinh->tenThuocTinh); ?>:</strong>
                                <span class="specs-value"><?php echo htmlspecialchars($tt->tenThuocTinhHH); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Action buttons -->
            <div style="margin-top: 20px; margin-bottom: 15px;">
                <?php
                // Kiểm tra xem sản phẩm có thể mua được không
                $canPurchase = true;
                $purchaseMessage = '';

                if (isset($obj->trang_thai)) {
                    if ($obj->trang_thai == 2) {
                        $canPurchase = false;
                        $purchaseMessage = 'Sản phẩm này đã ngừng bán';
                    } elseif ($obj->trang_thai == 3) {
                        $canPurchase = false;
                        $purchaseMessage = 'Sản phẩm này đã hết hàng';
                    } elseif ($obj->trang_thai == 1 && (!$tonkhoInfo || $tonkhoInfo->soLuong == 0)) {
                        $canPurchase = false;
                        $purchaseMessage = 'Sản phẩm này đã hết hàng';
                    }
                } elseif (!$tonkhoInfo || $tonkhoInfo->soLuong == 0) {
                    $canPurchase = false;
                    $purchaseMessage = 'Sản phẩm này đã hết hàng';
                }
                ?>

                <!-- Add to cart button -->
                <?php if (!$canPurchase): ?>
                    <!-- Nút bị vô hiệu hóa khi sản phẩm không thể mua -->
                    <button disabled onclick="alert('<?php echo $purchaseMessage; ?>')"
                        style="background-color: #ccc; color: #999; padding: 12px 20px; margin: 5px; border-radius: 5px; border: none; cursor: not-allowed; font-weight: bold; opacity: 0.6;">
                        🛒 Thêm vào giỏ hàng
                    </button>
                <?php elseif (isset($_SESSION['USER'])): ?>
                    <button onclick="addToCart(<?php echo $obj->idhanghoa; ?>)"
                        style="background-color: #0d6efd; color: white; padding: 12px 20px; margin: 5px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold;">
                        🛒 Thêm vào giỏ hàng
                    </button>
                <?php else: ?>
                    <button onclick="toast.warning('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!'); setTimeout(() => window.location.href='administrator/userLogin.php', 1500);"
                        style="background-color: #0d6efd; color: white; padding: 12px 20px; margin: 5px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold;">
                        🛒 Thêm vào giỏ hàng
                    </button>
                <?php endif; ?>

                <!-- Buy now button -->
                <?php if (!$canPurchase): ?>
                    <!-- Nút bị vô hiệu hóa khi sản phẩm không thể mua -->
                    <button disabled onclick="alert('<?php echo $purchaseMessage; ?>')"
                        style="background-color: #ccc; color: #999; padding: 12px 20px; margin: 5px; border-radius: 5px; cursor: not-allowed; font-weight: bold; opacity: 0.6;">
                        💰 Mua ngay
                    </button>
                <?php elseif (isset($_SESSION['USER'])): ?>
                    <button onclick="buyNow(<?php echo $obj->idhanghoa; ?>)"
                        style="background-color: #198754; color: white; padding: 12px 20px; margin: 5px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold;">
                        💰 Mua ngay
                    </button>
                <?php else: ?>
                    <button onclick="toast.warning('Vui lòng đăng nhập để mua sản phẩm!'); setTimeout(() => window.location.href='administrator/userLogin.php', 1500);"
                        style="background-color: #198754; color: white; padding: 12px 20px; margin: 5px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold;">
                        💰 Mua ngay
                    </button>
                <?php endif; ?>

                <!-- Back button -->
                <button onclick="goBack()"
                    style="background-color: #6c757d; color: white; padding: 12px 20px; margin: 5px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold;">
                    ⬅️ Quay lại
                </button>
            </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-success {
        font-weight: bold;
        color: #28a745 !important;
    }

    .text-danger {
        font-weight: bold;
        color: #dc3545 !important;
    }

    /* Thêm style cho các nút */
    .btn-lg {
        font-weight: bold;
        padding: 10px 20px;
        margin: 5px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }

    .btn-success {
        background-color: #198754;
        border-color: #198754;
        color: white;
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
    }

    .d-flex {
        display: flex !important;
        flex-wrap: wrap;
    }

    .gap-2 {
        gap: 0.5rem;
    }

    .me-2 {
        margin-right: 0.5rem;
    }

    .mt-4 {
        margin-top: 1.5rem;
    }

    .mb-3 {
        margin-bottom: 1rem;
    }
</style>

<?php if (isset($_SESSION['USER'])): ?>
    <div class="dropdown">
        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="fas fa-user me-2"></i>
            <?php echo $_SESSION['USER']; ?>
        </button>
        <ul class="dropdown-menu" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                </a></li>
        </ul>
    </div>
<?php elseif (isset($_SESSION['ADMIN'])): ?>
    <a href="./administrator/index.php" class="btn btn-light me-2">
        <i class="fas fa-user-shield me-2"></i>
        Quản trị viên
    </a>
<?php else: ?>
    <a href="./administrator/userLogin.php" class="btn btn-light me-2">
        <i class="fas fa-user me-2"></i>
        Đăng nhập
    </a>
<?php endif; ?>

<?php
// Product Reviews Section - Sử dụng component mới
$productId = $idhanghoa; // ID sản phẩm hiện tại
include __DIR__ . '/../components/product_review_display.php';
?>

<!-- Related Products Section -->
<div class="related-products mt-5 mb-5">
    <h4 class="mb-4 border-bottom pb-2">
        <i class="fas fa-layer-group text-primary"></i>
        Sản phẩm liên quan
    </h4>

    <?php
    // Get related products: same brand or similar price range
    $relatedProducts = $hanghoa->getRelatedProducts($idhanghoa, 4);

    if (!empty($relatedProducts)):
    ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($relatedProducts as $rp): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm hover-card" style="transition: transform 0.2s;">
                        <!-- Badges -->
                        <?php if (isset($rp->giakhuyenmai) && $rp->giakhuyenmai > 0 && $rp->giakhuyenmai < $rp->giathamkhao): ?>
                            <div class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill">
                                -<?php echo round((($rp->giathamkhao - $rp->giakhuyenmai) / $rp->giathamkhao) * 100); ?>%
                            </div>
                        <?php endif; ?>
                        
                        <!-- Same Brand Badge -->
                        <?php 
                        $currentProduct = $hanghoa->HanghoaGetbyId($idhanghoa);
                        if ($currentProduct && $rp->idThuongHieu == $currentProduct->idThuongHieu): 
                        ?>
                            <div class="position-absolute top-0 end-0 m-2 badge bg-primary rounded-pill" style="font-size: 10px;">
                                Cùng hãng
                            </div>
                        <?php endif; ?>

                        <!-- Image -->
                        <div class="position-relative bg-white rounded-top" style="padding-top: 100%; overflow: hidden;">
                            <?php
                            $rpHinhanh = $hanghoa->GetHinhAnhById($rp->hinhanh);
                            $imgSrc = ($rpHinhanh && !empty($rpHinhanh->duong_dan))
                                ? "./administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $rp->hinhanh
                                : "./administrator/elements_LQA/img_LQA/no-image.png";
                            ?>
                            <a href="./index.php?reqHanghoa=<?php echo $rp->idhanghoa; ?>">
                                <img src="<?php echo $imgSrc; ?>"
                                    class="card-img-top position-absolute top-0 start-0 w-100 h-100"
                                    style="object-fit: contain; padding: 15px; transition: transform 0.3s ease;"
                                    onmouseover="this.style.transform='scale(1.05)'"
                                    onmouseout="this.style.transform='scale(1)'"
                                    alt="<?php echo htmlspecialchars($rp->tenhanghoa); ?>">
                            </a>
                        </div>

                        <div class="card-body d-flex flex-column p-3">
                            <h6 class="card-title text-truncate mb-2" title="<?php echo htmlspecialchars($rp->tenhanghoa); ?>">
                                <a href="./index.php?reqHanghoa=<?php echo $rp->idhanghoa; ?>" class="text-decoration-none text-dark fw-bold">
                                    <?php echo $rp->tenhanghoa; ?>
                                </a>
                            </h6>

                            <!-- Rating -->
                            <?php
                            $rpRating = $hanghoa->getAverageRating($rp->idhanghoa);
                            ?>
                            <div class="mb-2 d-flex align-items-center" style="font-size: 12px;">
                                <?php if ($rpRating['count'] > 0): ?>
                                    <div class="me-2">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= floor($rpRating['average'])):
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            elseif ($i == ceil($rpRating['average']) && ($rpRating['average'] - floor($rpRating['average']) >= 0.5)):
                                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                            else:
                                                echo '<i class="far fa-star text-secondary" style="opacity: 0.3;"></i>';
                                            endif;
                                        endfor;
                                        ?>
                                    </div>
                                    <span class="text-muted">(<?php echo $rpRating['count']; ?>)</span>
                                <?php else: ?>
                                    <i class="far fa-star text-secondary me-1"></i>
                                    <span class="text-muted">Chưa có đánh giá</span>
                                <?php endif; ?>
                            </div>

                            <!-- Price -->
                            <div class="mt-auto">
                                <?php if (isset($rp->giakhuyenmai) && $rp->giakhuyenmai > 0 && $rp->giakhuyenmai < $rp->giathamkhao): ?>
                                    <div class="text-danger fw-bold fs-5"><?php echo number_format($rp->giakhuyenmai, 0, ',', '.'); ?>₫</div>
                                    <div class="text-muted text-decoration-line-through small">
                                        <?php echo number_format($rp->giathamkhao, 0, ',', '.'); ?>₫
                                    </div>
                                <?php else: ?>
                                    <div class="text-danger fw-bold fs-5"><?php echo number_format($rp->giathamkhao, 0, ',', '.'); ?>₫</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top-0 p-3 pt-0">
                            <a href="./index.php?reqHanghoa=<?php echo $rp->idhanghoa; ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill">
                                <i class="fas fa-eye me-1"></i>Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center border rounded p-4">
            <i class="fas fa-box-open fa-2x text-muted mb-3"></i>
            <p class="text-muted mb-0">Chưa có sản phẩm liên quan nào được tìm thấy.</p>
        </div>
    <?php endif; ?>
</div>
</div>