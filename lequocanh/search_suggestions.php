<?php
// Add error logging for debugging
error_log("Search suggestions request received: " . date('Y-m-d H:i:s'));

// Bật báo cáo lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Không cần file core/init.php, chỉ cần kết nối database
require_once './administrator/elements_LQA/mod/database.php';
require_once './administrator/elements_LQA/mod/hanghoaCls.php';

// Check if we received a search query
if (!isset($_GET['query']) || empty($_GET['query'])) {
    // Return empty JSON array if no query provided
    error_log("No query parameter provided");
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Get and sanitize the search query
$searchQuery = trim($_GET['query']);
error_log("Search query: " . $searchQuery);
// Sử dụng htmlspecialchars thay vì FILTER_SANITIZE_STRING đã bị deprecated
$searchQuery = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');

// Return empty array if query is too short
if (strlen($searchQuery) < 2) {
    error_log("Query too short: " . $searchQuery);
    echo json_encode([]);
    exit;
}

$results = [];

// Try searching in products table first
try {
    error_log("Attempting to search in products table");
    $results = searchProducts($searchQuery);
} catch (Exception $e) {
    error_log("Error searching products: " . $e->getMessage());
}

// If no results from products, try the hanghoa table
if (empty($results)) {
    error_log("No results from products table, trying hanghoa");
    try {
        $hanghoa = new hanghoa();
        $hanghoaResults = $hanghoa->searchHanghoa($searchQuery);

        if ($hanghoaResults) {
            error_log("Found " . count($hanghoaResults) . " results in hanghoa");
            foreach ($hanghoaResults as $item) {
                // Get image information if available
                $imagePath = './administrator/elements_LQA/img_LQA/no-image.png'; // Default fallback image
                if (isset($item->hinhanh) && $item->hinhanh > 0) {
                    $imagePath = "./administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $item->hinhanh;
                }

                // Format price - ưu tiên giá khuyến mãi nếu có
                $hasDiscount = false;
                $displayPrice = 0;
                $originalPrice = 0;
                
                if (isset($item->giakhuyenmai) && $item->giakhuyenmai > 0 && $item->giakhuyenmai < $item->giathamkhao) {
                    // Có khuyến mãi
                    $hasDiscount = true;
                    $displayPrice = $item->giakhuyenmai;
                    $originalPrice = $item->giathamkhao;
                } else {
                    // Không khuyến mãi
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

// Return JSON response
header('Content-Type: application/json');
echo json_encode($results);

// Function to search using the 'products' table
function searchProducts($searchQuery)
{
    // Sử dụng Database class thay vì biến $pdo global
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $results = [];

    try {
        // Prepare search query with wildcards for partial matching
        $searchParam = '%' . $searchQuery . '%';

        // SQL query to search products by name, description, or brand
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

        // Format results for the frontend
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
