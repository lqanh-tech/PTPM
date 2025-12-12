<?php
/**
 * Calculate Shipping Fee API Endpoint
 * 
 * AJAX endpoint to calculate shipping fee and delivery time
 * Supports both GHN API and fallback pricing
 * 
 * @method POST
 * @return JSON
 */

header('Content-Type: application/json');

// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/ShippingCls.php';

SessionManager::start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    $errors = [];

    // For GHN: need district_id and ward_code
    // For fallback: need province_name or lat/lng
    if (empty($data['to_district_id']) && empty($data['to_province_name'])) {
        $errors[] = 'Missing destination information (district_id or province_name required)';
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed: ' . implode(', ', $errors)
        ]);
        exit;
    }

    // Initialize Shipping class
    $shipping = new Shipping();

    // Prepare parameters
    $params = [
        'to_district_id' => isset($data['to_district_id']) ? intval($data['to_district_id']) : null,
        'to_ward_code' => $data['to_ward_code'] ?? null,
        'to_province_id' => isset($data['to_province_id']) ? intval($data['to_province_id']) : null,
        'to_province_name' => $data['to_province_name'] ?? null,
        'latitude' => isset($data['latitude']) ? floatval($data['latitude']) : null,
        'longitude' => isset($data['longitude']) ? floatval($data['longitude']) : null,
        'weight' => isset($data['weight']) ? intval($data['weight']) : 1000,
        'insurance_value' => isset($data['insurance_value']) ? floatval($data['insurance_value']) : 0,
    ];

    // Get insurance value from session if not provided
    if ($params['insurance_value'] == 0 && isset($_SESSION['total_amount'])) {
        $params['insurance_value'] = floatval($_SESSION['total_amount']);
    }

    // Calculate shipping (fee + delivery time)
    $result = $shipping->calculateShippingComplete($params);

    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to calculate shipping fee'
        ]);
        exit;
    }

    // Update session with shipping info
    $_SESSION['shipping_fee'] = $result['shipping_fee'];
    $_SESSION['shipping_method'] = $result['method'];
    $_SESSION['shipping_method_name'] = $result['method_name'];
    $_SESSION['estimated_delivery'] = $result['estimated_delivery'];
    $_SESSION['estimated_days'] = $result['estimated_days'];
    $_SESSION['shipping_distance_km'] = $result['distance_km'];

    // If we have district/ward info, save to session
    if (!empty($params['to_district_id'])) {
        $_SESSION['to_district_id'] = $params['to_district_id'];
    }
    if (!empty($params['to_ward_code'])) {
        $_SESSION['to_ward_code'] = $params['to_ward_code'];
    }
    if (!empty($params['to_province_id'])) {
        $_SESSION['to_province_id'] = $params['to_province_id'];
    }

    // Recalculate total amount
    $subtotal = $_SESSION['subtotal'] ?? 0;
    $vatAmount = $_SESSION['vat_amount'] ?? 0;
    $shippingFee = $result['shipping_fee'];
    
    // QUAN TRỌNG: Phải trừ coupon discount!
    $couponDiscount = $_SESSION['coupon_discount'] ?? 0;
    
    // Công thức đúng: Subtotal + VAT + Shipping - Coupon
    $totalAmount = $subtotal + $vatAmount + $shippingFee - $couponDiscount;
    $_SESSION['total_amount'] = $totalAmount;
    
    // Log để debug
    error_log("=== CALCULATE SHIPPING API ===");
    error_log("Subtotal: $subtotal, VAT: $vatAmount, Shipping: $shippingFee, Coupon: $couponDiscount");
    error_log("Total: $totalAmount");
    error_log("==============================");

    // Prepare response
    $response = [
        'success' => true,
        'shipping_fee' => $result['shipping_fee'],
        'shipping_fee_formatted' => number_format($result['shipping_fee'], 0, ',', '.') . ' ₫',
        'method' => $result['method'],
        'method_name' => $result['method_name'],
        'estimated_days' => $result['estimated_days'],
        'estimated_delivery' => $result['estimated_delivery'],
        'estimated_delivery_formatted' => !empty($result['estimated_delivery']) 
            ? date('d/m/Y', strtotime($result['estimated_delivery'])) 
            : null,
        'distance_km' => $result['distance_km'],
        'distance_formatted' => $result['distance_km'] 
            ? number_format($result['distance_km'], 2) . ' km' 
            : null,
        'total_amount' => $totalAmount,
        'total_amount_formatted' => number_format($totalAmount, 0, ',', '.') . ' ₫',
        'message' => $result['message'],
        
        // Additional info
        'breakdown' => [
            'subtotal' => $subtotal,
            'subtotal_formatted' => number_format($subtotal, 0, ',', '.') . ' ₫',
            'vat' => $vatAmount,
            'vat_formatted' => number_format($vatAmount, 0, ',', '.') . ' ₫',
            'shipping' => $shippingFee,
            'shipping_formatted' => number_format($shippingFee, 0, ',', '.') . ' ₫',
            'coupon_discount' => $couponDiscount,
            'coupon_discount_formatted' => number_format($couponDiscount, 0, ',', '.') . ' ₫',
            'total' => $totalAmount,
            'total_formatted' => number_format($totalAmount, 0, ',', '.') . ' ₫',
        ]
    ];

    // If GHN, include service fee breakdown
    if ($result['method'] === 'GHN') {
        $response['service_fee'] = $result['service_fee'] ?? 0;
        $response['insurance_fee'] = $result['insurance_fee'] ?? 0;
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Calculate Shipping API Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
