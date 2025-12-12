<?php
/**
 * API xử lý mã Coupon
 * 
 * Endpoints:
 * - POST: Kiểm tra và áp dụng mã coupon
 * - GET: Lấy thông tin coupon
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/CouponCls.php';

SessionManager::start();

$couponManager = new Coupon();

// Lấy method và action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'validate';

try {
    switch ($method) {
        case 'POST':
            handlePostRequest($couponManager, $action);
            break;
            
        case 'GET':
            handleGetRequest($couponManager, $action);
            break;
            
        default:
            jsonResponse(false, 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    error_log("Coupon API error: " . $e->getMessage());
    jsonResponse(false, 'Có lỗi xảy ra: ' . $e->getMessage(), null, 500);
}

/**
 * Xử lý POST request
 */
function handlePostRequest($couponManager, $action) {
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    switch ($action) {
        case 'validate':
            // Kiểm tra mã coupon
            $code = $input['code'] ?? '';
            $orderTotal = floatval($input['order_total'] ?? 0);
            $userId = $_SESSION['USER'] ?? null;
            
            $result = $couponManager->validateCoupon($code, $orderTotal, $userId);
            
            if ($result['valid']) {
                jsonResponse(true, $result['message'], [
                    'coupon' => [
                        'code' => $result['coupon']->code,
                        'name' => $result['coupon']->name,
                        'description' => $result['coupon']->description,
                        'discount_type' => $result['coupon']->discount_type,
                        'discount_value' => $result['coupon']->discount_value,
                        'max_discount' => $result['coupon']->max_discount,
                        'min_order_value' => $result['coupon']->min_order_value
                    ],
                    'discount_amount' => $result['discount'],
                    'discount_formatted' => number_format($result['discount']) . 'đ'
                ]);
            } else {
                jsonResponse(false, $result['message']);
            }
            break;
            
        case 'remove':
            // Xóa coupon khỏi session
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['coupon_discount']);
            jsonResponse(true, 'Đã xóa mã giảm giá');
            break;
            
        case 'apply':
            // Lưu coupon vào session để sử dụng khi thanh toán
            $code = $input['code'] ?? '';
            $orderTotal = floatval($input['order_total'] ?? 0);
            $userId = $_SESSION['USER'] ?? null;
            
            $result = $couponManager->validateCoupon($code, $orderTotal, $userId);
            
            if ($result['valid']) {
                $_SESSION['applied_coupon'] = $result['coupon']->code;
                $_SESSION['coupon_discount'] = $result['discount'];
                $_SESSION['coupon_data'] = [
                    'code' => $result['coupon']->code,
                    'name' => $result['coupon']->name,
                    'discount_type' => $result['coupon']->discount_type,
                    'discount_value' => $result['coupon']->discount_value,
                    'discount_amount' => $result['discount']
                ];
                
                jsonResponse(true, $result['message'], [
                    'discount_amount' => $result['discount'],
                    'discount_formatted' => number_format($result['discount']) . 'đ'
                ]);
            } else {
                jsonResponse(false, $result['message']);
            }
            break;
            
        default:
            jsonResponse(false, 'Invalid action', null, 400);
    }
}

/**
 * Xử lý GET request
 */
function handleGetRequest($couponManager, $action) {
    switch ($action) {
        case 'info':
            // Lấy thông tin coupon đã áp dụng
            if (isset($_SESSION['applied_coupon'])) {
                jsonResponse(true, 'Coupon applied', [
                    'code' => $_SESSION['applied_coupon'],
                    'discount_amount' => $_SESSION['coupon_discount'] ?? 0,
                    'coupon_data' => $_SESSION['coupon_data'] ?? null
                ]);
            } else {
                jsonResponse(false, 'No coupon applied');
            }
            break;
            
        case 'available':
            // Lấy danh sách coupon có thể sử dụng (public)
            $coupons = $couponManager->getAllCoupons(false);
            $now = date('Y-m-d H:i:s');
            
            $availableCoupons = array_filter($coupons, function($c) use ($now) {
                // Chỉ hiển thị coupon còn hiệu lực và còn lượt
                if ($c->start_date && $now < $c->start_date) return false;
                if ($c->end_date && $now > $c->end_date) return false;
                if ($c->usage_limit !== null && $c->usage_count >= $c->usage_limit) return false;
                return true;
            });
            
            $result = array_map(function($c) {
                return [
                    'code' => $c->code,
                    'name' => $c->name,
                    'description' => $c->description,
                    'discount_type' => $c->discount_type,
                    'discount_value' => $c->discount_value,
                    'max_discount' => $c->max_discount,
                    'min_order_value' => $c->min_order_value,
                    'end_date' => $c->end_date
                ];
            }, array_values($availableCoupons));
            
            jsonResponse(true, 'Available coupons', $result);
            break;
            
        default:
            jsonResponse(false, 'Invalid action', null, 400);
    }
}

/**
 * Trả về JSON response
 */
function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
