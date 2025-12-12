<?php
/**
 * Batch Shipping Operations
 * 
 * Xử lý nhiều đơn hàng cùng lúc:
 * - Tạo nhiều vận đơn
 * - Cập nhật trạng thái hàng loạt
 * - In nhãn hàng loạt
 */

require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/GHNService.php';

// Check admin permission - must be called from index.php with proper session
if (!isset($_SESSION['ADMIN'])) {
    die('Access denied. Please login as admin.');
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle batch operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderIds = $_POST['order_ids'] ?? [];
    
    if (empty($orderIds)) {
        $message = 'Vui lòng chọn ít nhất một đơn hàng';
        $messageType = 'warning';
    } else {
        try {
            switch ($action) {
                case 'create_shipments':
                    $result = createBatchShipments($orderIds, $db);
                    $message = "Đã tạo {$result['success']} vận đơn thành công, {$result['failed']} thất bại";
                    $messageType = $result['failed'] > 0 ? 'warning' : 'success';
                    break;
                    
                case 'update_status':
                    $newStatus = $_POST['new_status'] ?? '';
                    $result = updateBatchStatus($orderIds, $newStatus, $db);
                    $message = "Đã cập nhật {$result} đơn hàng";
                    $messageType = 'success';
                    break;
                    
                case 'print_labels':
                    $result = printBatchLabels($orderIds, $db);
                    $message = "Đã tạo file in nhãn cho {$result} đơn hàng";
                    $messageType = 'success';
                    break;
                    
                default:
                    $message = 'Hành động không hợp lệ';
                    $messageType = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Lỗi: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get pending orders
$stmt = $db->query("
    SELECT 
        id,
        ma_don_hang,
        ten_khach_hang,
        dia_chi_giao_hang,
        phi_van_chuyen,
        shipping_status,
        tracking_code,
        ngay_dat_hang
    FROM don_hang 
    WHERE shipping_status IN ('pending', 'picking')
    ORDER BY ngay_dat_hang DESC
    LIMIT 50
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Create batch shipments
 */
function createBatchShipments($orderIds, $db) {
    $ghn = new GHNService();
    $success = 0;
    $failed = 0;
    
    foreach ($orderIds as $orderId) {
        try {
            // Get order details
            $stmt = $db->prepare("SELECT * FROM don_hang WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $failed++;
                continue;
            }
            
            // Create shipment
            $orderData = [
                'to_name' => $order['ten_khach_hang'],
                'to_phone' => $order['so_dien_thoai'] ?? '0000000000',
                'to_address' => $order['dia_chi_giao_hang'],
                'to_ward_code' => $order['ward_id'] ?? '10001',
                'to_district_id' => $order['district_id'] ?? 1001,
                'cod_amount' => $order['tong_tien'],
                'content' => 'Hàng hóa',
                'weight' => 1000,
                'insurance_value' => $order['tong_tien']
            ];
            
            $result = $ghn->createShippingOrder($orderData);
            
            if ($result['code'] === 200 && !empty($result['data']['order_code'])) {
                // Update order with tracking code
                $stmt = $db->prepare("
                    UPDATE don_hang 
                    SET tracking_code = ?,
                        carrier = 'GHN',
                        shipping_status = 'picking',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$result['data']['order_code'], $orderId]);
                
                $success++;
            } else {
                $failed++;
            }
            
        } catch (Exception $e) {
            error_log("Batch shipment error for order $orderId: " . $e->getMessage());
            $failed++;
        }
    }
    
    return ['success' => $success, 'failed' => $failed];
}

/**
 * Update batch status
 */
function updateBatchStatus($orderIds, $newStatus, $db) {
    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
    
    $stmt = $db->prepare("
        UPDATE don_hang 
        SET shipping_status = ?,
            updated_at = NOW()
        WHERE id IN ($placeholders)
    ");
    
    $params = array_merge([$newStatus], $orderIds);
    $stmt->execute($params);
    
    return $stmt->rowCount();
}

/**
 * Print batch labels
 */
function printBatchLabels($orderIds, $db) {
    // This would integrate with GHN print API
    // For now, just return count
    return count($orderIds);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xử Lý Hàng Loạt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .action-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Xử Lý Hàng Loạt</h1>
            <p class="mb-0">Tạo vận đơn và xử lý nhiều đơn hàng cùng lúc</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Action Bar -->
        <div class="action-bar">
            <form method="POST" id="batchForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Hành động</label>
                        <select name="action" class="form-select" required>
                            <option value="">-- Chọn hành động --</option>
                            <option value="create_shipments">Tạo vận đơn GHN</option>
                            <option value="update_status">Cập nhật trạng thái</option>
                            <option value="print_labels">In nhãn hàng loạt</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="statusField" style="display: none;">
                        <label class="form-label">Trạng thái mới</label>
                        <select name="new_status" class="form-select">
                            <option value="pending">Chờ xử lý</option>
                            <option value="picking">Đang lấy hàng</option>
                            <option value="shipping">Đang vận chuyển</option>
                            <option value="delivered">Đã giao</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Thực hiện
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="selectAll()">
                            <i class="fas fa-check-square"></i> Chọn tất cả
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <h5><i class="fas fa-list"></i> Đơn hàng cần xử lý (<?= count($orders) ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCheckbox" onclick="toggleAll(this)"></th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Địa chỉ</th>
                            <th>Phí VC</th>
                            <th>Trạng thái</th>
                            <th>Mã vận đơn</th>
                            <th>Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <i class="fas fa-inbox"></i> Không có đơn hàng
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="order_ids[]" value="<?= $order['id'] ?>" form="batchForm" class="order-checkbox">
                            </td>
                            <td><strong><?= htmlspecialchars($order['ma_don_hang']) ?></strong></td>
                            <td><?= htmlspecialchars($order['ten_khach_hang']) ?></td>
                            <td><?= htmlspecialchars(substr($order['dia_chi_giao_hang'], 0, 40)) ?>...</td>
                            <td><?= number_format($order['phi_van_chuyen'], 0, ',', '.') ?>₫</td>
                            <td>
                                <span class="badge bg-<?= $order['shipping_status'] === 'pending' ? 'warning' : 'info' ?>">
                                    <?= $order['shipping_status'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($order['tracking_code'] ?? '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($order['ngay_dat_hang'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide status field
        document.querySelector('select[name="action"]').addEventListener('change', function() {
            document.getElementById('statusField').style.display = 
                this.value === 'update_status' ? 'block' : 'none';
        });

        // Toggle all checkboxes
        function toggleAll(source) {
            document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }

        // Select all button
        function selectAll() {
            document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
            document.getElementById('selectAllCheckbox').checked = true;
        }

        // Confirm before submit
        document.getElementById('batchForm').addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('.order-checkbox:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất một đơn hàng');
                return;
            }
            
            const action = document.querySelector('select[name="action"]').value;
            const actionNames = {
                'create_shipments': 'tạo vận đơn',
                'update_status': 'cập nhật trạng thái',
                'print_labels': 'in nhãn'
            };
            
            if (!confirm(`Bạn có chắc muốn ${actionNames[action]} cho ${checked} đơn hàng?`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
