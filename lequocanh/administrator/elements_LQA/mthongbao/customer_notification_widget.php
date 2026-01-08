<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/CustomerNotificationManager.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    return;
}

$userId = $_SESSION['USER'];
$notificationManager = new CustomerNotificationManager();
$unreadCount = $notificationManager->getUnreadCount($userId);
$notifications = $notificationManager->getUserNotifications($userId, 5);
?>

<style>
.notification-widget {
    position: relative;
    display: inline-block;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 20px;
    color: #333;
}

.notification-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    z-index: 1000;
    margin-top: 10px;
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h5 {
    margin: 0;
    font-size: 16px;
}

.mark-all-read {
    font-size: 12px;
    color: #007bff;
    cursor: pointer;
    text-decoration: none;
}

.mark-all-read:hover {
    text-decoration: underline;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
}

.notification-item .notification-title {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 14px;
}

.notification-item .notification-message {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.notification-item .notification-time {
    font-size: 11px;
    color: #999;
}

.no-notifications {
    padding: 30px;
    text-align: center;
    color: #999;
}

.notification-header-actions {
    display: flex;
    gap: 10px;
    font-size: 12px;
}

.notification-header-actions a {
    color: #007bff;
    text-decoration: none;
}

.notification-header-actions a:hover {
    text-decoration: underline;
}

.delete-read {
    color: #dc3545 !important;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex-wrap: wrap;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    min-width: 200px;
    cursor: pointer;
}

.btn-view-order {
    width: 100%;
    margin-top: 10px;
    padding: 8px 12px;
    background: #007bff;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
    text-align: center;
    display: block;
}

.btn-view-order:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
}

.bg-success { background-color: #28a745; }
.bg-danger { background-color: #dc3545; }
.bg-warning { background-color: #ffc107; color: #333; }
.bg-info { background-color: #17a2b8; }
.bg-primary { background-color: #007bff; }
.bg-secondary { background-color: #6c757d; }
</style>

<div class="notification-widget">
    <div class="notification-bell" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-count"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
        <?php endif; ?>
    </div>
    
    <div id="notificationDropdown" class="notification-dropdown">
        <div class="notification-header">
            <h5>Thông báo</h5>
            <div class="notification-header-actions">
                <?php if ($unreadCount > 0): ?>
                    <a href="#" class="mark-all-read" onclick="markAllAsRead(); return false;">Đánh dấu tất cả đã đọc</a>
                <?php endif; ?>
                <a href="#" class="delete-read" onclick="deleteReadNotifications(); return false;">Xóa thông báo đã đọc</a>
            </div>
        </div>
        
        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash" style="font-size: 40px; margin-bottom: 10px;"></i>
                    <p>Không có thông báo nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): 
                    $iconClass = getNotificationIconClass($notification['type']);
                    $colorClass = getNotificationColorClass($notification['type']);
                ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                         data-id="<?php echo $notification['id']; ?>"
                         data-order-id="<?php echo $notification['order_id'] ?? ''; ?>">
                        <div class="notification-icon <?php echo $colorClass; ?>">
                            <i class="fas fa-<?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <div class="notification-time">
                                <?php 
                                $time = strtotime($notification['created_at']);
                                $timeDiff = time() - $time;
                                
                                if ($timeDiff < 60) {
                                    echo 'Vừa xong';
                                } elseif ($timeDiff < 3600) {
                                    echo floor($timeDiff / 60) . ' phút trước';
                                } elseif ($timeDiff < 86400) {
                                    echo floor($timeDiff / 3600) . ' giờ trước';
                                } else {
                                    echo date('d/m/Y H:i', $time);
                                }
                                ?>
                            </div>
                        </div>
                        <?php if ($notification['order_id']): ?>
                            <?php if ($notification['type'] == 'order_approved'): ?>
                                <a href="/lequocanh/customer/order_invoice.php?order_id=<?php echo $notification['order_id']; ?>" 
                                   class="btn-view-order"
                                   onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                    <i class="fas fa-file-invoice"></i> Xem hóa đơn & Đánh giá
                                </a>
                            <?php else: ?>
                                <a href="/lequocanh/customer/order_invoice.php?order_id=<?php echo $notification['order_id']; ?>" 
                                   class="btn-view-order"
                                   onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                    <i class="fas fa-eye"></i> Xem chi tiết đơn hàng
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php

function getNotificationIconClass($type) {
    switch ($type) {
        case 'order_approved': return 'check-circle';
        case 'order_cancelled': return 'times-circle';
        case 'order_shipped': return 'truck';
        case 'order_delivered': return 'box-open';
        case 'payment_confirmed': return 'money-bill-wave';
        case 'order_created': return 'shopping-cart';
        case 'payment_pending': return 'clock';
        case 'payment_rejected': return 'exclamation-triangle';
        default: return 'bell';
    }
}

function getNotificationColorClass($type) {
    switch ($type) {
        case 'order_approved':
        case 'payment_confirmed': return 'bg-success';
        case 'order_cancelled':
        case 'payment_rejected': return 'bg-danger';
        case 'order_shipped': return 'bg-info';
        case 'order_delivered':
        case 'order_created': return 'bg-primary';
        case 'payment_pending': return 'bg-warning';
        default: return 'bg-secondary';
    }
}
?>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(event) {
    const widget = document.querySelector('.notification-widget');
    if (!widget.contains(event.target)) {
        document.getElementById('notificationDropdown').classList.remove('show');
    }
});

function markAsRead(notificationId) {

    fetch('/lequocanh/administrator/elements_LQA/mthongbao/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        console.log('Notification marked as read:', data);
    })
    .catch(error => console.error('Error:', error));
    
    return true;
}

function markAllAsRead() {
    fetch('/lequocanh/administrator/elements_LQA/mthongbao/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteReadNotifications() {
    if (!confirm('Bạn có chắc chắn muốn xóa tất cả thông báo đã đọc?')) {
        return;
    }
    
    fetch('/lequocanh/administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=delete_read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

setInterval(function() {
    refreshNotificationCount();
}, 30000);

function refreshNotificationCount() {
    fetch('/lequocanh/administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const countEl = document.querySelector('.notification-count');
            if (data.unread_count > 0) {
                if (countEl) {
                    countEl.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    countEl.style.display = 'flex';
                } else {

                    const bell = document.querySelector('.notification-bell');
                    if (bell) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-count';
                        newBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        bell.appendChild(newBadge);
                    }
                }
            } else if (countEl) {
                countEl.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error refreshing notification count:', error));
}
</script>
