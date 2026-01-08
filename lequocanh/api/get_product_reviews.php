<?php

header('Content-Type: application/json');

require_once __DIR__ . '/middleware/ApiSecurityMiddleware.php';
require_once __DIR__ . '/../administrator/elements_LQA/mod/ProductReviewCls.php';

$security = ApiSecurityMiddleware::getInstance();
$security->handle('get_product_reviews');

try {
    $idhanghoa = isset($_GET['idhanghoa']) ? (int)$_GET['idhanghoa'] : 0;
    $rating = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if (!$idhanghoa) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID'
        ]);
        exit;
    }

    $reviewCls = new ProductReview();

    $reviews = $reviewCls->getProductReviews($idhanghoa, $limit, $offset, $rating);

    $stats = $reviewCls->getProductRatingStats($idhanghoa);

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'stats' => $stats,
        'total' => count($reviews)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Get reviews error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Could not load reviews'
    ]);
}
