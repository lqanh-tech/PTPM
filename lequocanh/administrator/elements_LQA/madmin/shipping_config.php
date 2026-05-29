<?php
// Set UTF-8 encoding for proper Vietnamese character display
// Note: Header will be overridden for AJAX requests
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../mod/database.php';

if (!isset($_SESSION['ADMIN'])) {
    die('Access denied. Please login as admin.');
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

if (isset($_GET['action'])) {
    // Set JSON header for AJAX requests
    header('Content-Type: application/json; charset=UTF-8');
    
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
                
                // Get shipping method
                $stmt = $db->prepare("SELECT id, code, name, logo_url, api_endpoint, api_token, shop_id, is_active, priority, supports_tracking, supports_cod, config_json, created_at, updated_at FROM shipping_methods WHERE code = ? AND is_active = 1");
                $stmt->execute([$methodCode]);
                $method = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$method) {
                    echo json_encode(['success' => false, 'error' => 'Method not found']);
                    exit;
                }
                
                // Find matching fee configuration
                $stmt = $db->prepare("
                    SELECT sf.*
                    FROM shipping_fees sf
                    WHERE (sf.province_id = ? OR sf.province_id IS NULL)
                    AND sf.shipping_method_id = ?
                    AND sf.weight_from <= ?
                    AND (sf.weight_to IS NULL OR sf.weight_to >= ?)
                    AND sf.is_active = 1
                    ORDER BY sf.priority DESC, sf.province_id DESC
                    LIMIT 1
                ");
                $stmt->execute([$provinceId, $method['id'], $weight, $weight]);
                $feeConfig = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$feeConfig) {
                    echo json_encode(['success' => false, 'error' => 'No fee configuration found']);
                    exit;
                }
                
                // Calculate fee
                $baseFee = floatval($feeConfig['base_fee']);
                $feePerKg = floatval($feeConfig['fee_per_kg']);
                $freeThreshold = floatval($feeConfig['min_order_free_ship'] ?? 0);
                $weightFrom = floatval($feeConfig['weight_from']);
                
                // Calculate weight fee
                $extraWeight = max(0, $weight - $weightFrom);
                $weightFee = $extraWeight * $feePerKg;
                $totalBeforeDiscount = $baseFee + $weightFee;
                
                // Check free shipping
                $isFreeShipping = ($freeThreshold > 0 && $orderValue >= $freeThreshold);
                $finalFee = $isFreeShipping ? 0 : $totalBeforeDiscount;
                
                echo json_encode([
                    'success' => true,
                    'method_name' => $method['name'],
                    'delivery_time' => $method['delivery_time'],
                    'base_fee' => $baseFee,
                    'weight_fee' => $weightFee,
                    'total_before_discount' => $totalBeforeDiscount,
                    'is_free_shipping' => $isFreeShipping,
                    'free_threshold' => $freeThreshold,
                    'final_fee' => $finalFee,
                    'config_name' => $feeConfig['name']
                ]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_sort_order':

                $updates = json_decode($_POST['updates'] ?? '[]', true);
                foreach ($updates as $update) {
                    $stmt = $db->prepare("UPDATE shipping_methods SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$update['sortOrder'], $update['id']]);
                }
                echo json_encode(['success' => true]);
                exit;
                
            case 'add_shipping_method':
                $stmt = $db->prepare("
                    INSERT INTO shipping_methods (code, name, description, delivery_time, price_multiplier, is_active, supports_cod)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['delivery_time'],
                    $_POST['price_multiplier'],
                    isset($_POST['is_active']) ? 1 : 0,
                    isset($_POST['supports_cod']) ? 1 : 0
                ]);
                
                $newMethodId = $db->lastInsertId();
                $baseFee = floatval($_POST['base_fee'] ?? 0);
                $feePerKg = floatval($_POST['fee_per_kg'] ?? 0);
                $freeShip = !empty($_POST['min_order_free_ship']) ? floatval($_POST['min_order_free_ship']) : null;
                
                $insertFee = $db->prepare("INSERT INTO shipping_fees (name, shipping_method_id, base_fee, fee_per_kg, min_order_free_ship, priority, is_active) VALUES (?, ?, ?, ?, ?, 10, 1)");
                $insertFee->execute([$_POST['name'] . ' - Mặc định', $newMethodId, $baseFee, $feePerKg, $freeShip]);
                
                $message = 'Thêm phương thức vận chuyển thành công!';
                $messageType = 'success';
                break;
                
            case 'update_shipping_method':
                $stmt = $db->prepare("
                    UPDATE shipping_methods 
                    SET name = ?, description = ?, delivery_time = ?, price_multiplier = ?, is_active = ?, supports_cod = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['delivery_time'],
                    $_POST['price_multiplier'],
                    isset($_POST['is_active']) ? 1 : 0,
                    isset($_POST['supports_cod']) ? 1 : 0,
                    $_POST['method_id']
                ]);
                
                $baseFee = floatval($_POST['base_fee'] ?? 0);
                $feePerKg = floatval($_POST['fee_per_kg'] ?? 0);
                $freeShip = !empty($_POST['min_order_free_ship']) ? floatval($_POST['min_order_free_ship']) : null;
                $methodId = $_POST['method_id'];
                
                $existingFee = $db->prepare("SELECT id FROM shipping_fees WHERE shipping_method_id = ? AND is_active = 1 ORDER BY priority DESC LIMIT 1");
                $existingFee->execute([$methodId]);
                $feeRow = $existingFee->fetch(PDO::FETCH_ASSOC);
                
                if ($feeRow) {
                    $updateFee = $db->prepare("UPDATE shipping_fees SET base_fee = ?, fee_per_kg = ?, min_order_free_ship = ? WHERE id = ?");
                    $updateFee->execute([$baseFee, $feePerKg, $freeShip, $feeRow['id']]);
                } else {
                    $methodName = $_POST['name'];
                    $insertFee = $db->prepare("INSERT INTO shipping_fees (name, shipping_method_id, base_fee, fee_per_kg, min_order_free_ship, priority, is_active) VALUES (?, ?, ?, ?, ?, 10, 1)");
                    $insertFee->execute([$methodName . ' - Mặc định', $methodId, $baseFee, $feePerKg, $freeShip]);
                }
                
                $message = 'Cập nhật phương thức vận chuyển thành công!';
                $messageType = 'success';
                break;
                
            case 'delete_shipping_method':
                $stmt = $db->prepare("DELETE FROM shipping_fees WHERE shipping_method_id = ?");
                $stmt->execute([$_POST['method_id']]);
                $stmt = $db->prepare("DELETE FROM shipping_methods WHERE id = ?");
                $stmt->execute([$_POST['method_id']]);
                $message = 'Xóa phương thức vận chuyển thành công!';
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

// Set HTML header for page rendering
header('Content-Type: text/html; charset=UTF-8');

$shippingMethods = $db->query("SELECT * FROM v_shipping_methods_with_fees WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$shippingFees = $db->query("
    SELECT sf.*, sm.name as shipping_method_name, sm.code as method_code,
           p.name as province_name, '' as district_name
    FROM shipping_fees sf
    LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
    LEFT JOIN provinces p ON sf.province_id = p.id
    ORDER BY sf.priority DESC
")->fetchAll(PDO::FETCH_ASSOC);
$allMethods = $db->query("SELECT * FROM v_shipping_methods_with_fees ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
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

        * { box-sizing: border-box; }
        
        .shipping-config-container {
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            overflow-x: auto;
        }
        
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
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e83e8c;
            font-size: 12px;
        }
        
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
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-link {
            color: #667eea;
            text-decoration: none;
        }
        
        .btn-link:hover {
            color: #5568d3;
        }
        
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
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allMethods as $method): ?>
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
                                <button class="btn btn-sm btn-danger" onclick="deleteMethod(<?= $method['id'] ?>, '<?= htmlspecialchars($method['name'] ?? '') ?>')" title="Xóa">
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
                                <button class="btn btn-sm btn-primary" onclick='editFee(<?= json_encode($fee) ?>)' title="Chỉnh sửa">
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
                            <input type="text" name="code" class="form-control" required placeholder="VD: GHN, GHTK, VNPOST">
                            <small class="text-muted">Mã duy nhất, không dấu, viết hoa</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="VD: Giao Hàng Nhanh">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Mô tả ngắn về phương thức"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thời gian giao hàng</label>
                            <input type="text" name="delivery_time" class="form-control" placeholder="VD: 2-3 ngày">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hệ số giá</label>
                                <input type="number" name="price_multiplier" class="form-control" step="0.1" value="1.0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kích hoạt</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked>
                                    <label class="form-check-label" for="add_is_active">Phương thức đang hoạt động</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="supports_cod" id="add_supports_cod" checked>
                                <label class="form-check-label" for="add_supports_cod">Hỗ trợ COD (thanh toán khi nhận hàng)</label>
                            </div>
                        </div>
                        <hr>
                        <h6 class="fw-bold mb-3"><i class="fas fa-dollar-sign me-1"></i>Cấu hình phí vận chuyển</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phí cơ bản (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" name="base_fee" class="form-control" value="0" min="0" required>
                                <small class="text-muted">Phí vận chuyển cơ bản</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phí mỗi kg (VNĐ)</label>
                                <input type="number" name="fee_per_kg" class="form-control" value="0" min="0">
                                <small class="text-muted">Phí thêm mỗi kg vượt quá</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Miễn phí từ đơn hàng (VNĐ)</label>
                                <input type="number" name="min_order_free_ship" class="form-control" min="0" placeholder="VD: 500000">
                                <small class="text-muted">Đơn hàng ≥ số này sẽ miễn phí vận chuyển</small>
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

    <!-- Edit Method Modal -->
    <div class="modal fade" id="editMethodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_shipping_method">
                    <input type="hidden" name="method_id" id="edit_method_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Chỉnh sửa phương thức vận chuyển</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mã</label>
                            <input type="text" id="edit_method_code" class="form-control" readonly style="background: #e9ecef;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_method_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" id="edit_method_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thời gian giao hàng</label>
                            <input type="text" name="delivery_time" id="edit_method_delivery_time" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hệ số giá</label>
                                <input type="number" name="price_multiplier" id="edit_method_price_multiplier" class="form-control" step="0.1" value="1.0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kích hoạt</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                    <label class="form-check-label" for="edit_is_active">Phương thức đang hoạt động</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="supports_cod" id="edit_supports_cod">
                                <label class="form-check-label" for="edit_supports_cod">Hỗ trợ COD (thanh toán khi nhận hàng)</label>
                            </div>
                        </div>
                        <hr>
                        <h6 class="fw-bold mb-3"><i class="fas fa-dollar-sign me-1"></i>Cấu hình phí vận chuyển</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phí cơ bản (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" name="base_fee" id="edit_method_base_fee" class="form-control" value="0" min="0">
                                <small class="text-muted">Phí vận chuyển cơ bản</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phí mỗi kg (VNĐ)</label>
                                <input type="number" name="fee_per_kg" id="edit_method_fee_per_kg" class="form-control" value="0" min="0">
                                <small class="text-muted">Phí thêm mỗi kg vượt quá</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Miễn phí từ đơn hàng (VNĐ)</label>
                                <input type="number" name="min_order_free_ship" id="edit_method_free_ship" class="form-control" value="" min="0" placeholder="VD: 500000">
                                <small class="text-muted">Đơn hàng ≥ số này sẽ miễn phí vận chuyển</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
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
                                    <?php foreach ($allMethods as $method): ?>
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

    <!-- Edit Fee Modal -->
    <div class="modal fade" id="editFeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_shipping_fee">
                    <input type="hidden" name="fee_id" id="edit_fee_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Chỉnh sửa cấu hình phí vận chuyển</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên cấu hình <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_fee_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Độ ưu tiên</label>
                                <input type="number" name="priority" id="edit_fee_priority" class="form-control" value="0">
                                <small class="text-muted">Số cao hơn = ưu tiên hơn</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tỉnh/TP</label>
                                <select name="province_id" id="edit_fee_province_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($provinces as $province): ?>
                                    <option value="<?= $province['id'] ?? 0 ?>"><?= htmlspecialchars($province['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quận/Huyện</label>
                                <select name="district_id" id="edit_fee_district_id" class="form-select">
                                    <option value="">Tất cả</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phương thức</label>
                                <select name="shipping_method_id" id="edit_fee_method_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($allMethods as $method): ?>
                                    <option value="<?= $method['id'] ?? 0 ?>"><?= htmlspecialchars($method['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phí cơ bản (VNĐ)</label>
                                <input type="number" name="base_fee" id="edit_fee_base_fee" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phí mỗi kg (VNĐ)</label>
                                <input type="number" name="fee_per_kg" id="edit_fee_per_kg" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Từ trọng lượng (kg)</label>
                                <input type="number" name="weight_from" id="edit_fee_weight_from" class="form-control" step="0.1" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Đến trọng lượng (kg)</label>
                                <input type="number" name="weight_to" id="edit_fee_weight_to" class="form-control" step="0.1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Miễn phí từ (VNĐ)</label>
                                <input type="number" name="min_order_free_ship" id="edit_fee_free_ship" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Từ giá trị đơn (VNĐ)</label>
                                <input type="number" name="order_value_from" id="edit_fee_order_from" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Đến giá trị đơn (VNĐ)</label>
                                <input type="number" name="order_value_to" id="edit_fee_order_to" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_fee_is_active" checked>
                                <label class="form-check-label" for="edit_fee_is_active">Kích hoạt</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            initDragAndDrop();
        });
        
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

                        const allRows = [...this.parentNode.children];
                        const draggedIndex = allRows.indexOf(draggedElement);
                        const targetIndex = allRows.indexOf(this);
                        
                        if (draggedIndex < targetIndex) {
                            this.parentNode.insertBefore(draggedElement, this.nextSibling);
                        } else {
                            this.parentNode.insertBefore(draggedElement, this);
                        }
                        
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
                const sortOrder = rows.length - index;
                updates.push({ id, sortOrder });
            });
            
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
        
        function showFeeDetails(methodCode) {

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
        
        function previewOnCheckout(methodCode) {
            alert('Tính năng xem trước trên Checkout đang được phát triển');
        }
        
        function calculatePreviewFee(methodCode) {
            alert('Tính năng đang được phát triển');
        }
        
        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }
        
        function previewShipping(methodId) {
            alert('Tính năng xem trước phí vận chuyển đang được phát triển');
        }
        
        function calculatePreview(methodId) {
            alert('Tính năng đang được phát triển');
        }
        
        function editMethod(method) {
            document.getElementById('edit_method_id').value = method.id;
            document.getElementById('edit_method_code').value = method.code || '';
            document.getElementById('edit_method_name').value = method.name || '';
            document.getElementById('edit_method_description').value = method.description || '';
            document.getElementById('edit_method_delivery_time').value = method.delivery_time || '';
            document.getElementById('edit_method_price_multiplier').value = method.price_multiplier || 1.0;
            document.getElementById('edit_is_active').checked = (method.is_active == 1);
            document.getElementById('edit_supports_cod').checked = (method.supports_cod == 1);
            
            document.getElementById('edit_method_base_fee').value = method.min_base_fee || 0;
            document.getElementById('edit_method_fee_per_kg').value = 0;
            document.getElementById('edit_method_free_ship').value = method.min_free_ship_threshold || '';
            
            var modal = new bootstrap.Modal(document.getElementById('editMethodModal'));
            modal.show();
        }
        
        function deleteMethod(methodId, methodName) {
            if (confirm('Bạn có chắc muốn xóa phương thức "' + methodName + '"?\nTất cả cấu hình phí liên quan cũng sẽ bị xóa.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_shipping_method">
                    <input type="hidden" name="method_id" value="${methodId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editFee(fee) {
            document.getElementById('edit_fee_id').value = fee.id;
            document.getElementById('edit_fee_name').value = fee.name || '';
            document.getElementById('edit_fee_priority').value = fee.priority || 0;
            document.getElementById('edit_fee_province_id').value = fee.province_id || '';
            document.getElementById('edit_fee_method_id').value = fee.shipping_method_id || '';
            document.getElementById('edit_fee_base_fee').value = fee.base_fee || 0;
            document.getElementById('edit_fee_per_kg').value = fee.fee_per_kg || 0;
            document.getElementById('edit_fee_weight_from').value = fee.weight_from || 0;
            document.getElementById('edit_fee_weight_to').value = fee.weight_to || '';
            document.getElementById('edit_fee_free_ship').value = fee.min_order_free_ship || '';
            document.getElementById('edit_fee_order_from').value = fee.order_value_from || 0;
            document.getElementById('edit_fee_order_to').value = fee.order_value_to || '';
            document.getElementById('edit_fee_is_active').checked = (fee.is_active == 1);
            
            var modal = new bootstrap.Modal(document.getElementById('editFeeModal'));
            modal.show();
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
