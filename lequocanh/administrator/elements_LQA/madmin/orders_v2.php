<?php
// Security includes
require_once __DIR__ . '/../mod/SecurityHelpers.php';
require_once __DIR__ . '/../mod/InputValidator.php';


require_once './elements_LQA/mod/sessionManager.php';
require_once './elements_LQA/config/logger_config.php';

SessionManager::start();

require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (empty($username)) {
    header('Location: ./userLogin.php');
    exit();
}

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('don_hang', $username)) {
    echo "<div class='alert alert-danger'>Bạn không có quyền truy cập trang này!</div>";
    exit();
}

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/mtonkhoCls.php';
require_once './elements_LQA/mod/CustomerNotificationManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$tonkho = new MTonKho();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $orderId = (int)$_GET['id'];
    
    try {
        switch ($action) {
            case 'approve':

                $updateSql = "UPDATE don_hang SET trang_thai = 'approved', ngay_cap_nhat = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                $orderInfoSql = "SELECT ma_nguoi_dung FROM don_hang WHERE id = ?";
                $orderInfoStmt = $conn->prepare($orderInfoSql);
                $orderInfoStmt->execute([$orderId]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($orderInfo) {
                    $notificationManager = new CustomerNotificationManager();
                    $notificationManager->notifyOrderApproved($orderId, $orderInfo['ma_nguoi_dung']);
                }
                
                $_SESSION['success_message'] = "Đã duyệt đơn hàng #$orderId thành công!";
                break;
                
            case 'cancel':

                $itemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                $itemsStmt = $conn->prepare($itemsSql);
                $itemsStmt->execute([$orderId]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($items as $item) {
                    $tonkho->updateSoLuong($item['ma_san_pham'], $item['so_luong'], true);
                }
                
                $updateSql = "UPDATE don_hang SET trang_thai = 'cancelled', ngay_cap_nhat = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                $orderInfoSql = "SELECT ma_nguoi_dung FROM don_hang WHERE id = ?";
                $orderInfoStmt = $conn->prepare($orderInfoSql);
                $orderInfoStmt->execute([$orderId]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($orderInfo) {
                    $notificationManager = new CustomerNotificationManager();
                    $notificationManager->notifyOrderCancelled($orderId, $orderInfo['ma_nguoi_dung'], 'Đơn hàng bị hủy bởi admin');
                }
                
                $_SESSION['success_message'] = "Đã hủy đơn hàng #$orderId và hoàn trả tồn kho!";
                break;
                
            case 'approve_return':

                $itemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                $itemsStmt = $conn->prepare($itemsSql);
                $itemsStmt->execute([$orderId]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($items as $item) {
                    $tonkho->updateSoLuong($item['ma_san_pham'], $item['so_luong'], true);
                }
                
                $updateSql = "UPDATE don_hang SET trang_thai_doi_tra = 'approved', ngay_xu_ly_doi_tra = NOW(), ngay_cap_nhat = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                $_SESSION['success_message'] = "Đã duyệt yêu cầu đổi/trả đơn hàng #$orderId và hoàn kho!";
                break;
                
            case 'reject_return':

                $updateSql = "UPDATE don_hang SET trang_thai_doi_tra = 'rejected', ngay_xu_ly_doi_tra = NOW(), ngay_cap_nhat = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                $_SESSION['success_message'] = "Đã từ chối yêu cầu đổi/trả đơn hàng #$orderId!";
                break;
                
            case 'confirm_delivery':

                $orderInfoSql = "SELECT * FROM don_hang WHERE id = ?";
                $orderInfoStmt = $conn->prepare($orderInfoSql);
                $orderInfoStmt->execute([$orderId]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$orderInfo) {
                    throw new Exception('Không tìm thấy đơn hàng');
                }
                
                if ($orderInfo['trang_thai'] !== 'approved') {
                    throw new Exception('Đơn hàng chưa được duyệt');
                }
                
                $updateSql = "UPDATE don_hang SET trang_thai = 'delivered', ngay_giao_hang = NOW(), ngay_cap_nhat = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                $notificationManager = new CustomerNotificationManager();
                $title = "📦 Đơn hàng #{$orderId} đã được giao";
                $isCOD = ($orderInfo['phuong_thuc_thanh_toan'] === 'cod');
                if ($isCOD) {
                    $message = "Đơn hàng #{$orderInfo['ma_don_hang_text']} đã được giao. Vui lòng xác nhận đã nhận hàng và thanh toán.";
                } else {
                    $message = "Đơn hàng #{$orderInfo['ma_don_hang_text']} đã được giao. Vui lòng xác nhận đã nhận hàng.";
                }
                $notificationManager->createNotification($orderInfo['ma_nguoi_dung'], $title, $message, 'order_delivered', $orderId);
                
                $_SESSION['success_message'] = "Đã xác nhận giao hàng cho đơn #$orderId!";
                break;
                
            case 'complete_order':

                $orderInfoSql = "SELECT * FROM don_hang WHERE id = ?";
                $orderInfoStmt = $conn->prepare($orderInfoSql);
                $orderInfoStmt->execute([$orderId]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$orderInfo) {
                    throw new Exception('Không tìm thấy đơn hàng');
                }
                
                if (!in_array($orderInfo['trang_thai'], ['approved', 'delivered'])) {
                    throw new Exception('Đơn hàng không ở trạng thái có thể hoàn tất');
                }
                
                $isCOD = ($orderInfo['phuong_thuc_thanh_toan'] === 'cod');
                if ($isCOD) {
                    $updateSql = "UPDATE don_hang SET 
                                  trang_thai = 'completed', 
                                  trang_thai_thanh_toan = 'paid',
                                  ngay_nhan_hang = NOW(), 
                                  ngay_cap_nhat = NOW() 
                                  WHERE id = ?";
                } else {

                    $updateSql = "UPDATE don_hang SET 
                                  trang_thai = 'completed', 
                                  ngay_nhan_hang = NOW(), 
                                  ngay_cap_nhat = NOW() 
                                  WHERE id = ?";
                }
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                $notificationManager = new CustomerNotificationManager();
                $notificationManager->notifyOrderSuccess($orderId, $orderInfo['ma_nguoi_dung']);
                
                $_SESSION['success_message'] = "Đã hoàn tất đơn hàng #$orderId!";
                break;
        }
        
        header('Location: index.php?req=don_hang&t=' . time());
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
        error_log("Error processing order action: " . $e->getMessage());
    }
}

$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$returnFilter = isset($_GET['return_status']) ? $_GET['return_status'] : 'all';

$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$searchDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$searchPriceMin = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$searchPriceMax = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$searchPaymentMethod = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$searchProvince = isset($_GET['province']) ? trim($_GET['province']) : '';

$statsSql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN trang_thai = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN trang_thai = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN trang_thai = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN trang_thai = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN trang_thai = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN trang_thai_doi_tra = 'requested' THEN 1 ELSE 0 END) as return_requested,
    SUM(CASE WHEN trang_thai_doi_tra = 'approved' THEN 1 ELSE 0 END) as return_approved,
    SUM(CASE WHEN trang_thai IN ('approved', 'delivered', 'completed') THEN tong_tien ELSE 0 END) as total_revenue
FROM don_hang";

$statsStmt = $conn->query($statsSql);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$whereClauses = [];
$params = [];

if ($statusFilter != 'all') {
    $whereClauses[] = "don_hang.trang_thai = ?";
    $params[] = $statusFilter;
}

if ($returnFilter != 'all') {
    $whereClauses[] = "don_hang.trang_thai_doi_tra = ?";
    $params[] = $returnFilter;
}

if (!empty($searchKeyword)) {
    $whereClauses[] = "(don_hang.ma_don_hang_text LIKE ? OR 
                        don_hang.ma_nguoi_dung LIKE ? OR 
                        don_hang.dia_chi_giao_hang LIKE ? OR
                        don_hang.id IN (
                            SELECT DISTINCT ma_don_hang 
                            FROM chi_tiet_don_hang 
                            INNER JOIN hanghoa ON chi_tiet_don_hang.ma_san_pham = hanghoa.idhanghoa
                            WHERE hanghoa.tenhanghoa LIKE ?
                        ))";
    $searchParam = "%$searchKeyword%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($searchDateFrom)) {
    $whereClauses[] = "DATE(don_hang.ngay_tao) >= ?";
    $params[] = $searchDateFrom;
}
if (!empty($searchDateTo)) {
    $whereClauses[] = "DATE(don_hang.ngay_tao) <= ?";
    $params[] = $searchDateTo;
}

if (!empty($searchPriceMin)) {
    $whereClauses[] = "don_hang.tong_tien >= ?";
    $params[] = $searchPriceMin;
}
if (!empty($searchPriceMax)) {
    $whereClauses[] = "don_hang.tong_tien <= ?";
    $params[] = $searchPriceMax;
}

if (!empty($searchPaymentMethod)) {
    $whereClauses[] = "don_hang.phuong_thuc_thanh_toan = ?";
    $params[] = $searchPaymentMethod;
}

if (!empty($searchProvince)) {
    $whereClauses[] = "don_hang.dia_chi_giao_hang LIKE ?";
    $params[] = "%$searchProvince%";
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$ordersSql = "SELECT don_hang.* FROM don_hang $whereSQL ORDER BY don_hang.ngay_tao DESC";
$ordersStmt = $conn->prepare($ordersSql);
$ordersStmt->execute($params);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Quản lý đơn hàng - <?php echo date('H:i:s'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Export CSS -->
    <link rel="stylesheet" href="./css_LQA/order_export.css">
    <style>
        body { background: #f8f9fa; }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stats-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .stats-label { color: #6c757d; font-size: 14px; }
        
        .bg-pending { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
        .bg-approved { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .bg-cancelled { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .bg-return { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        .bg-revenue { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); }
        
        .filter-tabs {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filter-tabs .nav-link {
            border-radius: 8px;
            margin: 0 5px;
            font-weight: 500;
        }
        .filter-tabs .nav-link.active {
            background: #0d6efd;
            color: white;
        }
        
        .order-table-inner th { padding: 15px; font-weight: 600; }
        .order-table-inner td { padding: 12px; vertical-align: middle; }
        .order-table-inner tbody tr:hover { background: #f8f9fa; }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-return {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin: 2px;
        }
        
        .search-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .search-input-group {
            position: relative;
        }
        .search-input-group input {
            border-radius: 25px;
            padding: 12px 50px 12px 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .search-input-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 20px;
            padding: 8px 20px;
        }
        .advanced-search {
            margin-top: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            display: none;
        }
        .advanced-search.show {
            display: block;
        }
        .advanced-search .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .advanced-search .form-control,
        .advanced-search .form-select {
            border-radius: 8px;
        }
        .toggle-advanced {
            color: #667eea;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 10px;
        }
        .toggle-advanced:hover {
            color: #5568d3;
        }
        .toggle-advanced i {
            transition: transform 0.3s;
        }
        .toggle-advanced.active i {
            transform: rotate(180deg);
        }
        .search-tags {
            margin-top: 15px;
        }
        .search-tag {
            display: inline-block;
            background: #e7f3ff;
            color: #0066cc;
            padding: 5px 12px;
            border-radius: 15px;
            margin: 3px;
            font-size: 13px;
        }
        .search-tag i {
            margin-left: 5px;
            cursor: pointer;
        }
        .search-tag i:hover {
            color: #cc0000;
        }
        .clear-all-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 13px;
            cursor: pointer;
        }
        .clear-all-btn:hover {
            background: #c82333;
        }
        
        .table-scroll-container {
            max-height: 60vh;
            min-height: 300px;
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #ddd;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .table-scroll-container::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }
        
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 5px;
        }
        
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #0056b3;
        }
        
        .table-scroll-container .order-table-inner {
            margin-bottom: 0;
            min-width: 1000px;
        }
        
        .table-scroll-container .order-table-inner thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table-scroll-container .order-table-inner thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 15px 10px;
            font-weight: 600;
            border-bottom: 2px solid #007bff;
            white-space: nowrap;
        }
        
        .order-actions {
            white-space: nowrap;
            min-width: 220px;
        }
        
        .order-actions .action-btn {
            margin: 2px;
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .order-actions .btn-action {
            padding: 4px 8px;
            font-size: 12px;
            margin: 2px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .order-actions .btn-action:hover {
            background: #e9ecef;
        }
        
        .order-actions .btn-action-print {
            color: #6c757d;
        }
        
        .order-actions .btn-action-export {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <!-- Thông báo -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shopping-cart me-2"></i>Quản lý đơn hàng</h2>
            <div>
                <span class="badge bg-secondary me-2">
                    <i class="fas fa-clock me-1"></i>Cập nhật: <?php echo date('H:i:s'); ?>
                </span>
                <button class="btn btn-outline-primary" onclick="window.location.href=window.location.href.split('?')[0]+'?req=don_hang&t='+Date.now()">
                    <i class="fas fa-sync-alt me-2"></i>Làm mới
                </button>
            </div>
        </div>

        <!-- Export Toolbar -->
        <div class="export-toolbar" style="margin-bottom: 20px;">
            <div class="export-toolbar-left">
                <div class="select-all-container">
                    <input type="checkbox" id="select-all-orders">
                    <label for="select-all-orders">Chọn tất cả</label>
                </div>
                
                <span class="selected-count" id="selected-count" style="display: none;">
                    Đã chọn: <span id="count-number">0</span>
                </span>
            </div>
            
            <div class="export-toolbar-right">
                <!-- Xuất chi tiết các đơn đã chọn -->
                <button class="btn-export btn-export-pdf" id="btn-export-pdf" disabled>
                    <i class="fas fa-file-pdf"></i> Xuất PDF
                </button>
                
                <button class="btn-export btn-export-excel" id="btn-export-excel" disabled>
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
                
                <!-- Xuất tổng hợp theo bộ lọc -->
                <button class="btn-export btn-export-summary" id="btn-export-summary-pdf">
                    <i class="fas fa-file-pdf"></i> Báo cáo PDF
                </button>
                
                <button class="btn-export btn-export-summary" id="btn-export-summary-excel">
                    <i class="fas fa-file-excel"></i> Báo cáo Excel
                </button>
            </div>
        </div>

        <!-- Statistics Cards - Row 1 -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-2">
                <div class="stats-card" onclick="window.location='?req=don_hang&status=pending'" style="cursor:pointer">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo $stats['pending']; ?></div>
                            <div class="stats-label">Chờ xác nhận</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-2">
                <div class="stats-card" onclick="window.location='?req=don_hang&status=approved'" style="cursor:pointer">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-approved">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo $stats['approved']; ?></div>
                            <div class="stats-label">Đang giao</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-2">
                <div class="stats-card" onclick="window.location='?req=don_hang&status=delivered'" style="cursor:pointer">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo $stats['delivered'] ?? 0; ?></div>
                            <div class="stats-label">Đã giao</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-2">
                <div class="stats-card" onclick="window.location='?req=don_hang&status=completed'" style="cursor:pointer">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo $stats['completed'] ?? 0; ?></div>
                            <div class="stats-label">Hoàn tất</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-2">
                <div class="stats-card" onclick="window.location='?req=don_hang&status=cancelled'" style="cursor:pointer">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-cancelled">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo $stats['cancelled']; ?></div>
                            <div class="stats-label">Đã hủy</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-2">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo number_format($stats['total_revenue']/1000000, 1); ?>M</div>
                            <div class="stats-label">Doanh thu</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards - Row 2 (Return requests) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stats-card" onclick="window.location='?req=don_hang&return_status=requested'" style="cursor:pointer">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-return">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stats-number"><?php echo $stats['return_requested']; ?></div>
                            <div class="stats-label">Yêu cầu đổi/trả</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Container -->
        <div class="search-container">
            <form method="GET" action="" id="searchForm">
                <input type="hidden" name="req" value="don_hang">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <input type="hidden" name="return_status" value="<?php echo htmlspecialchars($returnFilter); ?>">
                
                <!-- Basic Search -->
                <div class="search-input-group">
                    <input type="text" 
                           class="form-control form-control-lg" 
                           name="search" 
                           id="searchInput"
                           placeholder="🔍 Tìm kiếm theo mã đơn hàng, tên khách hàng, số điện thoại, tên sản phẩm..."
                           value="<?php echo htmlspecialchars($searchKeyword); ?>">
                    <button type="submit" class="btn btn-primary search-btn">
                        <i class="fas fa-search me-1"></i>Tìm
                    </button>
                </div>
                
                <!-- Toggle Advanced Search -->
                <a href="#" class="toggle-advanced" id="toggleAdvanced">
                    <i class="fas fa-sliders-h me-2"></i>
                    <span>Tìm kiếm nâng cao</span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </a>
                
                <!-- Advanced Search -->
                <div class="advanced-search" id="advancedSearch">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Từ ngày
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   name="date_from" 
                                   value="<?php echo htmlspecialchars($searchDateFrom); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Đến ngày
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   name="date_to" 
                                   value="<?php echo htmlspecialchars($searchDateTo); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill-wave me-1"></i>Giá từ (₫)
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="price_min" 
                                   placeholder="0"
                                   value="<?php echo htmlspecialchars($searchPriceMin); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill-wave me-1"></i>Giá đến (₫)
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="price_max" 
                                   placeholder="10000000"
                                   value="<?php echo htmlspecialchars($searchPriceMax); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-credit-card me-1"></i>Phương thức thanh toán
                            </label>
                            <select class="form-select" name="payment_method">
                                <option value="">Tất cả</option>
                                <option value="momo" <?php echo $searchPaymentMethod == 'momo' ? 'selected' : ''; ?>>MoMo</option>
                                <option value="cod" <?php echo $searchPaymentMethod == 'cod' ? 'selected' : ''; ?>>COD</option>
                                <option value="bank_transfer" <?php echo $searchPaymentMethod == 'bank_transfer' ? 'selected' : ''; ?>>Chuyển khoản</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Tỉnh/Thành phố
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="province" 
                                   placeholder="VD: Hà Nội, TP.HCM..."
                                   value="<?php echo htmlspecialchars($searchProvince); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Tìm kiếm
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Active Search Tags -->
            <?php
            $hasActiveSearch = !empty($searchKeyword) || !empty($searchDateFrom) || !empty($searchDateTo) || 
                              !empty($searchPriceMin) || !empty($searchPriceMax) || !empty($searchPaymentMethod) || 
                              !empty($searchProvince);
            
            if ($hasActiveSearch):
            ?>
            <div class="search-tags">
                <strong class="me-2">Đang lọc:</strong>
                
                <?php if (!empty($searchKeyword)): ?>
                    <span class="search-tag">
                        Từ khóa: "<?php echo htmlspecialchars($searchKeyword); ?>"
                        <i class="fas fa-times" onclick="removeSearchParam('search')"></i>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($searchDateFrom) || !empty($searchDateTo)): ?>
                    <span class="search-tag">
                        Thời gian: <?php echo $searchDateFrom ?: '...'; ?> → <?php echo $searchDateTo ?: '...'; ?>
                        <i class="fas fa-times" onclick="removeSearchParams(['date_from', 'date_to'])"></i>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($searchPriceMin) || !empty($searchPriceMax)): ?>
                    <span class="search-tag">
                        Giá: <?php echo number_format($searchPriceMin ?: 0); ?>₫ - <?php echo number_format($searchPriceMax ?: 0); ?>₫
                        <i class="fas fa-times" onclick="removeSearchParams(['price_min', 'price_max'])"></i>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($searchPaymentMethod)): ?>
                    <span class="search-tag">
                        PT: <?php 
                            switch($searchPaymentMethod) {
                                case 'momo': echo 'MoMo'; break;
                                case 'cod': echo 'COD'; break;
                                case 'bank_transfer': echo 'Chuyển khoản'; break;
                            }
                        ?>
                        <i class="fas fa-times" onclick="removeSearchParam('payment_method')"></i>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($searchProvince)): ?>
                    <span class="search-tag">
                        Địa chỉ: <?php echo htmlspecialchars($searchProvince); ?>
                        <i class="fas fa-times" onclick="removeSearchParam('province')"></i>
                    </span>
                <?php endif; ?>
                
                <button class="clear-all-btn" onclick="clearAllSearch()">
                    <i class="fas fa-times-circle me-1"></i>Xóa tất cả
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?php echo $statusFilter == 'all' ? 'active' : ''; ?>" 
                       href="?req=don_hang&status=all">
                        <i class="fas fa-list me-2"></i>Tất cả (<?php echo $stats['total']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $statusFilter == 'pending' ? 'active' : ''; ?>" 
                       href="?req=don_hang&status=pending">
                        <i class="fas fa-clock me-2"></i>Chờ xác nhận (<?php echo $stats['pending']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $statusFilter == 'approved' ? 'active' : ''; ?>" 
                       href="?req=don_hang&status=approved">
                        <i class="fas fa-shipping-fast me-2"></i>Đang giao (<?php echo $stats['approved']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $statusFilter == 'delivered' ? 'active' : ''; ?>" 
                       href="?req=don_hang&status=delivered">
                        <i class="fas fa-truck me-2"></i>Đã giao (<?php echo $stats['delivered'] ?? 0; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $statusFilter == 'completed' ? 'active' : ''; ?>" 
                       href="?req=don_hang&status=completed">
                        <i class="fas fa-check-double me-2"></i>Hoàn tất (<?php echo $stats['completed'] ?? 0; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $statusFilter == 'cancelled' ? 'active' : ''; ?>" 
                       href="?req=don_hang&status=cancelled">
                        <i class="fas fa-times me-2"></i>Đã hủy (<?php echo $stats['cancelled']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $returnFilter == 'requested' ? 'active' : ''; ?>" 
                       href="?req=don_hang&return_status=requested">
                        <i class="fas fa-exchange-alt me-2"></i>Yêu cầu đổi/trả (<?php echo $stats['return_requested']; ?>)
                    </a>
                </li>
            </ul>
        </div>

        <!-- Orders Table -->
        <div class="table-scroll-container">
            <table class="table table-hover mb-0 order-table-inner">
                <thead>
                    <tr>
                        <th style="width: 40px; min-width: 40px;">
                            <input type="checkbox" id="select-all-orders-table">
                        </th>
                        <th style="width: 50px; min-width: 50px;">ID</th>
                        <th style="width: 140px; min-width: 140px;">Mã đơn hàng</th>
                        <th style="width: 100px; min-width: 100px;">Khách hàng</th>
                        <th style="width: 110px; min-width: 110px;">Tổng tiền</th>
                        <th style="width: 90px; min-width: 90px;">Phương thức</th>
                        <th style="width: 130px; min-width: 130px;">Trạng thái</th>
                        <th style="width: 90px; min-width: 90px;">Ngày đặt</th>
                        <th style="width: 220px; min-width: 220px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Không có đơn hàng nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="order-checkbox-cell">
                                    <input type="checkbox" class="order-checkbox" value="<?php echo $order['id']; ?>">
                                </td>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?php echo $order['ma_don_hang_text']; ?></div>
                                    <small class="text-muted">
                                        <?php
                                        switch ($order['phuong_thuc_thanh_toan']) {
                                            case 'momo': echo '<i class="fas fa-mobile-alt"></i> MoMo'; break;
                                            case 'cod': echo '<i class="fas fa-money-bill"></i> COD'; break;
                                            case 'bank_transfer': echo '<i class="fas fa-university"></i> Chuyển khoản'; break;
                                            default: echo $order['phuong_thuc_thanh_toan'];
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($order['ma_nguoi_dung']); ?></td>
                                <td>
                                    <span class="fw-bold text-danger">
                                        <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    switch ($order['phuong_thuc_thanh_toan']) {
                                        case 'momo':
                                            echo '<span class="badge bg-primary">MoMo</span>';
                                            break;
                                        case 'cod':
                                            echo '<span class="badge bg-success">COD</span>';
                                            break;
                                        case 'bank_transfer':
                                            echo '<span class="badge bg-info">Chuyển khoản</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">' . $order['phuong_thuc_thanh_toan'] . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php

                                    switch ($order['trang_thai']) {
                                        case 'pending':
                                            echo '<span class="badge-status bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ xác nhận</span>';
                                            break;
                                        case 'approved':
                                            echo '<span class="badge-status bg-info"><i class="fas fa-check me-1"></i>Đã duyệt</span>';
                                            break;
                                        case 'delivered':
                                            echo '<span class="badge-status bg-primary"><i class="fas fa-truck me-1"></i>Đã giao</span>';
                                            break;
                                        case 'completed':
                                            echo '<span class="badge-status bg-success"><i class="fas fa-check-double me-1"></i>Hoàn tất</span>';
                                            break;
                                        case 'cancelled':
                                            echo '<span class="badge-status bg-danger"><i class="fas fa-times me-1"></i>Đã hủy</span>';
                                            break;
                                    }
                                    
                                    if ($order['phuong_thuc_thanh_toan'] == 'cod') {
                                        echo '<br>';
                                        if ($order['trang_thai_thanh_toan'] == 'paid') {
                                            echo '<span class="badge bg-success"><i class="fas fa-money-bill me-1"></i>Đã TT</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary"><i class="fas fa-money-bill me-1"></i>Chưa TT</span>';
                                        }
                                    }
                                    
                                    $returnStatus = isset($order['trang_thai_doi_tra']) ? $order['trang_thai_doi_tra'] : 'none';
                                    if ($returnStatus != 'none') {
                                        echo '<br>';
                                        switch ($returnStatus) {
                                            case 'requested':
                                                echo '<span class="badge-return bg-warning text-dark"><i class="fas fa-exchange-alt me-1"></i>Yêu cầu đổi/trả</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="badge-return bg-info"><i class="fas fa-check-circle me-1"></i>Đã duyệt đổi/trả</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="badge-return bg-secondary"><i class="fas fa-times-circle me-1"></i>Từ chối đổi/trả</span>';
                                                break;
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($order['ngay_tao'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($order['ngay_tao'])); ?></small>
                                </td>
                                <td class="order-actions">
                                    <button type="button" 
                                            class="action-btn btn btn-sm btn-info" 
                                            onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i> Xem
                                    </button>
                                    
                                    <?php if ($order['trang_thai'] == 'pending'): ?>
                                        <a href="?req=don_hang&action=approve&id=<?php echo $order['id']; ?>" 
                                           class="action-btn btn btn-sm btn-success"
                                           onclick="return confirm('Xác nhận duyệt đơn hàng?')">
                                            <i class="fas fa-check"></i> Duyệt
                                        </a>
                                        <a href="?req=don_hang&action=cancel&id=<?php echo $order['id']; ?>" 
                                           class="action-btn btn btn-sm btn-danger"
                                           onclick="return confirm('Xác nhận hủy đơn hàng?')">
                                            <i class="fas fa-times"></i> Hủy
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['trang_thai'] == 'approved'): ?>
                                        <a href="?req=don_hang&action=confirm_delivery&id=<?php echo $order['id']; ?>" 
                                           class="action-btn btn btn-sm btn-primary"
                                           onclick="return confirm('Xác nhận đã giao hàng cho khách?')">
                                            <i class="fas fa-truck"></i> Đã giao
                                        </a>
                                        <a href="?req=don_hang&action=complete_order&id=<?php echo $order['id']; ?>" 
                                           class="action-btn btn btn-sm btn-success"
                                           onclick="return confirm('Xác nhận đơn hàng đã hoàn tất<?php echo $order['phuong_thuc_thanh_toan'] == 'cod' ? ' và thanh toán' : ''; ?>?')">
                                            <i class="fas fa-check-double"></i> Hoàn tất
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['trang_thai'] == 'delivered'): ?>
                                        <a href="?req=don_hang&action=complete_order&id=<?php echo $order['id']; ?>" 
                                           class="action-btn btn btn-sm btn-success"
                                           onclick="return confirm('Xác nhận đơn hàng đã hoàn tất?')">
                                            <i class="fas fa-check-double"></i> Hoàn tất
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($returnStatus == 'requested'): ?>
                                        <a href="?req=don_hang&action=approve_return&id=<?php echo $order['id']; ?>" 
                                           class="action-btn btn btn-sm btn-success"
                                           onclick="return confirm('Duyệt yêu cầu đổi/trả?')">
                                            <i class="fas fa-check-circle"></i> Duyệt đổi/trả
                                        </a>
                                    <?php endif; ?>
                                    
                                    <br>
                                    
                                    <button class="btn-action btn-action-print btn btn-sm" 
                                            onclick="orderExporter.printInvoice(<?php echo $order['id']; ?>)"
                                            title="In hóa đơn">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    
                                    <button class="btn-action btn-action-export btn btn-sm" 
                                            onclick="orderExporter.exportSinglePDF(<?php echo $order['id']; ?>)"
                                            title="Xuất PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                    
                                    <button class="btn-action btn-action-export btn btn-sm" 
                                            onclick="orderExporter.exportSingleExcel(<?php echo $order['id']; ?>)"
                                            title="Xuất Excel">
                                        <i class="fas fa-file-excel"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div><!-- End table-scroll-container -->
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Chi Tiết Đơn Hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Đang tải thông tin...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js_LQA/order_export.js"></script>
    
    <script>

        function viewOrderDetail(orderId) {

            const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
            modal.show();
            
            fetch('elements_LQA/madmin/get_order_detail.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('orderDetailContent').innerHTML = 
                        '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Lỗi khi tải thông tin đơn hàng!</div>';
                    console.error('Error:', error);
                });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllToolbar = document.getElementById('select-all-orders');
            const selectAllTable = document.getElementById('select-all-orders-table');
            
            if (selectAllTable && selectAllToolbar) {
                selectAllTable.addEventListener('change', function(e) {
                    selectAllToolbar.checked = e.target.checked;
                    selectAllToolbar.dispatchEvent(new Event('change'));
                });
            }
            
            function updateSelectedCount() {
                const count = document.querySelectorAll('.order-checkbox:checked').length;
                const countDisplay = document.getElementById('selected-count');
                const countNumber = document.getElementById('count-number');
                
                if (count > 0) {
                    countDisplay.style.display = 'block';
                    countNumber.textContent = count;
                } else {
                    countDisplay.style.display = 'none';
                }
            }
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('order-checkbox') || e.target.id === 'select-all-orders') {
                    updateSelectedCount();
                }
            });
        });
    </script>
    
    <script>

        document.getElementById('toggleAdvanced').addEventListener('click', function(e) {
            e.preventDefault();
            const advancedSearch = document.getElementById('advancedSearch');
            const toggleBtn = this;
            
            advancedSearch.classList.toggle('show');
            toggleBtn.classList.toggle('active');
            
            if (advancedSearch.classList.contains('show')) {
                toggleBtn.querySelector('span').textContent = 'Ẩn tìm kiếm nâng cao';
            } else {
                toggleBtn.querySelector('span').textContent = 'Tìm kiếm nâng cao';
            }
        });
        
        <?php if (!empty($searchDateFrom) || !empty($searchDateTo) || !empty($searchPriceMin) || 
                  !empty($searchPriceMax) || !empty($searchPaymentMethod) || !empty($searchProvince)): ?>
        document.getElementById('advancedSearch').classList.add('show');
        document.getElementById('toggleAdvanced').classList.add('active');
        document.getElementById('toggleAdvanced').querySelector('span').textContent = 'Ẩn tìm kiếm nâng cao';
        <?php endif; ?>
        
        function removeSearchParam(param) {
            const url = new URL(window.location.href);
            url.searchParams.delete(param);
            window.location.href = url.toString();
        }
        
        function removeSearchParams(params) {
            const url = new URL(window.location.href);
            params.forEach(param => url.searchParams.delete(param));
            window.location.href = url.toString();
        }
        
        function clearAllSearch() {
            const url = new URL(window.location.href);
            const req = url.searchParams.get('req');
            const status = url.searchParams.get('status');
            const returnStatus = url.searchParams.get('return_status');
            
            const newUrl = new URL(window.location.origin + window.location.pathname);
            if (req) newUrl.searchParams.set('req', req);
            if (status && status !== 'all') newUrl.searchParams.set('status', status);
            if (returnStatus && returnStatus !== 'all') newUrl.searchParams.set('return_status', returnStatus);
            
            window.location.href = newUrl.toString();
        }
        
        <?php if (!empty($searchKeyword)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const keyword = <?php echo json_encode($searchKeyword); ?>;
            const tableBody = document.querySelector('.order-table tbody');
            
            if (tableBody && keyword) {
                const regex = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                
                tableBody.querySelectorAll('td').forEach(function(cell) {
                    if (cell.querySelector('a, button')) return;
                    
                    const text = cell.textContent;
                    if (regex.test(text)) {
                        cell.innerHTML = cell.innerHTML.replace(regex, '<mark style="background: #fff3cd; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                    }
                });
            }
        });
        <?php endif; ?>
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('searchForm').submit();
            }
        });
        
        document.getElementById('searchForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang tìm...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
