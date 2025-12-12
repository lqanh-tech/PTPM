<?php
/**
 * Trang quản lý mã Coupon (Giảm giá) - Admin
 */

require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/CouponCls.php';
require_once __DIR__ . '/../mod/sessionManager.php';

SessionManager::start();

// Kiểm tra quyền admin
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    header('Location: ../../userLogin.php');
    exit();
}

$couponManager = new Coupon();
$message = '';
$messageType = 'success';

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $data = [
                    'code' => $_POST['code'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'discount_type' => $_POST['discount_type'],
                    'discount_value' => floatval($_POST['discount_value']),
                    'max_discount' => $_POST['max_discount'] ? floatval($_POST['max_discount']) : null,
                    'min_order_value' => floatval($_POST['min_order_value'] ?? 0),
                    'usage_limit' => $_POST['usage_limit'] ? intval($_POST['usage_limit']) : null,
                    'usage_per_user' => intval($_POST['usage_per_user'] ?? 1),
                    'start_date' => $_POST['start_date'] ?: null,
                    'end_date' => $_POST['end_date'] ?: null,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'created_by' => $_SESSION['ADMIN'] ?? $_SESSION['USER']
                ];
                
                $couponManager->createCoupon($data);
                $message = '✅ Tạo mã giảm giá thành công!';
                break;
                
            case 'update':
                $id = intval($_POST['id']);
                $data = [
                    'code' => $_POST['code'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'discount_type' => $_POST['discount_type'],
                    'discount_value' => floatval($_POST['discount_value']),
                    'max_discount' => $_POST['max_discount'] ? floatval($_POST['max_discount']) : null,
                    'min_order_value' => floatval($_POST['min_order_value'] ?? 0),
                    'usage_limit' => $_POST['usage_limit'] ? intval($_POST['usage_limit']) : null,
                    'usage_per_user' => intval($_POST['usage_per_user'] ?? 1),
                    'start_date' => $_POST['start_date'] ?: null,
                    'end_date' => $_POST['end_date'] ?: null,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                $couponManager->updateCoupon($id, $data);
                $message = '✅ Cập nhật mã giảm giá thành công!';
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $couponManager->deleteCoupon($id);
                $message = '✅ Xóa mã giảm giá thành công!';
                break;
                
            case 'toggle':
                $id = intval($_POST['id']);
                $couponManager->toggleStatus($id);
                $message = '✅ Đã thay đổi trạng thái!';
                break;
        }
    } catch (Exception $e) {
        $message = '❌ Lỗi: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Lấy danh sách coupon
$coupons = $couponManager->getAllCoupons(true);
$stats = $couponManager->getCouponStats();

// Lấy coupon để edit (nếu có)
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editCoupon = $couponManager->getCouponById(intval($_GET['edit']));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý mã giảm giá (Coupon)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff; padding: 30px; border-radius: 10px; margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .coupon-card {
            background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); border-left: 4px solid #28a745;
            transition: all 0.3s;
        }
        .coupon-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .coupon-card.inactive { border-left-color: #dc3545; opacity: 0.7; }
        .coupon-code {
            font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
        }
        .badge-percent { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .badge-fixed { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .form-section { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; }
        .btn-success:hover { background: linear-gradient(135deg, #218838 0%, #1aa179 100%); }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="page-header">
            <h2><i class="fas fa-ticket-alt me-2"></i>Quản lý mã giảm giá (Coupon)</h2>
            <p class="mb-0">Tạo và quản lý các mã giảm giá cho khách hàng</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType == 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary text-white me-3">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['total'] ?></h3>
                            <small class="text-muted">Tổng mã</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success text-white me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['active'] ?></h3>
                            <small class="text-muted">Đang hoạt động</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info text-white me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= number_format($stats['total_usage']) ?></h3>
                            <small class="text-muted">Lượt sử dụng</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning text-white me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= number_format($stats['total_discount']) ?>đ</h3>
                            <small class="text-muted">Tổng giảm giá</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Form tạo/sửa coupon -->
            <div class="col-md-4">
                <div class="form-section">
                    <h5 class="mb-4">
                        <i class="fas fa-<?= $editCoupon ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $editCoupon ? 'Sửa mã giảm giá' : 'Tạo mã giảm giá mới' ?>
                    </h5>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $editCoupon ? 'update' : 'create' ?>">
                        <?php if ($editCoupon): ?>
                        <input type="hidden" name="id" value="<?= $editCoupon->id ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Mã coupon <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required 
                                   style="text-transform: uppercase; font-weight: bold; letter-spacing: 2px;"
                                   placeholder="VD: SALE10, FREESHIP"
                                   value="<?= htmlspecialchars($editCoupon->code ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên mã <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="VD: Giảm 10% đơn hàng"
                                   value="<?= htmlspecialchars($editCoupon->name ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Mô tả chi tiết điều kiện áp dụng"><?= htmlspecialchars($editCoupon->description ?? '') ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Loại giảm <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-select" id="discount_type">
                                    <option value="percent" <?= ($editCoupon->discount_type ?? '') == 'percent' ? 'selected' : '' ?>>Giảm %</option>
                                    <option value="fixed" <?= ($editCoupon->discount_type ?? '') == 'fixed' ? 'selected' : '' ?>>Giảm tiền</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Giá trị <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="discount_value" class="form-control" required
                                           min="0" step="0.01"
                                           value="<?= $editCoupon->discount_value ?? '' ?>">
                                    <span class="input-group-text" id="discount_unit">%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="max_discount_group">
                            <label class="form-label">Giảm tối đa (VNĐ)</label>
                            <input type="number" name="max_discount" class="form-control"
                                   placeholder="Để trống = không giới hạn"
                                   value="<?= $editCoupon->max_discount ?? '' ?>">
                            <small class="text-muted">Chỉ áp dụng cho loại giảm %</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Đơn hàng tối thiểu (VNĐ)</label>
                            <input type="number" name="min_order_value" class="form-control"
                                   value="<?= $editCoupon->min_order_value ?? 0 ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Số lượt dùng</label>
                                <input type="number" name="usage_limit" class="form-control"
                                       placeholder="Không giới hạn"
                                       value="<?= $editCoupon->usage_limit ?? '' ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Lượt/người</label>
                                <input type="number" name="usage_per_user" class="form-control"
                                       value="<?= $editCoupon->usage_per_user ?? 1 ?>" min="1">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Ngày bắt đầu</label>
                                <input type="datetime-local" name="start_date" class="form-control"
                                       value="<?= ($editCoupon && $editCoupon->start_date) ? date('Y-m-d\TH:i', strtotime($editCoupon->start_date)) : '' ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Ngày kết thúc</label>
                                <input type="datetime-local" name="end_date" class="form-control"
                                       value="<?= ($editCoupon && $editCoupon->end_date) ? date('Y-m-d\TH:i', strtotime($editCoupon->end_date)) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       <?= ($editCoupon->is_active ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Kích hoạt ngay</label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i><?= $editCoupon ? 'Cập nhật' : 'Tạo mã' ?>
                            </button>
                            <?php if ($editCoupon): ?>
                            <a href="?req=coupon" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Danh sách coupon -->
            <div class="col-md-8">
                <div class="form-section">
                    <h5 class="mb-4"><i class="fas fa-list me-2"></i>Danh sách mã giảm giá</h5>
                    
                    <?php if (empty($coupons)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Chưa có mã giảm giá nào. Hãy tạo mã đầu tiên!
                    </div>
                    <?php else: ?>
                    <?php foreach ($coupons as $coupon): 
                        $isExpired = $coupon->end_date && strtotime($coupon->end_date) < time();
                        $isNotStarted = $coupon->start_date && strtotime($coupon->start_date) > time();
                        $isOutOfStock = $coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit;
                    ?>
                    <div class="coupon-card <?= !$coupon->is_active ? 'inactive' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="coupon-code"><?= htmlspecialchars($coupon->code) ?></div>
                                <h6 class="mt-2 mb-1"><?= htmlspecialchars($coupon->name) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($coupon->description) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $coupon->discount_type == 'percent' ? 'badge-percent' : 'badge-fixed' ?>">
                                    <?php if ($coupon->discount_type == 'percent'): ?>
                                        -<?= $coupon->discount_value ?>%
                                        <?php if ($coupon->max_discount): ?>
                                        <br><small>Max: <?= number_format($coupon->max_discount) ?>đ</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -<?= number_format($coupon->discount_value) ?>đ
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mt-3 text-muted small">
                            <div class="col-4">
                                <i class="fas fa-shopping-cart me-1"></i>
                                Đơn tối thiểu: <?= number_format($coupon->min_order_value) ?>đ
                            </div>
                            <div class="col-4">
                                <i class="fas fa-users me-1"></i>
                                Đã dùng: <?= $coupon->usage_count ?><?= $coupon->usage_limit ? '/' . $coupon->usage_limit : '' ?>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-calendar me-1"></i>
                                <?php if ($coupon->end_date): ?>
                                    HSD: <?= date('d/m/Y', strtotime($coupon->end_date)) ?>
                                <?php else: ?>
                                    Không giới hạn
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Trạng thái -->
                        <div class="mt-2">
                            <?php if (!$coupon->is_active): ?>
                                <span class="badge bg-secondary">Đã tắt</span>
                            <?php elseif ($isExpired): ?>
                                <span class="badge bg-danger">Hết hạn</span>
                            <?php elseif ($isNotStarted): ?>
                                <span class="badge bg-warning text-dark">Chưa bắt đầu</span>
                            <?php elseif ($isOutOfStock): ?>
                                <span class="badge bg-danger">Hết lượt</span>
                            <?php else: ?>
                                <span class="badge bg-success">Đang hoạt động</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="mt-3 d-flex gap-2">
                            <a href="?req=coupon&edit=<?= $coupon->id ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $coupon->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?= $coupon->is_active ? 'warning' : 'success' ?>">
                                    <i class="fas fa-<?= $coupon->is_active ? 'pause' : 'play' ?>"></i>
                                    <?= $coupon->is_active ? 'Tắt' : 'Bật' ?>
                                </button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa mã giảm giá này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $coupon->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle hiển thị max_discount dựa trên loại giảm
        document.getElementById('discount_type').addEventListener('change', function() {
            const maxDiscountGroup = document.getElementById('max_discount_group');
            const discountUnit = document.getElementById('discount_unit');
            
            if (this.value === 'percent') {
                maxDiscountGroup.style.display = 'block';
                discountUnit.textContent = '%';
            } else {
                maxDiscountGroup.style.display = 'none';
                discountUnit.textContent = 'đ';
            }
        });
        
        // Trigger on load
        document.getElementById('discount_type').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
