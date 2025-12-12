<?php
/**
 * Shipping Configuration Management
 * 
 * Admin module to manage:
 * - Shipping methods (standard, express, economy)
 * - Shipping fees by zone/weight/distance
 * - Shipping zones (supported areas)
 */

require_once __DIR__ . '/../mod/database.php';

// Check admin permission - must be called from index.php with proper session
if (!isset($_SESSION['ADMIN'])) {
    die('Access denied. Please login as admin.');
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_fee_details':
                $methodCode = $_GET['method_code'] ?? '';
                $stmt = $db->prepare("
                    SELECT sf.* 
                    FROM shipping_fees sf
                    JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
                    WHERE sm.code = ? AND sf.is_active = 1
                    ORDER BY sf.priority DESC
                ");
                $stmt->execute([$methodCode]);
                $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'fees' => $fees]);
                exit;
                
            case 'calculate_preview':
                $methodCode = $_GET['method_code'] ?? '';
                $weight = floatval($_GET['weight'] ?? 1);
                $orderValue = floatval($_GET['order_value'] ?? 0);
                $provinceId = intval($_GET['province_id'] ?? 1);
                
                // Get method info
                $stmt = $db->prepare("SELECT * FROM v_shipping_methods_with_fees WHERE code = ?");
                $stmt->execute([$methodCode]);
                $method = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$method) {
                    echo json_encode(['success' => false, 'error' => 'Method not found']);
                    exit;
                }
                
                // Calculate fee using function
                $stmt = $db->prepare("
                    SELECT calculate_shipping_fee(?, ?, 1, ?, ?) as fee
                ");
                $stmt->execute([$method['id'], $provinceId, $weight, $orderValue]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $finalFee = $result['fee'] ?? 0;
                
                // Get fee breakdown
                $stmt = $db->prepare("
                    SELECT base_fee, fee_per_kg, min_order_free_ship
                    FROM shipping_fees
                    WHERE shipping_method_id = ? AND is_active = 1
                    ORDER BY priority DESC
                    LIMIT 1
                ");
                $stmt->execute([$method['id']]);
                $feeConfig = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $baseFee = $feeConfig['base_fee'] ?? 0;
                $feePerKg = $feeConfig['fee_per_kg'] ?? 0;
                $freeThreshold = $feeConfig['min_order_free_ship'] ?? 0;
                
                $weightFee = $weight * $feePerKg;
                $totalBeforeDiscount = $baseFee + $weightFee;
                $isFreeShipping = ($freeThreshold > 0 && $orderValue >= $freeThreshold);
                
                echo json_encode([
                    'success' => true,
                    'method_name' => $method['name'],
                    'delivery_time' => $method['delivery_time'],
                    'base_fee' => $baseFee,
                    'weight_fee' => $weightFee,
                    'total_before_discount' => $totalBeforeDiscount,
                    'is_free_shipping' => $isFreeShipping,
                    'free_threshold' => $freeThreshold,
                    'final_fee' => $finalFee
                ]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_sort_order':
                // Handle AJAX request for drag & drop sort order update
                $updates = json_decode($_POST['updates'] ?? '[]', true);
                foreach ($updates as $update) {
                    $stmt = $db->prepare("UPDATE shipping_methods SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$update['sortOrder'], $update['id']]);
                }
                echo json_encode(['success' => true]);
                exit;
                
            case 'add_shipping_method':
                $stmt = $db->prepare("
                    INSERT INTO shipping_methods (code, name, description, delivery_time, price_multiplier, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['delivery_time'],
                    $_POST['price_multiplier'],
                    $_POST['sort_order']
                ]);
                $message = 'Thêm phương thức vận chuyển thành công!';
                $messageType = 'success';
                break;
                
            case 'update_shipping_method':
                $stmt = $db->prepare("
                    UPDATE shipping_methods 
                    SET name = ?, description = ?, delivery_time = ?, price_multiplier = ?, sort_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['delivery_time'],
                    $_POST['price_multiplier'],
                    $_POST['sort_order'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['method_id']
                ]);
                $message = 'Cập nhật phương thức vận chuyển thành công!';
                $messageType = 'success';
                break;
                
            case 'add_shipping_fee':
                $stmt = $db->prepare("
                    INSERT INTO shipping_fees 
                    (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, 
                     fee_per_kg, order_value_from, order_value_to, min_order_free_ship, priority)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    !empty($_POST['province_id']) ? $_POST['province_id'] : null,
                    !empty($_POST['district_id']) ? $_POST['district_id'] : null,
                    !empty($_POST['shipping_method_id']) ? $_POST['shipping_method_id'] : null,
                    $_POST['base_fee'],
                    $_POST['weight_from'],
                    !empty($_POST['weight_to']) ? $_POST['weight_to'] : null,
                    $_POST['fee_per_kg'],
                    $_POST['order_value_from'],
                    !empty($_POST['order_value_to']) ? $_POST['order_value_to'] : null,
                    !empty($_POST['min_order_free_ship']) ? $_POST['min_order_free_ship'] : null,
                    $_POST['priority']
                ]);
                $message = 'Thêm cấu hình phí vận chuyển thành công!';
                $messageType = 'success';
                break;
                
            case 'update_shipping_fee':
                $stmt = $db->prepare("
                    UPDATE shipping_fees 
                    SET name = ?, province_id = ?, district_id = ?, shipping_method_id = ?, 
                        base_fee = ?, weight_from = ?, weight_to = ?, fee_per_kg = ?,
                        order_value_from = ?, order_value_to = ?, min_order_free_ship = ?, 
                        priority = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    !empty($_POST['province_id']) ? $_POST['province_id'] : null,
                    !empty($_POST['district_id']) ? $_POST['district_id'] : null,
                    !empty($_POST['shipping_method_id']) ? $_POST['shipping_method_id'] : null,
                    $_POST['base_fee'],
                    $_POST['weight_from'],
                    !empty($_POST['weight_to']) ? $_POST['weight_to'] : null,
                    $_POST['fee_per_kg'],
                    $_POST['order_value_from'],
                    !empty($_POST['order_value_to']) ? $_POST['order_value_to'] : null,
                    !empty($_POST['min_order_free_ship']) ? $_POST['min_order_free_ship'] : null,
                    $_POST['priority'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['fee_id']
                ]);
                $message = 'Cập nhật cấu hình phí thành công!';
                $messageType = 'success';
                break;
                
            case 'delete_shipping_fee':
                $stmt = $db->prepare("DELETE FROM shipping_fees WHERE id = ?");
                $stmt->execute([$_POST['fee_id']]);
                $message = 'Xóa cấu hình phí thành công!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get data for display - SỬ DỤNG VIEW MỚI
$shippingMethods = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order DESC")->fetchAll(PDO::FETCH_ASSOC);
$shippingFees = $db->query("SELECT * FROM v_shipping_fees_detail ORDER BY priority DESC")->fetchAll(PDO::FETCH_ASSOC);
$provinces = $db->query("SELECT id, name FROM provinces WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Cấu hình Vận chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset và base styles */
        * { box-sizing: border-box; }
        
        /* Container chính */
        .shipping-config-container {
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            overflow-x: auto;
        }
        
        /* Header */
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        
        /* Cards */
        .config-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .config-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .config-card-body {
            padding: 20px;
        }
        
        /* Tables */
        .config-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .config-table thead th {
            background: #f8f9fa;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .config-table tbody td {
            padding: 12px 8px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .config-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-inactive {
            background: #dc3545;
            color: white;
        }
        
        .badge.bg-info {
            background: #17a2b8 !important;
            color: white;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-light {
            background: white;
            color: #667eea;
            border: 1px solid white;
        }
        
        .btn-light:hover {
            background: rgba(255,255,255,0.9);
        }
        
        /* Alert messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .config-table {
                font-size: 12px;
            }
            
            .config-table thead th,
            .config-table tbody td {
                padding: 8px 4px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
        
        /* Table wrapper for horizontal scroll */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Code tags */
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e83e8c;
            font-size: 12px;
        }
        
        /* Drag and Drop */
        .draggable-row {
            cursor: move;
            transition: all 0.3s;
        }
        
        .draggable-row:hover {
            background: #f8f9fa;
        }
        
        .draggable-row.dragging {
            opacity: 0.5;
        }
        
        /* Info button */
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        /* Success button */
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        /* Link button */
        .btn-link {
            color: #667eea;
            text-decoration: none;
        }
        
        .btn-link:hover {
            color: #5568d3;
        }
        
        /* Tooltip custom */
        .tooltip-inner {
            max-width: 300px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="shipping-config-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-shipping-fast"></i> Quản lý Cấu hình Vận chuyển</h1>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <span><?= htmlspecialchars($message) ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Shipping Methods -->
        <div class="config-card">
            <div class="config-card-header">
                <span><i class="fas fa-truck"></i> Phương thức vận chuyển</span>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                    <i class="fas fa-plus"></i> Thêm mới
                </button>
            </div>
            <div class="config-card-body">
                <div class="table-responsive">
                    <table class="config-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên</th>
                            <th>Mô tả</th>
                            <th>Thời gian giao</th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Phí hiện tại khách hàng sẽ thấy trên checkout">
                                    Phí hiện tại <i class="fas fa-info-circle text-muted" style="font-size: 12px;"></i>
                                </span>
                            </th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Số cấu hình phí đang áp dụng cho phương thức này">
                                    Cấu hình <i class="fas fa-info-circle text-muted" style="font-size: 12px;"></i>
                                </span>
                            </th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Số càng cao, phương thức được ưu tiên hiển thị trước">
                                    Thứ tự <i class="fas fa-info-circle text-muted" style="font-size: 12px;"></i>
                                </span>
                            </th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shippingMethods as $method): ?>
                        <tr draggable="true" data-id="<?= $method['id'] ?? 0 ?>" class="draggable-row">
                            <td><code><?= htmlspecialchars($method['code'] ?? '') ?></code></td>
                            <td><strong><?= htmlspecialchars($method['name'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($method['description'] ?? '') ?></td>
                            <td>
                                <?php 
                                $deliveryTime = $method['delivery_time'] ?? '';
                                if ($deliveryTime && !stripos($deliveryTime, 'ngày làm việc')) {
                                    echo htmlspecialchars($deliveryTime) . ' <small class="text-muted">(ngày làm việc)</small>';
                                } else {
                                    echo htmlspecialchars($deliveryTime);
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $baseFee = $method['min_base_fee'] ?? 0;
                                $freeThreshold = $method['min_free_ship_threshold'] ?? 0;
                                $configCount = $method['fee_config_count'] ?? 0;
                                
                                if ($configCount == 0) {
                                    echo "<span class='text-danger' data-bs-toggle='tooltip' title='Chưa có cấu hình phí!'>";
                                    echo "<i class='fas fa-exclamation-triangle'></i> Chưa cấu hình";
                                    echo "</span>";
                                } elseif ($baseFee == 0) {
                                    echo "<strong style='color: green;'>Miễn phí</strong>";
                                } else {
                                    echo "<strong style='color: #2c3e50;'>" . number_format($baseFee, 0, ',', '.') . "₫</strong>";
                                    if ($freeThreshold > 0) {
                                        echo " → <span style='color: green;'>Miễn phí</span>";
                                        echo "<br><small class='text-muted'>≥ " . number_format($freeThreshold, 0, ',', '.') . "₫</small>";
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-info" data-bs-toggle="tooltip" title="<?= $configCount ?> cấu hình phí đang áp dụng">
                                    <?= $configCount ?>
                                </span>
                                <?php if ($configCount > 0): ?>
                                <button class="btn btn-sm btn-link p-0 ms-1" onclick="showFeeDetails('<?= $method['code'] ?>')" title="Xem chi tiết">
                                    <i class="fas fa-list"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $method['sort_order'] ?? 0 ?></span>
                                <i class="fas fa-grip-vertical text-muted ms-2" style="cursor: move;" title="Kéo để sắp xếp"></i>
                            </td>
                            <td>
                                <span class="badge <?= ($method['is_active'] ?? 0) ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ($method['is_active'] ?? 0) ? 'Hoạt động' : 'Tắt' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editMethod(<?= htmlspecialchars(json_encode($method)) ?>)" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="previewOnCheckout('<?= $method['code'] ?>')" title="Xem trước trên checkout">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div> <!-- /table-responsive -->
            </div> <!-- /config-card-body -->
        </div> <!-- /config-card -->

        <!-- Shipping Fees -->
        <div class="config-card">
            <div class="config-card-header">
                <span><i class="fas fa-dollar-sign"></i> Cấu hình phí vận chuyển</span>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                    <i class="fas fa-plus"></i> Thêm mới
                </button>
            </div>
            <div class="config-card-body">
                <div class="table-responsive">
                    <table class="config-table">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Tỉnh/TP</th>
                            <th>Quận/Huyện</th>
                            <th>Phương thức</th>
                            <th>Phí cơ bản</th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Phí tính thêm theo trọng lượng. 0đ = Không tính thêm phí theo trọng lượng">
                                    Phí/kg <i class="fas fa-info-circle text-muted" style="font-size: 12px;"></i>
                                </span>
                            </th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Miễn phí vận chuyển khi đơn hàng đạt giá trị này">
                                    Miễn phí từ <i class="fas fa-info-circle text-muted" style="font-size: 12px;"></i>
                                </span>
                            </th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Số càng cao, phương thức được ưu tiên áp dụng trước">
                                    Ưu tiên <i class="fas fa-info-circle text-muted" style="font-size: 12px;"></i>
                                </span>
                            </th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shippingFees as $fee): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($fee['name'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($fee['province_name'] ?? '') ?: '<em>Tất cả</em>' ?></td>
                            <td><?= htmlspecialchars($fee['district_name'] ?? '') ?: '<em>Tất cả</em>' ?></td>
                            <td><?= htmlspecialchars($fee['shipping_method_name'] ?? '') ?: '<em>Tất cả</em>' ?></td>
                            <td><strong><?= number_format($fee['base_fee'] ?? 0, 0, ',', '.') ?>₫</strong></td>
                            <td>
                                <?php if (($fee['fee_per_kg'] ?? 0) == 0): ?>
                                    <span class="text-muted" data-bs-toggle="tooltip" title="Không tính thêm phí theo trọng lượng">
                                        0₫ <i class="fas fa-check-circle text-success"></i>
                                    </span>
                                <?php else: ?>
                                    <?= number_format($fee['fee_per_kg'], 0, ',', '.') ?>₫
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($fee['min_order_free_ship'] ?? 0): ?>
                                    <span class="text-success" data-bs-toggle="tooltip" title="Miễn phí vận chuyển khi đơn hàng ≥ <?= number_format($fee['min_order_free_ship'], 0, ',', '.') ?>₫">
                                        <strong>≥ <?= number_format($fee['min_order_free_ship'], 0, ',', '.') ?>₫</strong>
                                        <i class="fas fa-gift"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info" data-bs-toggle="tooltip" title="Số càng cao, phương thức được ưu tiên áp dụng trước">
                                    <?= $fee['priority'] ?? 0 ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ($fee['is_active'] ?? 0) ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ($fee['is_active'] ?? 0) ? 'Hoạt động' : 'Tắt' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editFee(<?= $fee['id'] ?? 0 ?>)" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteFee(<?= $fee['id'] ?? 0 ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div> <!-- /table-responsive -->
            </div> <!-- /config-card-body -->
        </div> <!-- /config-card -->
    </div> <!-- /shipping-config-container -->

    <!-- Add Method Modal -->
    <div class="modal fade" id="addMethodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_shipping_method">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm phương thức vận chuyển</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mã <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thời gian giao hàng</label>
                            <input type="text" name="delivery_time" class="form-control" placeholder="VD: 2-3 ngày">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hệ số giá</label>
                            <input type="number" name="price_multiplier" class="form-control" step="0.1" value="1.0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thứ tự hiển thị</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm mới</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Fee Modal -->
    <div class="modal fade" id="addFeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_shipping_fee">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm cấu hình phí vận chuyển</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên cấu hình <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Độ ưu tiên</label>
                                <input type="number" name="priority" class="form-control" value="0">
                                <small class="text-muted">Số cao hơn = ưu tiên hơn</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tỉnh/TP</label>
                                <select name="province_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($provinces as $province): ?>
                                    <option value="<?= $province['id'] ?? 0 ?>"><?= htmlspecialchars($province['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quận/Huyện</label>
                                <select name="district_id" class="form-select">
                                    <option value="">Tất cả</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phương thức</label>
                                <select name="shipping_method_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($shippingMethods as $method): ?>
                                    <option value="<?= $method['id'] ?? 0 ?>"><?= htmlspecialchars($method['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phí cơ bản (VNĐ)</label>
                                <input type="number" name="base_fee" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phí mỗi kg (VNĐ)</label>
                                <input type="number" name="fee_per_kg" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Từ trọng lượng (kg)</label>
                                <input type="number" name="weight_from" class="form-control" step="0.1" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Đến trọng lượng (kg)</label>
                                <input type="number" name="weight_to" class="form-control" step="0.1" placeholder="Để trống = không giới hạn">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Miễn phí từ (VNĐ)</label>
                                <input type="number" name="min_order_free_ship" class="form-control" placeholder="VD: 500000">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Từ giá trị đơn (VNĐ)</label>
                                <input type="number" name="order_value_from" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Đến giá trị đơn (VNĐ)</label>
                                <input type="number" name="order_value_to" class="form-control" placeholder="Để trống = không giới hạn">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm mới</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize drag and drop for shipping methods
            initDragAndDrop();
        });
        
        // Drag and Drop functionality
        function initDragAndDrop() {
            const draggableRows = document.querySelectorAll('.draggable-row');
            let draggedElement = null;
            
            draggableRows.forEach(row => {
                row.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.style.opacity = '0.5';
                });
                
                row.addEventListener('dragend', function(e) {
                    this.style.opacity = '';
                });
                
                row.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (draggedElement !== this) {
                        this.style.borderTop = '2px solid #667eea';
                    }
                });
                
                row.addEventListener('dragleave', function(e) {
                    this.style.borderTop = '';
                });
                
                row.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.borderTop = '';
                    
                    if (draggedElement !== this) {
                        // Swap rows
                        const allRows = [...this.parentNode.children];
                        const draggedIndex = allRows.indexOf(draggedElement);
                        const targetIndex = allRows.indexOf(this);
                        
                        if (draggedIndex < targetIndex) {
                            this.parentNode.insertBefore(draggedElement, this.nextSibling);
                        } else {
                            this.parentNode.insertBefore(draggedElement, this);
                        }
                        
                        // Update sort order
                        updateSortOrder();
                    }
                });
            });
        }
        
        function updateSortOrder() {
            const rows = document.querySelectorAll('.draggable-row');
            const updates = [];
            
            rows.forEach((row, index) => {
                const id = row.dataset.id;
                const sortOrder = rows.length - index; // Higher number = higher priority
                updates.push({ id, sortOrder });
            });
            
            // Send AJAX request to update sort order
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_sort_order&updates=' + JSON.stringify(updates)
            }).then(response => {
                if (response.ok) {
                    console.log('Sort order updated successfully');
                }
            });
        }
        
        // Show fee details for a method
        function showFeeDetails(methodCode) {
            // Get method name
            const methodName = document.querySelector(`code:contains('${methodCode}')`);
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <h5 class="modal-title"><i class="fas fa-list"></i> Chi tiết cấu hình phí: ${methodCode}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="fee-details-content">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p>Đang tải...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Load fee details via AJAX
            fetch('?action=get_fee_details&method_code=' + methodCode)
                .then(response => response.json())
                .then(data => {
                    let html = '<table class="table table-bordered">';
                    html += '<thead><tr><th>Tên</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Ưu tiên</th></tr></thead>';
                    html += '<tbody>';
                    
                    if (data.fees && data.fees.length > 0) {
                        data.fees.forEach(fee => {
                            html += '<tr>';
                            html += '<td>' + fee.name + '</td>';
                            html += '<td><strong>' + formatMoney(fee.base_fee) + '₫</strong></td>';
                            html += '<td>' + formatMoney(fee.fee_per_kg) + '₫</td>';
                            html += '<td>' + (fee.min_order_free_ship > 0 ? '≥ ' + formatMoney(fee.min_order_free_ship) + '₫' : '-') + '</td>';
                            html += '<td><span class="badge bg-info">' + fee.priority + '</span></td>';
                            html += '</tr>';
                        });
                    } else {
                        html += '<tr><td colspan="5" class="text-center text-muted">Chưa có cấu hình phí</td></tr>';
                    }
                    
                    html += '</tbody></table>';
                    document.getElementById('fee-details-content').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('fee-details-content').innerHTML = '<div class="alert alert-danger">Lỗi tải dữ liệu</div>';
                });
            
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        }
        
        // Preview on checkout
        function previewOnCheckout(methodCode) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <h5 class="modal-title"><i class="fas fa-calculator"></i> Xem trước trên Checkout: ${methodCode}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-cog"></i> Thông tin đơn hàng</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Tỉnh/Thành phố</label>
                                        <select id="preview-province" class="form-select">
                                            <option value="1">Hồ Chí Minh</option>
                                            <option value="2">Hà Nội</option>
                                            <option value="3">Đà Nẵng</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Trọng lượng (kg)</label>
                                        <input type="number" id="preview-weight" class="form-control" value="1" step="0.1" min="0.1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Giá trị đơn hàng (VNĐ)</label>
                                        <input type="number" id="preview-value" class="form-control" value="300000" step="1000">
                                    </div>
                                    <button class="btn btn-primary w-100" onclick="calculatePreviewFee('${methodCode}')">
                                        <i class="fas fa-calculator"></i> Tính phí
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-shopping-cart"></i> Khách hàng sẽ thấy</h6>
                                    <div id="preview-checkout-result" style="border: 2px dashed #ccc; padding: 20px; border-radius: 10px; min-height: 300px;">
                                        <p class="text-muted text-center">Nhập thông tin và bấm "Tính phí" để xem kết quả</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        }
        
        function calculatePreviewFee(methodCode) {
            const weight = document.getElementById('preview-weight').value;
            const orderValue = document.getElementById('preview-value').value;
            const provinceId = document.getElementById('preview-province').value;
            
            const resultDiv = document.getElementById('preview-checkout-result');
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Đang tính...</p></div>';
            
            // Call API to calculate
            fetch(`?action=calculate_preview&method_code=${methodCode}&weight=${weight}&order_value=${orderValue}&province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 2px solid #667eea;">';
                    
                    // Method info
                    html += '<div style="display: flex; align-items: center; margin-bottom: 15px;">';
                    html += '<div style="width: 50px; height: 50px; background: #667eea; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">';
                    html += '<i class="fas fa-truck" style="color: white; font-size: 24px;"></i>';
                    html += '</div>';
                    html += '<div>';
                    html += '<strong style="font-size: 16px;">' + data.method_name + '</strong><br>';
                    html += '<small class="text-muted">' + data.delivery_time + '</small>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Fee breakdown
                    html += '<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
                    html += '<h6 style="margin-bottom: 10px;"><i class="fas fa-receipt"></i> Chi tiết tính phí:</h6>';
                    html += '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
                    html += '<span>Phí cơ bản:</span>';
                    html += '<strong>' + formatMoney(data.base_fee) + '₫</strong>';
                    html += '</div>';
                    html += '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
                    html += '<span>Phí theo trọng lượng:</span>';
                    html += '<strong>' + formatMoney(data.weight_fee) + '₫</strong>';
                    html += '</div>';
                    html += '<div style="border-top: 2px solid #eee; padding-top: 8px; margin-top: 8px;"></div>';
                    html += '<div style="display: flex; justify-content: space-between;">';
                    html += '<span>Tổng phí:</span>';
                    html += '<strong style="font-size: 18px;">' + formatMoney(data.total_before_discount) + '₫</strong>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Free shipping check
                    if (data.is_free_shipping) {
                        html += '<div style="background: #d4edda; padding: 15px; border-radius: 8px; border: 2px solid #28a745; margin-bottom: 15px;">';
                        html += '<h6 style="color: #155724; margin-bottom: 10px;"><i class="fas fa-gift"></i> MIỄN PHÍ VẬN CHUYỂN!</h6>';
                        html += '<p style="margin: 0; color: #155724;">Đơn hàng ' + formatMoney(orderValue) + '₫ ≥ ' + formatMoney(data.free_threshold) + '₫</p>';
                        html += '</div>';
                    }
                    
                    // Final fee
                    html += '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; text-align: center; color: white;">';
                    html += '<div style="font-size: 14px; margin-bottom: 5px;">Phí vận chuyển</div>';
                    html += '<div style="font-size: 32px; font-weight: bold;">';
                    if (data.final_fee == 0) {
                        html += 'Miễn phí';
                    } else {
                        html += formatMoney(data.final_fee) + '₫';
                    }
                    html += '</div>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Lỗi tính phí: ' + error.message + '</div>';
                });
        }
        
        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }
        
        // Preview shipping fee
        function previewShipping(methodId) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-calculator"></i> Xem trước phí vận chuyển</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tỉnh/Thành phố</label>
                                    <select id="preview-province" class="form-select">
                                        <option value="">Chọn tỉnh/thành</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Quận/Huyện</label>
                                    <select id="preview-district" class="form-select">
                                        <option value="">Chọn quận/huyện</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Trọng lượng (kg)</label>
                                    <input type="number" id="preview-weight" class="form-control" value="1" step="0.1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Giá trị đơn hàng (VNĐ)</label>
                                    <input type="number" id="preview-value" class="form-control" value="100000">
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="calculatePreview(${methodId})">
                                <i class="fas fa-calculator"></i> Tính phí
                            </button>
                            <div id="preview-result" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        }
        
        function calculatePreview(methodId) {
            const result = document.getElementById('preview-result');
            result.innerHTML = `
                <div class="alert alert-info">
                    <h5><i class="fas fa-shipping-fast"></i> Kết quả tính phí</h5>
                    <p><strong>Phí cơ bản:</strong> 30,000₫</p>
                    <p><strong>Phí theo trọng lượng:</strong> 10,000₫</p>
                    <p><strong>Tổng phí:</strong> <span class="text-success"><strong>40,000₫</strong></span></p>
                    <p class="mb-0"><small class="text-muted">* Đây là phí ước tính, phí thực tế có thể thay đổi</small></p>
                </div>
            `;
        }
        
        function editMethod(method) {
            alert('Chức năng chỉnh sửa đang được phát triển');
        }
        
        function editFee(feeId) {
            alert('Chức năng chỉnh sửa đang được phát triển');
        }
        
        function deleteFee(feeId) {
            if (confirm('Bạn có chắc muốn xóa cấu hình này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_shipping_fee">
                    <input type="hidden" name="fee_id" value="${feeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
