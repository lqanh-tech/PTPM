<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $shippingFee = isset($input['shipping_fee']) ? floatval($input['shipping_fee']) : 0;
    $distance = isset($input['distance']) ? floatval($input['distance']) : 0;
    $shippingAddress = isset($input['shipping_address']) ? trim($input['shipping_address']) : '';

    $_SESSION['shipping_fee'] = $shippingFee;
    $_SESSION['shipping_distance'] = $distance;
    
    if (!empty($shippingAddress)) {
        $_SESSION['shipping_address'] = $shippingAddress;
    }

    $subtotal = 0;
    if (isset($_SESSION['order_details'])) {
        foreach ($_SESSION['order_details'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
    } else {

        $subtotal = 0;
    }

    $vatAmount = isset($_SESSION['vat_amount']) ? $_SESSION['vat_amount'] : 0;
    
    $couponDiscount = isset($_SESSION['coupon_discount']) ? floatval($_SESSION['coupon_discount']) : 0;
    
    $newTotal = $subtotal + $vatAmount + $shippingFee - $couponDiscount;
    
    $_SESSION['total_amount'] = $newTotal;
    
    error_log("=== UPDATE SHIPPING SESSION ===");
    error_log("Subtotal: $subtotal");
    error_log("VAT: $vatAmount");
    error_log("Shipping: $shippingFee");
    error_log("Coupon Discount: $couponDiscount");
    error_log("New Total: $newTotal");
    error_log("==============================");

    echo json_encode([
        'success' => true,
        'new_total' => $newTotal,
        'new_total_formatted' => number_format($newTotal, 0, ',', '.') . ' ₫',
        'shipping_fee_formatted' => number_format($shippingFee, 0, ',', '.') . ' ₫',
        'coupon_discount' => $couponDiscount,
        'coupon_discount_formatted' => number_format($couponDiscount, 0, ',', '.') . ' ₫'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
