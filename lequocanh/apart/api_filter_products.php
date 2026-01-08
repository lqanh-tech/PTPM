<?php

header('Content-Type: application/json');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header_remove('X-Powered-By');

require_once __DIR__ . '/../administrator/elements_LQA/mod/hanghoaCls.php';

@ini_set('display_errors', '0');
@error_reporting(0);
if (ob_get_level()) ob_clean();

$hanghoa = new hanghoa();

$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000;
$colors = isset($_GET['colors']) ? explode(',', $_GET['colors']) : [];
$sizes = isset($_GET['sizes']) ? explode(',', $_GET['sizes']) : [];
$category = isset($_GET['reqView']) ? (int)$_GET['reqView'] : null;
$min_rating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;

$filters = [
    'min_price' => $min_price,
    'max_price' => $max_price,
    'colors' => $colors,
    'sizes' => $sizes,
    'category' => $category,
    'min_rating' => $min_rating
];

try {

    if (!empty($colors) || !empty($sizes) || $min_price > 0 || $max_price < 100000000 || $min_rating > 0) {
        $list_hanghoa = $hanghoa->filterProducts($filters);
    } elseif ($category) {
        $list_hanghoa = $hanghoa->HanghoaGetbyIdloaihang($category);
    } else {
        $list_hanghoa = $hanghoa->HanghoaGetAll();
    }

    echo json_encode([
        'success' => true,
        'count' => count($list_hanghoa),
        'products' => $list_hanghoa
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
