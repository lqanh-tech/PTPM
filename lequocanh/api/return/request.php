<?php
declare(strict_types=1);

// api/return/request.php
require_once __DIR__ . '/../../app/autoload.php';

use App\Services\ReturnAutomationService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check authentication
if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Validate required fields
$required = ['order_id', 'reason'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

try {
    $service = ReturnAutomationService::fromConfig();
    
    $request = [
        'order_id' => $data['order_id'],
        'user_id' => $_SESSION['USER'],
        'reason' => $data['reason'],
        'order_status' => $data['order_status'] ?? 'completed',
        'order_date' => $data['order_date'] ?? date('Y-m-d'),
        'order_total' => $data['order_total'] ?? 0,
        'item_count' => $data['item_count'] ?? 1,
        'payment_method' => $data['payment_method'] ?? 'bank_transfer',
        'customer_name' => $data['customer_name'] ?? '',
        'customer_phone' => $data['customer_phone'] ?? '',
        'address' => $data['address'] ?? '',
        'email' => $data['email'] ?? '',
        'preferred_method' => $data['preferred_method'] ?? null,
    ];
    
    $result = $service->processReturn($request);
    
    echo json_encode($result);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    error_log('Return API error: ' . $e->getMessage());
}