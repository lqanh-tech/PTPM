<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Location: ../../userLogin.php');
    exit();
}

require_once './elements_LQA/mthongbao/thongbaoCls.php';

$userId = $_SESSION['USER'];
$thongbao = new ThongBao();
$notifications = $thongbao->getUserNotifications($userId);
?>

<div class="admin-title">Thông báo đơn hàng</div>
<hr>

<div class="notification-container">
    <div class="notification-header">
        <h3>Danh sách thông báo</h3>
        <div class="notification-header-actions">
            <button id="mark-all-read" class="btn btn-outline-primary me-2">
                <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
            </button>
            <button id="delete-read-notifications" class="btn btn-outline-danger">
                <i class="fas fa-trash"></i> Xóa thông báo đã đọc
            </button>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Bạn chưa có thông báo nào.
        </div>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notification):
                $status = '';
                $icon = '';
                $bgColor = '';

                switch ($notification['status']) {
                    case 'pending':
                        $status = 'Đang chờ xử lý';
                        $icon = 'clock';
                        $bgColor = 'bg-warning';
                        break;
                    case 'approved':
                        $status = 'Đã duyệt';
                        $icon = 'check-circle';
                        $bgColor = 'bg-success';
                        break;
                    case 'cancelled':
                        $status = 'Đã hủy';
                        $icon = 'times-circle';
                        $bgColor = 'bg-danger';
                        break;
                    default:
                        $status = 'Không xác định';
                        $icon = 'question-circle';
                        $bgColor = 'bg-secondary';
                }

                $isRead = (bool)$notification['is_read'];
                $notificationClass = $isRead ? 'notification-item' : 'notification-item unread';
            ?>
                <div class="<?php echo $notificationClass; ?>" data-id="<?php echo $notification['id']; ?>" data-status="<?php echo $notification['status']; ?>">
                    <div class="notification-icon <?php echo $bgColor; ?>">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">
                            Đơn hàng #<?php echo $notification['id']; ?> - <?php echo $status; ?>
                            <?php if (!$isRead): ?>
                                <span class="badge bg-primary">Mới</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-info">
                            <p>Mã đơn hàng: <?php echo $notification['order_code']; ?></p>
                            <p>Tổng tiền: <?php echo number_format($notification['total_amount'], 0, ',', '.'); ?> đ</p>
                            <p>Thời gian: <?php echo date('d/m/Y H:i', strtotime($notification['updated_at'])); ?></p>
                        </div>
                        <div class="notification-actions">
                            <button class="btn btn-sm btn-primary view-order-detail-btn" data-id="<?php echo $notification['id']; ?>">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                            <?php if (!$isRead): ?>
                                <button class="btn btn-sm btn-outline-secondary mark-read-btn">
                                    <i class="fas fa-check"></i> Đánh dấu đã đọc
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger delete-notification-btn" data-id="<?php echo $notification['id']; ?>">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal chi tiết đơn hàng -->
<div class="order-detail-modal">
    <div class="order-detail-content">
        <span class="order-detail-close">&times;</span>
        <div class="order-detail-header">
            <h3>Chi tiết đơn hàng #<span id="order-id"></span></h3>
            <span class="order-status" id="order-status"></span>
        </div>
        <div class="order-detail-info">
            <div class="order-detail-info-col">
                <div class="order-detail-info-item">
                    <strong>Mã đơn hàng</strong>
                    <div id="order-code"></div>
                </div>
                <div class="order-detail-info-item">
                    <strong>Ngày đặt</strong>
                    <div id="order-date"></div>
                </div>
                <div class="order-detail-info-item">
                    <strong>Phương thức thanh toán</strong>
                    <div id="order-payment-method"></div>
                </div>
            </div>
            <div class="order-detail-info-col">
                <div class="order-detail-info-item">
                    <strong>Địa chỉ giao hàng</strong>
                    <div id="order-address" class="order-detail-address"></div>
                </div>
            </div>
        </div>
        <div class="order-detail-items">
            <h4>Sản phẩm</h4>
            <table>
                <thead>
                    <tr>
                        <th width="60">Hình ảnh</th>
                        <th>Sản phẩm</th>
                        <th width="100">Đơn giá</th>
                        <th width="80">Số lượng</th>
                        <th width="120">Thành tiền</th>
                    </tr>
                </thead>
                <tbody id="order-items">
                    <!-- Danh sách sản phẩm sẽ được thêm bằng JavaScript -->
                </tbody>
            </table>
            
            <!-- Chi tiết thanh toán -->
            <div class="order-payment-details">
                <div class="payment-row">
                    <span>Tạm tính:</span>
                    <span id="order-subtotal"></span>
                </div>
                <div class="payment-row">
                    <span>Thuế VAT (10%):</span>
                    <span id="order-tax"></span>
                </div>
                <div class="payment-row">
                    <span>Phí vận chuyển:</span>
                    <span id="order-shipping"></span>
                </div>
                <div class="payment-row payment-status-row">
                    <span>Trạng thái thanh toán:</span>
                    <span id="order-payment-status" class="payment-status-badge"></span>
                </div>
                <div class="order-detail-total">
                    Tổng cộng: <span id="order-total"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-container {
    max-width: 800px;
    margin: 0 auto;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.notification-header-actions {
    display: flex;
    gap: 10px;
}

.notification-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notification-item {
    display: flex;
    background-color: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.notification-item:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.notification-item.unread {
    background-color: #fff;
    border-left: 4px solid #007bff;
}

.notification-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    color: white;
    font-size: 24px;
}

.notification-content {
    flex: 1;
    padding: 15px;
}

.notification-title {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-info {
    margin-bottom: 15px;
    color: #6c757d;
}

.notification-info p {
    margin: 5px 0;
}

.notification-actions {
    display: flex;
    gap: 10px;
}

/* Modal chi tiết đơn hàng */
.order-detail-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    overflow-y: auto;
}

.order-detail-modal.show {
    display: block;
}

.order-detail-content {
    position: relative;
    background-color: white;
    margin: 50px auto;
    padding: 20px;
    border-radius: 8px;
    max-width: 800px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.order-detail-close {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.order-detail-close:hover {
    color: #343a40;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-detail-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.order-status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    color: white;
}

.order-status.warning {
    background-color: #ffc107;
}

.order-status.success {
    background-color: #28a745;
}

.order-status.danger {
    background-color: #dc3545;
}

.order-detail-info {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.order-detail-info-col {
    flex: 1;
    min-width: 250px;
    margin-bottom: 15px;
}

.order-detail-info-item {
    margin-bottom: 10px;
}

.order-detail-info-item strong {
    display: block;
    margin-bottom: 5px;
    color: #495057;
}

.order-detail-address {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    margin-top: 5px;
}

.order-detail-items {
    margin-bottom: 20px;
}

.order-detail-items table {
    width: 100%;
    border-collapse: collapse;
}

.order-detail-items th,
.order-detail-items td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.order-detail-items th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.order-detail-items .product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}

.order-detail-items .product-name {
    font-weight: 500;
}

.order-detail-total {
    text-align: right;
    font-size: 18px;
    font-weight: 600;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #333;
    color: #dc3545;
}

.order-payment-details {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.payment-row:last-of-type {
    border-bottom: none;
}

.payment-status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.payment-status-badge.paid {
    background-color: #d4edda;
    color: #155724;
}

.payment-status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.payment-status-badge.failed {
    background-color: #f8d7da;
    color: #721c24;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đánh dấu một thông báo đã đọc
    const markReadButtons = document.querySelectorAll('.mark-read-btn');
    markReadButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notificationItem = this.closest('.notification-item');
            const orderId = notificationItem.dataset.id;
            const status = notificationItem.dataset.status;

            // Gửi yêu cầu đánh dấu đã đọc
            fetch('./elements_LQA/mthongbao/getNotifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_read=1&order_id=${orderId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật giao diện
                    notificationItem.classList.remove('unread');
                    this.remove();

                    // Cập nhật số lượng thông báo chưa đọc
                    updateNotificationCount();
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
            });
        });
    });

    // Đánh dấu tất cả thông báo đã đọc
    const markAllReadButton = document.getElementById('mark-all-read');
    if (markAllReadButton) {
        markAllReadButton.addEventListener('click', function() {
            // Gửi yêu cầu đánh dấu tất cả đã đọc
            fetch('./elements_LQA/mthongbao/getNotifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_all_read=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật giao diện
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });

                    document.querySelectorAll('.mark-read-btn').forEach(btn => {
                        btn.remove();
                    });

                    // Cập nhật số lượng thông báo chưa đọc
                    updateNotificationCount();
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
            });
        });
    }

    // Xóa tất cả thông báo đã đọc
    const deleteReadNotificationsButton = document.getElementById('delete-read-notifications');
    if (deleteReadNotificationsButton) {
        deleteReadNotificationsButton.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa tất cả thông báo đã đọc?')) {
                // Gửi yêu cầu xóa tất cả thông báo đã đọc
                fetch('./elements_LQA/mthongbao/getNotifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'delete_read_notifications=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Tải lại trang để cập nhật danh sách thông báo
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                });
            }
        });
    }

    // Xóa một thông báo cụ thể
    const deleteNotificationButtons = document.querySelectorAll('.delete-notification-btn');
    deleteNotificationButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notificationItem = this.closest('.notification-item');
            const orderId = this.dataset.id;

            if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
                // Gửi yêu cầu xóa thông báo
                fetch('./elements_LQA/mthongbao/getNotifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `delete_notification=1&order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Xóa thông báo khỏi giao diện
                        notificationItem.remove();

                        // Kiểm tra xem còn thông báo nào không
                        const notificationList = document.querySelector('.notification-list');
                        if (notificationList && notificationList.children.length === 0) {
                            const notificationContainer = document.querySelector('.notification-container');
                            notificationContainer.innerHTML = `
                                <div class="notification-header">
                                    <h3>Danh sách thông báo</h3>
                                    <div class="notification-header-actions">
                                        <button id="mark-all-read" class="btn btn-outline-primary me-2">
                                            <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
                                        </button>
                                        <button id="delete-read-notifications" class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i> Xóa thông báo đã đọc
                                        </button>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Bạn chưa có thông báo nào.
                                </div>
                            `;
                        }

                        // Cập nhật số lượng thông báo chưa đọc
                        updateNotificationCount();
                    }
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                });
            }
        });
    });

    // Xử lý nút xem chi tiết đơn hàng
    const viewOrderDetailButtons = document.querySelectorAll('.view-order-detail-btn');
    viewOrderDetailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.id;
            showOrderDetail(orderId);
        });
    });

    // Xử lý đóng modal chi tiết đơn hàng
    const orderDetailModal = document.querySelector('.order-detail-modal');
    const orderDetailClose = document.querySelector('.order-detail-close');

    if (orderDetailModal && orderDetailClose) {
        // Đóng modal khi nhấn nút đóng
        orderDetailClose.addEventListener('click', function() {
            orderDetailModal.classList.remove('show');
        });

        // Đóng modal khi nhấn ra ngoài
        orderDetailModal.addEventListener('click', function(e) {
            if (e.target === orderDetailModal) {
                orderDetailModal.classList.remove('show');
            }
        });

        // Đóng modal khi nhấn phím Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && orderDetailModal.classList.contains('show')) {
                orderDetailModal.classList.remove('show');
            }
        });
    }

    // Hàm hiển thị chi tiết đơn hàng
    function showOrderDetail(orderId) {
        // Hiển thị loading
        const orderDetailModal = document.querySelector('.order-detail-modal');
        const orderItems = document.getElementById('order-items');

        orderItems.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải thông tin đơn hàng...
                </td>
            </tr>
        `;

        // Hiển thị modal
        orderDetailModal.classList.add('show');

        // Lấy thông tin chi tiết đơn hàng
        fetch(`./elements_LQA/mthongbao/getOrderDetail.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật thông tin đơn hàng
                    const order = data.order;

                    document.getElementById('order-id').textContent = order.id;
                    document.getElementById('order-code').textContent = order.order_code;
                    document.getElementById('order-date').textContent = order.created_at;
                    document.getElementById('order-payment-method').textContent = order.payment_method;
                    document.getElementById('order-address').textContent = order.shipping_address || 'Không có thông tin';
                    document.getElementById('order-status').textContent = order.status_text;
                    document.getElementById('order-status').className = `order-status ${order.status_class}`;
                    
                    // Hiển thị chi tiết thanh toán
                    document.getElementById('order-subtotal').textContent = new Intl.NumberFormat('vi-VN').format(order.subtotal || 0) + ' đ';
                    document.getElementById('order-tax').textContent = new Intl.NumberFormat('vi-VN').format(order.tax_amount || 0) + ' đ';
                    document.getElementById('order-shipping').textContent = new Intl.NumberFormat('vi-VN').format(order.shipping_fee || 0) + ' đ';
                    document.getElementById('order-total').textContent = new Intl.NumberFormat('vi-VN').format(order.total_amount) + ' đ';
                    
                    // Hiển thị trạng thái thanh toán
                    const paymentStatusEl = document.getElementById('order-payment-status');
                    paymentStatusEl.textContent = order.payment_status_text || 'Chờ thanh toán';
                    paymentStatusEl.className = `payment-status-badge ${order.payment_status || 'pending'}`;

                    // Cập nhật danh sách sản phẩm
                    let itemsHtml = '';

                    if (order.items.length === 0) {
                        itemsHtml = `
                            <tr>
                                <td colspan="5" class="text-center">Không có sản phẩm nào</td>
                            </tr>
                        `;
                    } else {
                        order.items.forEach(item => {
                            const imagePath = item.product_image ? `../images_LQA/${item.product_image}` : '../images_LQA/no-image.png';

                            itemsHtml += `
                                <tr>
                                    <td>
                                        <img src="${imagePath}" alt="${item.product_name}" class="product-image">
                                    </td>
                                    <td class="product-name">${item.product_name}</td>
                                    <td>${new Intl.NumberFormat('vi-VN').format(item.price)} đ</td>
                                    <td>${item.quantity}</td>
                                    <td>${new Intl.NumberFormat('vi-VN').format(item.total)} đ</td>
                                </tr>
                            `;
                        });
                    }

                    orderItems.innerHTML = itemsHtml;

                    // Cập nhật số lượng thông báo chưa đọc
                    updateNotificationCount();
                } else {
                    // Hiển thị thông báo lỗi
                    orderItems.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-circle"></i> ${data.message || 'Có lỗi xảy ra khi lấy thông tin đơn hàng'}
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                orderItems.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra khi lấy thông tin đơn hàng
                        </td>
                    </tr>
                `;
            });
    }

    // Hàm cập nhật số lượng thông báo chưa đọc
    function updateNotificationCount() {
        fetch('./elements_LQA/mthongbao/getNotifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật badge thông báo
                    const notificationBadge = document.querySelector('.notification-badge');
                    if (notificationBadge) {
                        if (data.unread_count > 0) {
                            notificationBadge.textContent = data.unread_count;
                            notificationBadge.style.display = 'block';
                        } else {
                            notificationBadge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
            });
    }
});
</script>
