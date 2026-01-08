<?php

require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/database.php';
require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';

SessionManager::start();

if (!isset($_SESSION['USER'])) {
    die("Please login first to diagnose notifications.");
}

$username = $_SESSION['USER'];
$db = Database::getInstance()->getConnection();
$notificationManager = new CustomerNotificationManager();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'create_cod_notification':

            $title = "📦 Test COD Order Created";
            $message = "This is a test COD order notification created at " . date('Y-m-d H:i:s');
            $result = $notificationManager->createNotification($username, $title, $message, 'order_created');
            header('Location: diagnose_notifications.php?msg=' . ($result ? 'success' : 'failed'));
            exit;
            
        case 'create_payment_notification':

            $title = "💰 Test Payment Confirmed";
            $message = "This is a test payment confirmation notification created at " . date('Y-m-d H:i:s');
            $result = $notificationManager->createNotification($username, $title, $message, 'payment_confirmed');
            header('Location: diagnose_notifications.php?msg=' . ($result ? 'success' : 'failed'));
            exit;
            
        case 'create_approved_notification':

            $title = "✅ Test Order Approved";
            $message = "This is a test order approved notification created at " . date('Y-m-d H:i:s');
            $result = $notificationManager->createNotification($username, $title, $message, 'order_approved');
            header('Location: diagnose_notifications.php?msg=' . ($result ? 'success' : 'failed'));
            exit;
            
        case 'clear_all':

            $sql = "DELETE FROM customer_notifications WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username]);
            header('Location: diagnose_notifications.php?msg=cleared');
            exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notification System Diagnostics</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h2>Notification System Diagnostics</h2>
    <p>Current User: <strong><?php echo $username; ?></strong></p>
    
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'success'): ?>
            <div class="alert alert-success">Test notification created successfully!</div>
        <?php elseif ($_GET['msg'] == 'failed'): ?>
            <div class="alert alert-danger">Failed to create test notification!</div>
        <?php elseif ($_GET['msg'] == 'cleared'): ?>
            <div class="alert alert-info">All notifications cleared!</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <hr>
    
    <!-- System Status -->
    <h3>1. System Status</h3>
    <?php

    $tableExists = $db->query("SHOW TABLES LIKE 'customer_notifications'")->rowCount() > 0;
    ?>
    <div class="mb-3">
        <p>
            <i class="fas <?php echo $tableExists ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
            Customer notifications table: <?php echo $tableExists ? 'EXISTS' : 'DOES NOT EXIST'; ?>
        </p>
    </div>
    
    <!-- Notification Statistics -->
    <h3>2. Notification Statistics</h3>
    <?php
    $stats = [
        'total' => 0,
        'unread' => 0,
        'read' => 0,
        'by_type' => []
    ];
    
    if ($tableExists) {

        $sql = "SELECT COUNT(*) as count FROM customer_notifications WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stats['unread'] = $notificationManager->getUnreadCount($username);
        $stats['read'] = $stats['total'] - $stats['unread'];
        
        $sql = "SELECT type, COUNT(*) as count FROM customer_notifications WHERE user_id = ? GROUP BY type";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_type'][$row['type']] = $row['count'];
        }
    }
    ?>
    
    <table class="table table-bordered">
        <tr>
            <th>Metric</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>Total Notifications</td>
            <td><?php echo $stats['total']; ?></td>
        </tr>
        <tr>
            <td>Unread</td>
            <td><span class="badge bg-danger"><?php echo $stats['unread']; ?></span></td>
        </tr>
        <tr>
            <td>Read</td>
            <td><span class="badge bg-success"><?php echo $stats['read']; ?></span></td>
        </tr>
        <?php foreach ($stats['by_type'] as $type => $count): ?>
        <tr>
            <td>Type: <?php echo $type; ?></td>
            <td><?php echo $count; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <!-- Recent Orders -->
    <h3>3. Recent Orders</h3>
    <?php
    $sql = "SELECT id, ma_don_hang_text, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao 
            FROM don_hang 
            WHERE ma_nguoi_dung = ? 
            ORDER BY ngay_tao DESC 
            LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">No orders found</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Code</th>
                    <th>Status</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo $order['ma_don_hang_text']; ?></td>
                    <td>
                        <?php
                        $statusClass = '';
                        switch ($order['trang_thai']) {
                            case 'pending': $statusClass = 'warning'; break;
                            case 'approved': $statusClass = 'success'; break;
                            case 'cancelled': $statusClass = 'danger'; break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $order['trang_thai']; ?></span>
                    </td>
                    <td><?php echo $order['phuong_thuc_thanh_toan']; ?></td>
                    <td><?php echo $order['trang_thai_thanh_toan']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- Test Actions -->
    <h3>4. Test Notification Creation</h3>
    <div class="btn-group" role="group">
        <a href="?action=create_cod_notification" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create COD Order Notification
        </a>
        <a href="?action=create_payment_notification" class="btn btn-success">
            <i class="fas fa-plus"></i> Create Payment Notification
        </a>
        <a href="?action=create_approved_notification" class="btn btn-info">
            <i class="fas fa-plus"></i> Create Approved Notification
        </a>
        <a href="?action=clear_all" class="btn btn-danger" onclick="return confirm('Clear all notifications?')">
            <i class="fas fa-trash"></i> Clear All Notifications
        </a>
    </div>
    
    <!-- Current Notifications -->
    <h3 class="mt-4">5. Current Notifications</h3>
    <?php
    $notifications = $notificationManager->getUserNotifications($username, 50);
    ?>
    
    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">No notifications found</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order ID</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Read</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notif): ?>
                    <tr class="<?php echo !$notif['is_read'] ? 'table-warning' : ''; ?>">
                        <td><?php echo $notif['id']; ?></td>
                        <td><?php echo $notif['order_id'] ?: 'N/A'; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $notif['type']; ?></span></td>
                        <td><?php echo htmlspecialchars($notif['title']); ?></td>
                        <td><?php echo htmlspecialchars($notif['message']); ?></td>
                        <td><?php echo $notif['is_read'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- JavaScript Test -->
    <h3 class="mt-4">6. JavaScript API Test</h3>
    <button id="testApiBtn" class="btn btn-primary">Test Notification API</button>
    <div id="apiResult" class="mt-3"></div>
    
    <hr>
    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary">Back to Homepage</a>
    </div>
</div>

<script>
document.getElementById('testApiBtn').addEventListener('click', function() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<div class="alert alert-info">Testing API...</div>';
    
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    const apiUrl = baseUrl + '/administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list';
    
    fetch(apiUrl, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5>API Test Successful!</h5>
                    <p>Unread Count: ${data.unread_count}</p>
                    <p>Total Notifications: ${data.total}</p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h5>API Test Failed!</h5>
                    <p>Error: ${data.error}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5>API Test Error!</h5>
                <p>${error.message}</p>
            </div>
        `;
    });
});
</script>

</body>
</html>
