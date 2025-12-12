<?php
/**
 * Shipment Tracking Page
 * 
 * Allows customers to track their orders using:
 * - Order code
 * - Tracking number
 * - GHN order code
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/ShippingCls.php';

SessionManager::start();

$trackingInfo = null;
$error = null;
$searchTerm = '';

// Handle search
if (isset($_GET['track']) && !empty($_GET['track'])) {
    $searchTerm = trim($_GET['track']);
    
    $shipping = new Shipping();
    $result = $shipping->trackShipment($searchTerm);
    
    if ($result['success']) {
        $trackingInfo = $result['tracking_info'];
    } else {
        $error = $result['message'] ?? 'Không tìm thấy thông tin vận đơn';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi đơn hàng - LQA E-commerce</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        .tracking-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .tracking-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .tracking-header h1 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .search-box {
            max-width: 600px;
            margin: 0 auto 40px;
        }
        
        .search-box .input-group {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 50px;
            overflow: hidden;
        }
        
        .search-box input {
            border: none;
            padding: 15px 25px;
            font-size: 16px;
        }
        
        .search-box input:focus {
            box-shadow: none;
        }
        
        .search-box button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        .tracking-timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 60px;
            padding-bottom: 40px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 30px;
            bottom: -10px;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            border: 3px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 18px;
        }
        
        .timeline-item.active .timeline-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            animation: pulse 2s infinite;
        }
        
        .timeline-item.completed .timeline-icon {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 10px;
            border-left: 3px solid #667eea;
        }
        
        .timeline-content h6 {
            margin: 0 0 5px 0;
            color: #333;
            font-weight: 600;
        }
        
        .timeline-content p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .timeline-content .time {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 5px;
        }
        
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .info-card h5 {
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-picked-up { background: #17a2b8; color: #fff; }
        .status-in-transit { background: #007bff; color: #fff; }
        .status-out-for-delivery { background: #6f42c1; color: #fff; }
        .status-delivered { background: #28a745; color: #fff; }
        .status-failed { background: #dc3545; color: #fff; }
        .status-returning { background: #fd7e14; color: #fff; }
        .status-returned { background: #6c757d; color: #fff; }
        
        .no-result {
            text-align: center;
            padding: 60px 20px;
        }
        
        .no-result i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <div class="tracking-header">
            <h1><i class="fas fa-shipping-fast"></i> Theo dõi đơn hàng</h1>
            <p class="text-muted">Nhập mã đơn hàng hoặc mã vận đơn để tra cứu</p>
        </div>
        
        <!-- Search Box -->
        <div class="search-box">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           name="track" 
                           placeholder="Nhập mã đơn hàng (VD: ORDER1732...) hoặc mã vận đơn"
                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                           required>
                    <button class="btn" type="submit">
                        <i class="fas fa-search"></i> Tra cứu
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($error): ?>
            <!-- Error Message -->
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($trackingInfo): ?>
            <!-- Tracking Information -->
            <div class="info-card">
                <h5><i class="fas fa-box"></i> Thông tin đơn hàng</h5>
                <div class="info-row">
                    <span>Mã đơn hàng:</span>
                    <strong><?php echo htmlspecialchars($trackingInfo['order_code']); ?></strong>
                </div>
                <div class="info-row">
                    <span>Mã vận đơn:</span>
                    <strong><?php echo htmlspecialchars($trackingInfo['tracking_number']); ?></strong>
                </div>
                <?php if (!empty($trackingInfo['carrier_order_code'])): ?>
                <div class="info-row">
                    <span>Mã vận đơn GHN:</span>
                    <strong><?php echo htmlspecialchars($trackingInfo['carrier_order_code']); ?></strong>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span>Trạng thái:</span>
                    <span class="status-badge status-<?php echo htmlspecialchars($trackingInfo['status']); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $trackingInfo['status'])); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span>Phí vận chuyển:</span>
                    <strong><?php echo number_format($trackingInfo['shipping_fee'], 0, ',', '.'); ?> ₫</strong>
                </div>
                <?php if ($trackingInfo['estimated_delivery']): ?>
                <div class="info-row">
                    <span>Dự kiến giao:</span>
                    <strong><?php echo date('d/m/Y H:i', strtotime($trackingInfo['estimated_delivery'])); ?></strong>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Delivery Address -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($trackingInfo['to_name']); ?></strong></p>
                    <p class="mb-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($trackingInfo['to_phone']); ?></p>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($trackingInfo['to_address']); ?></p>
                </div>
            </div>
            
            <!-- Tracking Timeline -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-4"><i class="fas fa-route"></i> Lịch sử vận chuyển</h6>
                    
                    <div class="tracking-timeline">
                        <?php
                        $statusList = [
                            'pending' => ['icon' => 'fa-clock', 'title' => 'Đơn hàng đã được tạo', 'desc' => 'Đơn hàng đang chờ xử lý'],
                            'picked_up' => ['icon' => 'fa-box-open', 'title' => 'Đã lấy hàng', 'desc' => 'Shipper đã nhận hàng từ người gửi'],
                            'in_transit' => ['icon' => 'fa-truck', 'title' => 'Đang vận chuyển', 'desc' => 'Đơn hàng đang trên đường vận chuyển'],
                            'out_for_delivery' => ['icon' => 'fa-shipping-fast', 'title' => 'Đang giao hàng', 'desc' => 'Shipper đang trên đường giao hàng đến bạn'],
                            'delivered' => ['icon' => 'fa-check-circle', 'title' => 'Đã giao hàng', 'desc' => 'Giao hàng thành công'],
                        ];
                        
                        $currentStatus = $trackingInfo['status'];
                        $statusKeys = array_keys($statusList);
                        $currentIndex = array_search($currentStatus, $statusKeys);
                        
                        foreach ($statusList as $key => $status):
                            $itemIndex = array_search($key, $statusKeys);
                            $isCompleted = $itemIndex < $currentIndex;
                            $isActive = $key === $currentStatus;
                            $class = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                        ?>
                            <div class="timeline-item <?php echo $class; ?>">
                                <div class="timeline-icon">
                                    <i class="fas <?php echo $status['icon']; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6><?php echo $status['title']; ?></h6>
                                    <p><?php echo $status['desc']; ?></p>
                                    <?php if ($isActive || $isCompleted): ?>
                                        <div class="time">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('d/m/Y H:i', strtotime($trackingInfo['updated_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php if ($key === $currentStatus && !empty($trackingInfo['current_location'])): ?>
                                        <p class="mt-2"><i class="fas fa-location-arrow"></i> <small><?php echo htmlspecialchars($trackingInfo['current_location']); ?></small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Additional Notes -->
            <?php if (!empty($trackingInfo['note']) || !empty($trackingInfo['customer_note'])): ?>
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                    <?php if (!empty($trackingInfo['note'])): ?>
                        <p class="mb-1"><strong>Ghi chú vận chuyển:</strong> <?php echo htmlspecialchars($trackingInfo['note']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($trackingInfo['customer_note'])): ?>
                        <p class="mb-0"><strong>Ghi chú khách hàng:</strong> <?php echo htmlspecialchars($trackingInfo['customer_note']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php elseif (empty($searchTerm)): ?>
            <!-- Initial State -->
            <div class="no-result">
                <i class="fas fa-search-location"></i>
                <h5>Nhập mã đơn hàng để bắt đầu tra cứu</h5>
                <p class="text-muted">Bạn có thể tìm mã đơn hàng trong email xác nhận hoặc lịch sử đơn hàng</p>
            </div>
        <?php endif; ?>
        
        <!-- Back Link -->
        <div class="back-link">
            <a href="../../index.php" class="btn btn-outline-primary">
                <i class="fas fa-home"></i> Về trang chủ
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
