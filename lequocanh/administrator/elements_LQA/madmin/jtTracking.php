<?php
/**
 * Admin - Quản lý vận chuyển J&T Express
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../userLogin.php');
    exit();
}

require_once __DIR__ . '/../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

use App\Services\JTExpressService;

$jtService = JTExpressService::fromConfig();

// Lấy danh sách đơn hàng có tracking
$sql = "SELECT dh.*, u.hoten as ten_khach_hang, u.dienthoai
        FROM don_hang dh
        LEFT JOIN users u ON dh.ma_nguoi_dung = u.username
        WHERE dh.tracking_number IS NOT NULL AND dh.tracking_number != ''
        ORDER BY dh.ngay_tao DESC
        LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tracking events
$trackingEvents = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    $sql = "SELECT id, order_id, tracking_number, status_code, status_desc, location, event_time FROM tracking_events WHERE order_id IN ({$placeholders}) ORDER BY event_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($orderIds);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($events as $event) {
        $trackingEvents[$event['order_id']][] = $event;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý vận chuyển J&T</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f5; }
        .container { max-width: 1400px; margin: 20px auto; }
        .table-container { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; margin-bottom: 15px; }
        .timeline-item::before { content: ''; position: absolute; left: -23px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #dee2e6; }
        .timeline-item.completed::before { background: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-truck me-2"></i>Quản lý vận chuyển J&T</h2>
            <div>
                <span class="badge bg-<?= $jtService->isConfigured() ? 'success' : 'danger' ?>">
                    <?= $jtService->isConfigured() ? 'Đã kết nối' : 'Chưa cấu hình' ?>
                </span>
                <a href="../index.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>
        
        <?php if (!$jtService->isConfigured()): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            J&T Express chưa được cấu hình. Vui lòng thêm API credentials vào file <code>.env</code>
        </div>
        <?php endif; ?>
        
        <!-- Orders with tracking -->
        <div class="table-container">
            <h5 class="mb-3"><i class="fas fa-box me-2"></i>Đơn hàng có mã vận đơn</h5>
            
            <?php if (empty($orders)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Chưa có đơn hàng nào có mã vận đơn</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Mã vận đơn</th>
                                <th>Trạng thái</th>
                                <th>Tổng tiền</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['ma_don_hang_text']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($order['ten_khach_hang'] ?? $order['ma_nguoi_dung']) ?>
                                    <br><small class="text-muted"><?= $order['dienthoai'] ?? '' ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($order['tracking_number']) ?></code></td>
                                <td>
                                    <?php
                                    $statusConfig = [
                                        'pending' => ['bg-warning text-dark', 'Chờ xử lý'],
                                        'approved' => ['bg-info', 'Đã duyệt'],
                                        'delivered' => ['bg-primary', 'Đang giao'],
                                        'completed' => ['bg-success', 'Hoàn tất'],
                                        'cancelled' => ['bg-danger', 'Đã hủy'],
                                    ];
                                    $status = $statusConfig[$order['trang_thai']] ?? ['bg-secondary', $order['trang_thai']];
                                    ?>
                                    <span class="badge <?= $status[0] ?>"><?= $status[1] ?></span>
                                </td>
                                <td class="text-danger fw-bold"><?= number_format($order['tong_tien'], 0, ',', '.') ?>₫</td>
                                <td><?= date('d/m/Y H:i', strtotime($order['ngay_tao'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#trackingModal<?= $order['id'] ?>">
                                        <i class="fas fa-map-marker-alt"></i> Tracking
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Tracking Modal -->
                            <div class="modal fade" id="trackingModal<?= $order['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Tracking: <?= htmlspecialchars($order['tracking_number']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if (!empty($trackingEvents[$order['id']])): ?>
                                                <div class="timeline">
                                                    <?php foreach ($trackingEvents[$order['id']] as $event): ?>
                                                    <div class="timeline-item completed">
                                                        <div>
                                                            <strong><?= $jtService->getStatusIcon($event['status_code']) ?> <?= htmlspecialchars($event['status_desc']) ?></strong>
                                                            <br><small class="text-muted"><?= $event['event_time'] ?></small>
                                                            <?php if (!empty($event['location'])): ?>
                                                                <br><small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center py-3 text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Chưa có sự kiện tracking
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>