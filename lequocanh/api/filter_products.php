<?php
/**
 * Product Filter API Endpoint
 * Returns filtered products based on price, color, size, and rating criteria
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';

try {
    $hanghoa = new hanghoa();
    
    // Get filter parameters
    $minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000;
    $colors = isset($_GET['colors']) ? explode(',', $_GET['colors']) : [];
    $sizes = isset($_GET['sizes']) ? explode(',', $_GET['sizes']) : [];
    $reqView = isset($_GET['reqView']) ? (int)$_GET['reqView'] : null;
    $minRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;
    
    // Build filter array
    $filters = [
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'colors' => array_filter($colors),
        'sizes' => array_filter($sizes),
        'category' => $reqView,
        'min_rating' => $minRating
    ];
    
    // Get filtered products
    $products = $hanghoa->filterProducts($filters);
    
    // Convert objects to arrays and format rating data for JSON encoding
    $productsArray = [];
    foreach ($products as $product) {
        $productData = (array)$product;
        
        // Format rating for display - check both avg_rating and average_rating
        if (isset($productData['average_rating'])) {
            $productData['average_rating'] = (float)$productData['average_rating'];
        } elseif (isset($productData['avg_rating'])) {
            $productData['average_rating'] = (float)$productData['avg_rating'];
        } else {
            $productData['average_rating'] = 0.0;
        }
        
        // Format review count
        if (isset($productData['review_count'])) {
            $productData['review_count'] = (int)$productData['review_count'];
        } else {
            $productData['review_count'] = 0;
        }
        
        $productsArray[] = $productData;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'products' => $productsArray,
        'total' => count($productsArray),
        'filters_applied' => $filters
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lọc sản phẩm',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
