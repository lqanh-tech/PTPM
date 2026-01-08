<?php

error_log("Search suggestions request received: " . date('Y-m-d H:i:s'));

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

require_once './administrator/elements_LQA/mod/database.php';
require_once './administrator/elements_LQA/mod/hanghoaCls.php';

if (!isset($_GET['query']) || empty($_GET['query'])) {

    error_log("No query parameter provided");
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$searchQuery = trim($_GET['query']);
error_log("Search query: " . $searchQuery);

$searchQuery = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');

if (strlen($searchQuery) < 2) {
    error_log("Query too short: " . $searchQuery);
    echo json_encode([]);
    exit;
}

$results = [];

try {
    error_log("Attempting to search in products table");
    $results = searchProducts($searchQuery);
} catch (Exception $e) {
    error_log("Error searching products: " . $e->getMessage());
}

if (empty($results)) {
    error_log("No results from products table, trying hanghoa");
    try {
        $hanghoa = new hanghoa();
        $hanghoaResults = $hanghoa->searchHanghoa($searchQuery);

        if ($hanghoaResults) {
            error_log("Found " . count($hanghoaResults) . " results in hanghoa");
            foreach ($hanghoaResults as $item) {

                $imagePath = './administrator/elements_LQA/img_LQA/no-image.png';
                if (isset($item->hinhanh) && $item->hinhanh > 0) {
                    $imagePath = "./administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $item->hinhanh;
                }

                $hasDiscount = false;
                $displayPrice = 0;
                $originalPrice = 0;
                
                if (isset($item->giakhuyenmai) && $item->giakhuyenmai > 0 && $item->giakhuyenmai < $item->giathamkhao) {

                    $hasDiscount = true;
                    $displayPrice = $item->giakhuyenmai;
                    $originalPrice = $item->giathamkhao;
                } else {

                    $displayPrice = isset($item->giathamkhao) ? $item->giathamkhao : 0;
                }
                
                $price = $displayPrice > 0 ? number_format($displayPrice, 0, ',', '.') . '₫' : 'Liên hệ';

                $results[] = [
                    'id' => $item->idhanghoa,
                    'name' => $item->tenhanghoa,
                    'price' => $price,
                    'original_price' => $hasDiscount ? number_format($originalPrice, 0, ',', '.') . '₫' : null,
                    'has_discount' => $hasDiscount,
                    'image' => $imagePath,
                    'brand' => isset($item->thuonghieu) ? $item->thuonghieu : '',
                    'url' => 'index.php?reqHanghoa=' . $item->idhanghoa
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error searching hanghoa: " . $e->getMessage());
    }
}

header('Content-Type: application/json');
echo json_encode($results);

function searchProducts($searchQuery)
{

    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $results = [];

    try {

        $searchParam = '%' . $searchQuery . '%';

        $sql = "SELECT
                    p.id,
                    p.name,
                    p.price,
                    p.sale_price,
                    (SELECT image FROM product_images WHERE product_id = p.id LIMIT 1) as image,
                    b.name as brand_name
                FROM
                    products p
                LEFT JOIN
                    brands b ON p.brand_id = b.id
                WHERE
                    p.name LIKE ? OR
                    p.description LIKE ? OR
                    b.name LIKE ?
                LIMIT 8";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchParam, $searchParam, $searchParam]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $displayPrice = isset($row['sale_price']) && $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
            $image = !empty($row['image']) ? 'uploads/products/' . $row['image'] : 'uploads/products/default.jpg';

            $results[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => number_format($displayPrice, 0, ',', '.') . '₫',
                'image' => $image,
                'brand' => $row['brand_name'],
                'url' => 'product.php?id=' . $row['id']
            ];
        }
    } catch (PDOException $e) {
        error_log('Search products error: ' . $e->getMessage());
    }

    return $results;
}
