<?php
/**
 * Get Available Filter Options API
 * Returns available colors and sizes for filtering
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';

try {
    $hanghoa = new hanghoa();
    
    // Get category if specified
    $reqView = isset($_GET['reqView']) ? (int)$_GET['reqView'] : null;
    
    // Get available filter options
    $options = $hanghoa->getFilterOptions($reqView);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'colors' => $options['colors'],
        'sizes' => $options['sizes'],
        'price_range' => $options['price_range']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tải tùy chọn lọc',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
