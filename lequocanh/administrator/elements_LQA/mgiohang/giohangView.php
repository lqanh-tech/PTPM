<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

require_once __DIR__ . '/../../../includes/csrf_helper.php';

SessionManager::start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/hanghoaCls.php';

$giohang = new GioHang();
$hanghoa = new hanghoa();

if (!$giohang->canUseCart()) {
    if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../../userLogin.php');
    } else {
        header('Location: ../../index.php');
    }
    exit();
}

ini_set('display_errors', 0);
error_reporting(0);

$cart = $giohang->getCart();
$cartDetails = [];
$totalAmount = 0;
$hasUnavailableProducts = false;

if (!empty($cart)) {
    foreach ($cart as $item) {
        if (isset($item['product_id'])) {
            $hinhanhValue = (isset($item['hinhanh']) && $item['hinhanh'] !== '') ? (int)$item['hinhanh'] : null;
            $currentPrice = $item['gia_hien_tai'] ?? $item['giathamkhao'] ?? 0;
            $hasDiscount = $item['has_discount'] ?? false;

            require_once '../../elements_LQA/mod/mtonkhoCls.php';
            $tonkho = new MTonKho();
            $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['product_id']);
            $stockQuantity = $tonkhoInfo ? $tonkhoInfo->soLuong : 0;

            $productStatus = $hanghoa->getProductStatusValue($item['product_id']);
            $isUnavailable = false;
            $statusMessage = '';
            $statusClass = '';

            if ($productStatus == 2) {
                $isUnavailable = true;
                $hasUnavailableProducts = true;
                $statusMessage = 'Ngừng bán';
                $statusClass = 'warning';
            } elseif ($productStatus == 3 || $stockQuantity == 0) {
                $isUnavailable = true;
                $hasUnavailableProducts = true;
                $statusMessage = 'Hết hàng';
                $statusClass = 'danger';
            } elseif ($productStatus == 1) {
                if ($stockQuantity <= 0) {
                    $isUnavailable = true;
                    $hasUnavailableProducts = true;
                    $statusMessage = 'Hết hàng';
                    $statusClass = 'danger';
                }
            }

            $cartDetails[] = [
                'id' => $item['product_id'],
                'name' => $item['tenhanghoa'] ?? 'Unknown Product',
                'price' => $currentPrice,
                'original_price' => $item['giathamkhao'] ?? 0,
                'discount_price' => $item['giakhuyenmai'] ?? null,
                'has_discount' => $hasDiscount,
                'quantity' => $item['quantity'],
                'hinhanh' => $hinhanhValue,
                'subtotal' => $currentPrice * $item['quantity'],
                'is_unavailable' => $isUnavailable,
                'status_message' => $statusMessage,
                'status_class' => $statusClass,
                'stock_quantity' => $stockQuantity
            ];

            if (!$isUnavailable) {
                $totalAmount += $currentPrice * $item['quantity'];
            }
        }
    }
}

