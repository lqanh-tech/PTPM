<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MoMo Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .notification-item {
            border-left: 4px solid #28a745;
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
        }
        .notification-item.failed {
            border-left-color: #dc3545;
        }
        .real-time-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Admin Dashboard - MoMo Payment
                    </h2>
                    <div>
                        <span class="real-time-indicator"></span>
                        <small class="text-muted">Real-time monitoring</small>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h3 id="todayRevenue">0 VND</h3>
                            <p class="mb-0">Doanh thu hôm nay</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 id="successCount">0</h3>
                            <p class="mb-0">Giao dịch thành công</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h3 id="failedCount">0</h3>
                            <p class="mb-0">Giao dịch thất bại</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 id="pendingCount">0</h3>
                            <p class="mb-0">Đang xử lý</p>
                        </div>
                    </div>
                </div>
                
                <!-- Real-time Notifications -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="dashboard-card p-4">
                            <h5>
                                <i class="fas fa-bell me-2"></i>
                                Thông báo Real-time
                                <button class="btn btn-sm btn-outline-primary float-end" onclick="toggleNotifications()">
                                    <i class="fas fa-pause" id="notificationToggle"></i>
                                </button>
                            </h5>
                            <div id="notificationsList" style="max-height: 400px; overflow-y: auto;">
                                <!-- Notifications will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="dashboard-card p-4">
                            <h5>
                                <i class="fas fa-cog me-2"></i>
                                Cài đặt thông báo
                            </h5>
                            
                            <form id="notificationSettings">
                                <div class="mb-3">
                                    <label class="form-label">Email nhận thông báo:</label>
                                    <input type="email" class="form-control" id="adminEmail" 
                                           value="admin@yourdomain.com" placeholder="your-email@domain.com">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại:</label>
                                    <input type="tel" class="form-control" id="adminPhone" 
                                           value="0123456789" placeholder="0123456789">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="emailNotification" checked>
                                        <label class="form-check-label" for="emailNotification">
                                            Thông báo qua Email
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="smsNotification">
                                        <label class="form-check-label" for="smsNotification">
                                            Thông báo qua SMS
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Lưu cài đặt
                                </button>
                            </form>
                            
                            <hr>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" onclick="sendTestNotification()">
                                    <i class="fas fa-paper-plane me-2"></i>Test thông báo
                                </button>
                                
                                <button class="btn btn-info" onclick="generateDailyReport()">
                                    <i class="fas fa-file-alt me-2"></i>Báo cáo ngày
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="dashboard-card p-4">
                            <h5>
                                <i class="fas fa-bolt me-2"></i>
                                Thao tác nhanh
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-2">
                                    <a href="demo.php" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-plus me-2"></i>Thanh toán mới
                                    </a>
                                </div>
                                <div class="col-md-2">
                                    <a href="transactions.php" class="btn btn-outline-info w-100 mb-2">
                                        <i class="fas fa-list me-2"></i>Lịch sử GD
                                    </a>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-success w-100 mb-2" onclick="exportTransactions()">
                                        <i class="fas fa-download me-2"></i>Xuất Excel
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-warning w-100 mb-2" onclick="clearLogs()">
                                        <i class="fas fa-trash me-2"></i>Xóa logs
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="test.php" class="btn btn-outline-secondary w-100 mb-2">
                                        <i class="fas fa-bug me-2"></i>Test hệ thống
                                    </a>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-danger w-100 mb-2" onclick="toggleMaintenance()">
                                        <i class="fas fa-tools me-2"></i>Bảo trì
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let notificationEnabled = true;
let refreshInterval;

function loadDashboardData() {
    fetch('api/dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('todayRevenue').textContent = 
                new Intl.NumberFormat('vi-VN').format(data.revenue) + ' VND';
            document.getElementById('successCount').textContent = data.success_count;
            document.getElementById('failedCount').textContent = data.failed_count;
            document.getElementById('pendingCount').textContent = data.pending_count;
        })
        .catch(error => console.error('Error loading dashboard data:', error));
}

function loadNotifications() {
    if (!notificationEnabled) return;
    
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(notifications => {
            const container = document.getElementById('notificationsList');
            container.innerHTML = '';
            
            notifications.forEach(notification => {
                const item = document.createElement('div');
                item.className = `notification-item ${notification.type === 'FAILED' ? 'failed' : ''}`;
                item.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${notification.title}</strong>
                            <p class="mb-1">${notification.message}</p>
                            <small class="text-muted">${notification.time}</small>
                        </div>
                        <div>
                            <i class="fas ${notification.type === 'SUCCESS' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'}"></i>
                        </div>
                    </div>
                `;
                container.appendChild(item);
            });
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function toggleNotifications() {
    notificationEnabled = !notificationEnabled;
    const icon = document.getElementById('notificationToggle');
    
    if (notificationEnabled) {
        icon.className = 'fas fa-pause';
        startAutoRefresh();
    } else {
        icon.className = 'fas fa-play';
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        loadDashboardData();
        loadNotifications();
    }, 5000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
}

function sendTestNotification() {
    fetch('api/test_notification.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Thông báo test đã được gửi!');
            } else {
                alert('❌ Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Lỗi kết nối');
        });
}

function generateDailyReport() {
    window.open('api/daily_report.php', '_blank');
}

function exportTransactions() {
    window.open('api/export_transactions.php', '_blank');
}

function clearLogs() {
    if (confirm('Bạn có chắc muốn xóa tất cả logs?')) {
        fetch('api/clear_logs.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? '✅ Đã xóa logs' : '❌ Lỗi: ' + data.message);
            });
    }
}

function toggleMaintenance() {
    const action = confirm('Bật chế độ bảo trì?') ? 'enable' : 'disable';
    
    fetch('api/maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.success ? '✅ Đã cập nhật chế độ bảo trì' : '❌ Lỗi: ' + data.message);
    });
}

document.getElementById('notificationSettings').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const settings = {
        email: document.getElementById('adminEmail').value,
        phone: document.getElementById('adminPhone').value,
        email_enabled: document.getElementById('emailNotification').checked,
        sms_enabled: document.getElementById('smsNotification').checked
    };
    
    fetch('api/save_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.success ? '✅ Đã lưu cài đặt' : '❌ Lỗi: ' + data.message);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadNotifications();
    startAutoRefresh();
});

window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>

</body>
</html>
