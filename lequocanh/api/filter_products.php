<?php

header('Content-Type: application/json');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';

$security = ApiSecurityMiddleware::getInstance();
$security->handle('filter_products');

try {
    $hanghoa = new hanghoa();
    
    $minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000;
    $colors = isset($_GET['colors']) ? explode(',', $_GET['colors']) : [];
    $sizes = isset($_GET['sizes']) ? explode(',', $_GET['sizes']) : [];
    $reqView = isset($_GET['reqView']) ? (int)$_GET['reqView'] : null;
    $minRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;
    
    $filters = [
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'colors' => array_filter($colors),
        'sizes' => array_filter($sizes),
        'category' => $reqView,
        'min_rating' => $minRating
    ];
    
    $products = $hanghoa->filterProducts($filters);
    $debugMode = isset($_GET['debug']);
    
    $productsArray = [];
    foreach ($products as $product) {
        $productData = (array)$product;
        
        if (isset($productData['average_rating'])) {
            $productData['average_rating'] = (float)$productData['average_rating'];
        } elseif (isset($productData['avg_rating'])) {
            $productData['average_rating'] = (float)$productData['avg_rating'];
        } else {
            $productData['average_rating'] = 0.0;
        }
        
        if (isset($productData['review_count'])) {
            $productData['review_count'] = (int)$productData['review_count'];
        } else {
            $productData['review_count'] = 0;
        }
        
        $productsArray[] = $productData;
    }
    
    $response = [
        'success' => true,
        'products' => $productsArray,
        'total' => count($productsArray),
        'filters_applied' => $filters
    ];

    if ($debugMode) {
        $response['debug'] = $hanghoa->getLastFilterDebug();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lọc sản phẩm',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}