$orderHistory = [];
if (isset($_SESSION['USER'])) {
    try {
        require_once '../../elements_LQA/mod/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $checkTable = $conn->query("SHOW TABLES LIKE 'don_hang'");
        if ($checkTable->rowCount() > 0) {
            $columnsStmt = $conn->query("SHOW COLUMNS FROM don_hang");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $selectCols = "id, ma_don_hang_text, ngay_tao, tong_tien, trang_thai";
            if (in_array('trang_thai_doi_tra', $columns)) {
                $selectCols .= ", trang_thai_doi_tra";
            }
            
            $sql = "SELECT $selectCols FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$_SESSION['USER']]);
            $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= csrf_meta() ?>
    <title>Giỏ hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public_files/mycss.css">
    <link rel="stylesheet" href="../../public_files/toast-notification.css">
    <style>
        .cart-container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08); }
        .cart-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; }
        .cart-table th { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 2px solid #e9ecef; color: #495057; font-weight: 600; }
        .cart-table td { padding: 15px; text-align: center; vertical-align: middle; border-bottom: 1px solid #e9ecef; }
        .product-image { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 15px; }
        .product-info { display: flex; align-items: center; padding: 10px; }
        .product-name { font-weight: 500; margin-left: 10px; }
        .quantity-controls { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .quantity-input { width: 60px; text-align: center; border: 1px solid #dee2e6; border-radius: 4px; padding: 4px; }
        .decrease-quantity, .increase-quantity { width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 1px solid #dee2e6; }
        .price, .total-price { font-weight: 500; color: #ee4d2d; }
        .cart-footer { position: sticky; bottom: 0; background: white; padding: 15px; border-top: 1px solid #dee2e6; box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1); }
        .right-actions { min-width: 400px; justify-content: flex-end; }
        .total-amount { color: #dc3545; font-size: 1.25rem; }
        .btn-primary { background-color: #3498db; border-color: #3498db; padding: 10px 20px; border-radius: 8px; transition: all 0.3s ease; }
        .btn-primary:hover { background-color: #2980b9; border-color: #2980b9; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2); }
        @media (max-width: 768px) { .cart-container { padding: 15px; } .product-image { width: 80px; height: 80px; } .cart-footer { flex-direction: column; gap: 20px; text-align: center; } }
    </style>
</head>
<body>
    <div class="cart-container">
        <?php if (empty($cartDetails)): ?>
            <div class="text-center py-5">
                <h3 class="mb-4">Giỏ hàng của bạn đang trống</h3>
                <a href="<?php echo isset($_SESSION['ADMIN']) ? '../../index.php' : '../../../index.php'; ?>"
                    class="btn btn-primary btn-lg">
                    Tiếp tục mua hàng
                </a>
            </div>
        <?php else: ?>
            <h2 class="mb-4">Giỏ hàng của bạn</h2>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" id="select-all" class="form-check-input">
                        </th>
                        <th width="45%">Sản phẩm</th>
                        <th width="15%">Đơn giá</th>
                        <th width="20%">Số lượng</th>
                        <th width="10%">Thành tiền</th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartDetails as $item): ?>
                        <tr <?php echo $item['is_unavailable'] ? 'style="background-color: #fff3cd; opacity: 0.8;"' : ''; ?>>
                            <td>
                                <input type="checkbox" class="form-check-input product-select" <?php echo $item['is_unavailable'] ? 'disabled' : ''; ?>>
                            </td>
                            <td class="product-info">
                                <?php
                                $imageSrc = (isset($item['hinhanh']) && is_numeric($item['hinhanh']) && $item['hinhanh'] > 0) 
                                    ? "../../elements_LQA/mhanghoa/displayImage.php?id=" . $item['hinhanh'] 
                                    : "../../elements_LQA/img_LQA/no-image.png";
                                ?>
                                <img src="<?php echo $imageSrc; ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                    class="product-image"
                                    onerror="this.onerror=null; this.src='../../elements_LQA/img_LQA/no-image.png';">
                                <div>
                                    <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <br><small class="text-muted">
                                        <i class="fas fa-box me-1"></i>
                                        Tồn kho: 
                                        <?php if ($item['stock_quantity'] > 0): ?>
                                            <span class="text-success fw-bold"><?php echo $item['stock_quantity']; ?></span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">Hết hàng</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($item['has_discount']): ?>
                                        <br><span class="badge bg-danger">Giảm giá</span>
                                    <?php endif; ?>
                                    <?php if ($item['is_unavailable']): ?>
                                        <br><span class="badge bg-<?php echo $item['status_class']; ?>">
                                            <?php echo $item['status_message']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="price" data-price="<?php echo $item['is_unavailable'] ? '0' : $item['price']; ?>">
                                <?php if ($item['is_unavailable']): ?>
                                    <del><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</del>
                                    <br><small class="text-danger"><strong>Không khả dụng</strong></small>
                                <?php elseif ($item['has_discount']): ?>
                                    <div>
                                        <span style="color: #dc3545; font-weight: bold;"><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</span>
                                        <br>
                                        <small class="text-muted text-decoration-line-through"><?php echo number_format($item['original_price'], 0, ',', '.'); ?> ₫</small>
                                    </div>
                                <?php else: ?>
                                    <?php echo number_format($item['price'], 0, ',', '.'); ?> ₫
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="quantity-controls">
                                    <button class="btn btn-outline-secondary decrease-quantity" type="button" <?php echo $item['is_unavailable'] ? 'disabled' : ''; ?>>−</button>
                                    <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>"
                                        min="1" 
                                        max="<?php echo $item['stock_quantity']; ?>"
                                        data-product-id="<?php echo $item['id']; ?>" 
                                        data-stock="<?php echo $item['stock_quantity']; ?>"
                                        <?php echo $item['is_unavailable'] ? 'disabled' : ''; ?>>
                                    <button class="btn btn-outline-secondary increase-quantity" type="button" <?php echo $item['is_unavailable'] ? 'disabled' : ''; ?>>+</button>
                                </div>
                                <small class="text-muted d-block text-center mt-1">
                                    Max: <?php echo $item['stock_quantity']; ?>
                                </small>
                            </td>
                            <td class="subtotal">
                                <?php echo number_format($item['subtotal'], 0, ',', '.'); ?> ₫
                            </td>
                            <td>
                                <button type="button"
                                    onclick="removeProductFromCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Xóa sản phẩm khỏi giỏ hàng">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="left-actions">
                        <input type="checkbox" id="select-all-bottom" class="form-check-input me-2">
                        <button onclick="deleteSelectedItems()" class="btn btn-outline-danger ms-3">
                            Xóa đã chọn
                        </button>
                        <a href="<?php echo isset($_SESSION['ADMIN']) ? '../../index.php' : '../../../index.php'; ?>"
                            class="btn btn-outline-primary ms-3">
                            <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua hàng
                        </a>
                    </div>

                    <div class="right-actions d-flex align-items-center">
                        <div class="total-section me-4">
                            <span class="me-2">Tổng tiền:</span>
                            <span class="total-amount fw-bold text-danger fs-4">
                                <?php echo number_format($totalAmount, 0, ',', '.'); ?> ₫
                            </span>
                        </div>

                        <?php if ($hasUnavailableProducts): ?>
                            <div class="alert alert-warning d-inline-block me-3" style="margin-bottom: 0;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Chú ý:</strong> Giỏ hàng có sản phẩm hết hàng/ngừng bán.
                            </div>
                            <button disabled class="btn btn-secondary btn-lg">
                                Mua hàng (Đang chờ sửa)
                            </button>
                        <?php else: ?>
                            <button onclick="proceedToCheckout()" class="btn btn-primary btn-lg">
                                Mua hàng
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($orderHistory)): ?>
    <div class="cart-container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-history me-2"></i>Lịch sử đơn hàng</h3>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderHistory as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['ma_don_hang_text']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                            <td><span class="text-danger fw-bold"><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫</span></td>
                            <td>
                                <?php
                                switch ($order['trang_thai']) {
                                    case 'pending': echo '<span class="badge bg-warning text-dark">Chờ xác nhận</span>'; break;
                                    case 'approved': echo '<span class="badge bg-info">Đang giao hàng</span>'; break;
                                    case 'delivered': echo '<span class="badge bg-primary">Đã giao hàng</span>'; break;
                                    case 'completed': echo '<span class="badge bg-success">Hoàn tất</span>'; break;
                                    case 'cancelled': echo '<span class="badge bg-danger">Đã hủy</span>'; break;
                                    case 'returned': echo '<span class="badge bg-secondary">Đã trả hàng</span>'; break;
                                    default: echo '<span class="badge bg-secondary">Không xác định</span>'; break;
                                }
                                ?>
                            </td>
                            <td>
                                <a href="orderDetailView.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public_files/toast-notification.js"></script>
    <script>
        function proceedToCheckout() {
            const selectedProducts = [];
            const unavailableSelected = [];
            document.querySelectorAll('.product-select:checked').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const productId = row.querySelector('.quantity-input').dataset.productId;
                const quantity = parseInt(row.querySelector('.quantity-input').value);
                const statusBadge = row.querySelector('.badge.bg-danger, .badge.bg-warning');
                if (statusBadge) {
                    unavailableSelected.push(row.querySelector('.product-name').textContent.trim());
                } else {
                    selectedProducts.push({ productId: productId, quantity: quantity });
                }
            });
            if (unavailableSelected.length > 0) {
                toast.error('⚠️ Không thể thanh toán sản phẩm hết hàng/ngừng bán:\n' + unavailableSelected.join(', '));
                return;
            }
            if (selectedProducts.length === 0) {
                toast.warning('Vui lòng chọn ít nhất một sản phẩm để mua');
                return;
            }
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'checkout.php';
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_products';
            input.value = JSON.stringify(selectedProducts);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function updateTotalPrice() {
            let total = 0;
            document.querySelectorAll('.cart-table tbody tr').forEach(row => {
                if (row.querySelector('.product-select').checked) {
                    const subtotal = parseInt(row.querySelector('.subtotal').textContent.replace(/[^\d]/g, ''));
                    total += subtotal;
                }
            });
            document.querySelector('.total-amount').textContent = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
        }

        document.querySelectorAll('.product-select, #select-all, #select-all-bottom').forEach(checkbox => {
            checkbox.addEventListener('change', updateTotalPrice);
        });

        document.querySelectorAll('.quantity-controls').forEach(control => {
            const decreaseBtn = control.querySelector('.decrease-quantity');
            const increaseBtn = control.querySelector('.increase-quantity');
            const input = control.querySelector('.quantity-input');
            const productId = input.dataset.productId;
            decreaseBtn.addEventListener('click', () => updateQuantity(productId, -1, input));
            increaseBtn.addEventListener('click', () => updateQuantity(productId, 1, input));
            input.addEventListener('change', () => {
                let value = parseInt(input.value);
                if (value < 1) value = 1;
                input.value = value;
                updateQuantity(productId, 0, input);
            });
        });

        async function updateQuantity(productId, change, input) {
            const currentValue = parseInt(input.value);
            let newValue = change === 0 ? currentValue : currentValue + change;
            if (newValue < 1) newValue = 1;
            try {
                const response = await fetch('giohangUpdate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productId: productId, quantity: newValue })
                });
                const data = await response.json();
                if (data.success) {
                    input.value = newValue;
                    const row = input.closest('tr');
                    const price = parseInt(row.querySelector('.price').dataset.price);
                    const subtotal = price * newValue;
                    row.querySelector('.subtotal').textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + ' ₫';
                    updateTotalPrice();
                } else {
                    if (data.outOfStock) {
                        toast.error(data.message);
                        input.value = currentValue;
                    } else if (data.availableQuantity !== undefined) {
                        toast.warning(data.message);
                        input.value = data.availableQuantity;
                        const row = input.closest('tr');
                        const price = parseInt(row.querySelector('.price').dataset.price);
                        const subtotal = price * data.availableQuantity;
                        row.querySelector('.subtotal').textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + ' ₫';
                        updateTotalPrice();
                    } else {
                        toast.error(data.message || 'Có lỗi xảy ra');
                        input.value = currentValue;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                toast.error('Có lỗi xảy ra');
                input.value = currentValue;
            }
        }

        async function deleteSelectedItems() {
            const selectedProducts = [];
            document.querySelectorAll('.product-select:checked').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const productId = row.querySelector('.quantity-input').dataset.productId;
                selectedProducts.push(productId);
            });
            if (selectedProducts.length === 0) {
                toast.warning('Vui lòng chọn sản phẩm để xóa');
                return;
            }
            if (confirm('Bạn có chắc chắn muốn xóa các sản phẩm đã chọn?')) {
                try {
                    const response = await fetch('giohangAct.php?action=removeSelected', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ productIds: selectedProducts })
                    });
                    const data = await response.json();
                    if (data.success) {
                        selectedProducts.forEach(productId => {
                            const row = document.querySelector(`input[data-product-id="${productId}"]`).closest('tr');
                            row.remove();
                        });
                        updateTotalPrice();
                        document.querySelectorAll('#select-all, #select-all-bottom').forEach(checkbox => { checkbox.checked = false; });
                        toast.success('Đã xóa sản phẩm khỏi giỏ hàng');
                    } else {
                        toast.error('Có lỗi xảy ra!');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    toast.error('Có lỗi xảy ra!');
                }
            }
        }

        async function removeProductFromCart(productId, productName) {
            if (confirm(`Bạn có chắc chắn muốn xóa "${productName}" khỏi giỏ hàng?`)) {
                try {
                    const response = await fetch('giohangAct.php?action=removeSelected', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ productIds: [productId] })
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            const row = document.querySelector(`input[data-product-id="${productId}"]`);
                            if (row) row.closest('tr').remove();
                            toast.success('Đã xóa sản phẩm');
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        toast.error('Có lỗi xảy ra!');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    toast.error('Có lỗi xảy ra!');
                }
            }
        }

        document.querySelectorAll('#select-all, #select-all-bottom').forEach(selectAll => {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('#select-all, #select-all-bottom').forEach(checkbox => { checkbox.checked = isChecked; });
                document.querySelectorAll('.product-select').forEach(checkbox => { checkbox.checked = isChecked; });
                updateTotalPrice();
            });
        });

        document.querySelectorAll('.product-select').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(document.querySelectorAll('.product-select')).every(cb => cb.checked);
                document.querySelectorAll('#select-all, #select-all-bottom').forEach(selectAll => { selectAll.checked = allChecked; });
                updateTotalPrice();
            });
        });
    </script>
    
    <!-- CSRF Protection Helper -->
    <script src="../../../public_files/js/csrf-helper.js"></script>
</body>
</html>