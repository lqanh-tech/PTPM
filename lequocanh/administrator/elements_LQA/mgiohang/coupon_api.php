<?php

header('Content-Type: application/json; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header_remove('X-Powered-By');

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';
require_once __DIR__ . '/../mod/CouponCls.php';

SessionManager::start();

$rateLimitKey = 'coupon_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'start' => time()];
}
$rateData = $_SESSION[$rateLimitKey];
if (time() - $rateData['start'] > 60) {
    $_SESSION[$rateLimitKey] = ['count' => 1, 'start' => time()];
} else {
    $_SESSION[$rateLimitKey]['count']++;
    if ($_SESSION[$rateLimitKey]['count'] > 30) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests']);
        exit;
    }
}

$couponManager = new Coupon();

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

function handlePostRequest($couponManager, $action) {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    switch ($action) {
        case 'validate':

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

            unset($_SESSION['applied_coupon']);
            unset($_SESSION['coupon_discount']);
            jsonResponse(true, 'Đã xóa mã giảm giá');
            break;
            
        case 'apply':

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

function handleGetRequest($couponManager, $action) {
    switch ($action) {
        case 'info':

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

            $coupons = $couponManager->getAllCoupons(false);
            $now = date('Y-m-d H:i:s');
            
            $availableCoupons = array_filter($coupons, function($c) use ($now) {

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

function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
