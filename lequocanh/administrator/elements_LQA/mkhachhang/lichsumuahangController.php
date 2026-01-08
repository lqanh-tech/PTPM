<?php

if (!isset($_SESSION['USER'])) {
    echo '<div class="alert alert-warning">Vui lòng đăng nhập để xem lịch sử mua hàng.</div>';
    return;
}

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/CustomerNotificationManager.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $username = $_SESSION['USER'];
    
    $userSql = "SELECT * FROM user WHERE username = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$username]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['order_id'])) {
        $orderId = (int)$_GET['order_id'];
        
        $checkOrderSql = "SELECT id, trang_thai FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
        $checkStmt = $conn->prepare($checkOrderSql);
        $checkStmt->execute([$orderId, $username]);
        $orderToCancel = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($orderToCancel && $orderToCancel['trang_thai'] === 'pending') {

            $cancelSql = "UPDATE don_hang SET trang_thai = 'cancelled', ngay_cap_nhat = NOW() WHERE id = ?";
            $cancelStmt = $conn->prepare($cancelSql);
            $cancelResult = $cancelStmt->execute([$orderId]);
            
            if ($cancelResult) {

                $notificationManager = new CustomerNotificationManager();
                $notificationManager->notifyOrderCancelled($orderId, $username, 'Khách hàng tự hủy đơn hàng');
                
                $_SESSION['message'] = 'Đơn hàng đã được hủy thành công.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Có lỗi xảy ra khi hủy đơn hàng.';
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'Không thể hủy đơn hàng này.';
            $_SESSION['message_type'] = 'warning';
        }
        
        echo '<script>window.location.href = "index.php?req=lichsumuahang";</script>';
        exit();
    }
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $countSql = "SELECT COUNT(*) as total FROM don_hang WHERE ma_nguoi_dung = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute([$username]);
    $totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalOrders / $limit);
    
    $ordersSql = "SELECT * FROM don_hang 
                  WHERE ma_nguoi_dung = ? 
                  ORDER BY ngay_tao DESC 
                  LIMIT ? OFFSET ?";
    $ordersStmt = $conn->prepare($ordersSql);
    $ordersStmt->execute([$username, $limit, $offset]);
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orders as &$order) {
        $itemsSql = "SELECT cth.*, h.tenhanghoa 
                     FROM chi_tiet_don_hang cth 
                     LEFT JOIN hanghoa h ON cth.ma_san_pham = h.idhanghoa 
                     WHERE cth.ma_don_hang = ?";
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $notificationManager = new CustomerNotificationManager();
    $unreadCount = $notificationManager->getUnreadCount($username);
    
} catch (Exception $e) {
    error_log("Error in lichsumuahang: " . $e->getMessage());
    echo '<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu.</div>';
    return;
}
?>

<!-- Hiển thị thông báo -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Lịch Sử Mua Hàng
                        </h5>
                        <div>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-warning me-2">
                                    <i class="fas fa-bell"></i> <?php echo $unreadCount; ?> thông báo mới
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-light text-dark">
                                Tổng: <?php echo $totalOrders; ?> đơn hàng
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Chưa có đơn hàng nào</h4>
                            <p class="text-muted">Bạn chưa có đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($orders as $order): ?>
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-receipt"></i> 
                                                <?php echo htmlspecialchars($order['ma_don_hang_text']); ?>
                                            </h6>
                                            <?php
                                            $statusConfig = [
                                                'pending' => ['class' => 'warning', 'text' => 'Chờ xác nhận', 'icon' => 'clock'],
                                                'approved' => ['class' => 'success', 'text' => 'Đã duyệt', 'icon' => 'check'],
                                                'cancelled' => ['class' => 'danger', 'text' => 'Đã hủy', 'icon' => 'times']
                                            ];
                                            $status = $statusConfig[$order['trang_thai']] ?? ['class' => 'secondary', 'text' => $order['trang_thai'], 'icon' => 'question'];
                                            ?>
                                            <span class="badge bg-<?php echo $status['class']; ?>">
                                                <i class="fas fa-<?php echo $status['icon']; ?>"></i>
                                                <?php echo $status['text']; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <small class="text-muted">Ngày đặt:</small><br>
                                                    <strong><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Tổng tiền:</small><br>
                                                    <strong class="text-success fs-5">
                                                        <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫
                                                    </strong>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($order['phuong_thuc_thanh_toan'])): ?>
                                                <div class="mb-3">
                                                    <small class="text-muted">Phương thức thanh toán:</small><br>
                                                    <?php
                                                    $paymentMethods = [
                                                        'momo' => '<i class="fas fa-mobile-alt text-primary"></i> MoMo',
                                                        'bank_transfer' => '<i class="fas fa-university text-info"></i> Chuyển khoản',
                                                        'cod' => '<i class="fas fa-money-bill-wave text-success"></i> COD'
                                                    ];
                                                    echo $paymentMethods[$order['phuong_thuc_thanh_toan']] ?? htmlspecialchars($order['phuong_thuc_thanh_toan']);
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($order['items'])): ?>
                                                <div class="mb-3">
                                                    <small class="text-muted">Sản phẩm:</small>
                                                    <div class="mt-1">
                                                        <?php foreach (array_slice($order['items'], 0, 2) as $item): ?>
                                                            <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                                                                <small><?php echo htmlspecialchars($item['tenhanghoa'] ?? 'Sản phẩm #' . $item['ma_san_pham']); ?></small>
                                                                <small class="text-muted">x<?php echo $item['so_luong']; ?></small>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if (count($order['items']) > 2): ?>
                                                            <small class="text-muted">... và <?php echo count($order['items']) - 2; ?> sản phẩm khác</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex gap-2">
                                                <a href="index.php?req=don_hang&action=view&id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary flex-fill">
                                                    <i class="fas fa-eye"></i> Chi tiết
                                                </a>
                                                
                                                <?php if ($order['trang_thai'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-times"></i> Hủy
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Phân trang -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Phân trang đơn hàng">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?req=lichsumuahang&page=<?php echo $page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i> Trước
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?req=lichsumuahang&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?req=lichsumuahang&page=<?php echo $page + 1; ?>">
                                                Sau <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?\n\nLưu ý: Đơn hàng đã hủy không thể khôi phục.')) {
        window.location.href = 'index.php?req=lichsumuahang&action=cancel&order_id=' + orderId;
    }
}
</script>
