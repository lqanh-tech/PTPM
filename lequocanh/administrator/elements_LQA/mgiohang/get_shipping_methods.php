<?php
/**
 * API: Lấy danh sách phương thức vận chuyển
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../mod/ShippingMethodCls.php';

try {
    $shippingMethod = new ShippingMethod();
    
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Lấy tất cả phương thức vận chuyển
            $methods = $shippingMethod->getActiveMethods();
            
            echo json_encode([
                'success' => true,
                'data' => $methods
            ]);
            break;
            
        case 'calculate':
            // Tính phí vận chuyển cho phương thức cụ thể
            $methodCode = $_GET['method'] ?? $_POST['method'] ?? 'standard';
            $orderTotal = floatval($_GET['order_total'] ?? $_POST['order_total'] ?? 0);
            $distanceKm = floatval($_GET['distance_km'] ?? $_POST['distance_km'] ?? 0);
            
            $result = $shippingMethod->calculateFee($methodCode, [
                'order_total' => $orderTotal,
                'distance_km' => $distanceKm
            ]);
            
            echo json_encode($result);
            break;
            
        case 'pickup_info':
            // Lấy thông tin cửa hàng cho pickup
            $storeInfo = $shippingMethod->getPickupStoreInfo();
            
            echo json_encode([
                'success' => true,
                'data' => $storeInfo
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    error_log("Error in get_shipping_methods.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
