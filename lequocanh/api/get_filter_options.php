<?php

header('Content-Type: application/json');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';

$security = ApiSecurityMiddleware::getInstance();
$security->handle('get_filter_options');

try {
    $hanghoa = new hanghoa();
    
    $reqView = isset($_GET['reqView']) ? (int)$_GET['reqView'] : null;
    
    $options = $hanghoa->getFilterOptions($reqView);
    
    echo json_encode([
        'success' => true,
        'colors' => $options['colors'],
        'sizes' => $options['sizes'],
        'price_range' => $options['price_range']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tải tùy chọn lọc',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
