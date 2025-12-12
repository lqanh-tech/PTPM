<!DOCTYPE html>
<html>
<head>
    <title>Debug Related Products</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .product-box { border: 1px solid #ccc; margin: 10px 0; padding: 10px; background: #f9f9f9; }
        .match { color: green; font-weight: bold; }
        .no-match { color: red; }
    </style>
</head>
<body>
<?php
require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

$db = new Database();
$hanghoa = new hanghoa($db->getConnection());

// Test with iPhone 13 Pro (assuming it exists)
echo "<h2>Debug Related Products System</h2>";

// First, let's find iPhone 13 Pro
$sql = "SELECT * FROM hanghoa WHERE tenhanghoa LIKE '%iPhone 13 Pro%' OR tenhanghoa LIKE '%iPhone%' LIMIT 5";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute();
$iphones = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "<h3>Available iPhone Products:</h3>";
foreach ($iphones as $iphone) {
    echo "<p>ID: {$iphone->idhanghoa} - Name: {$iphone->tenhanghoa} - Category: {$iphone->idloaihang} - Brand: {$iphone->idThuongHieu} - Price: " . number_format($iphone->giathamkhao) . "₫</p>";
}

if (!empty($iphones)) {
    $testProduct = $iphones[0]; // Use first iPhone found
    echo "<hr><h3>Testing Related Products for: {$testProduct->tenhanghoa}</h3>";
    
    // Get current product details
    $current = $hanghoa->HanghoaGetbyId($testProduct->idhanghoa);
    echo "<h4>Current Product Details:</h4>";
    echo "<p>ID: {$current->idhanghoa}</p>";
    echo "<p>Name: {$current->tenhanghoa}</p>";
    echo "<p>Category ID: {$current->idloaihang}</p>";
    echo "<p>Brand ID: {$current->idThuongHieu}</p>";
    echo "<p>Price: " . number_format($current->giathamkhao) . "₫</p>";
    
    // Calculate price range
    $priceMin = $current->giathamkhao * 0.7;
    $priceMax = $current->giathamkhao * 1.3;
    echo "<p>Price Range: " . number_format($priceMin) . "₫ - " . number_format($priceMax) . "₫</p>";
    
    // Test the query manually
    echo "<h4>Manual Query Test:</h4>";
    $sql = "SELECT h.*,
            CASE 
                WHEN h.hinhanh IS NOT NULL AND h.hinhanh != 0 AND h.hinhanh != '' 
                THEN 0 
                ELSE 1 
            END as image_priority,
            -- Calculate similarity score
            (CASE WHEN h.idloaihang = ? THEN 3 ELSE 0 END +
             CASE WHEN h.idThuongHieu = ? THEN 2 ELSE 0 END +
             CASE WHEN h.giathamkhao BETWEEN ? AND ? THEN 1 ELSE 0 END) as similarity_score
            FROM hanghoa h
            WHERE h.idhanghoa != ?
            ORDER BY similarity_score DESC, image_priority ASC, h.tenhanghoa ASC
            LIMIT 10";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([
        $current->idloaihang ?? 0,
        $current->idThuongHieu ?? 0,
        $priceMin,
        $priceMax,
        $testProduct->idhanghoa
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "<p>Found " . count($results) . " potential related products:</p>";
    
    foreach ($results as $result) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<p><strong>Name:</strong> {$result->tenhanghoa}</p>";
        echo "<p><strong>Category ID:</strong> {$result->idloaihang} " . ($result->idloaihang == $current->idloaihang ? "(MATCH)" : "") . "</p>";
        echo "<p><strong>Brand ID:</strong> {$result->idThuongHieu} " . ($result->idThuongHieu == $current->idThuongHieu ? "(MATCH)" : "") . "</p>";
        echo "<p><strong>Price:</strong> " . number_format($result->giathamkhao) . "₫ " . 
             ($result->giathamkhao >= $priceMin && $result->giathamkhao <= $priceMax ? "(IN RANGE)" : "") . "</p>";
        echo "<p><strong>Similarity Score:</strong> {$result->similarity_score}</p>";
        echo "<p><strong>Image Priority:</strong> {$result->image_priority}</p>";
        echo "</div>";
    }
    
    // Test the actual method
    echo "<hr><h4>Testing getRelatedProducts() Method:</h4>";
    $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 6);
    echo "<p>Method returned " . count($relatedProducts) . " products</p>";
    
    foreach ($relatedProducts as $rp) {
        echo "<p>- {$rp->tenhanghoa} (Price: " . number_format($rp->giathamkhao) . "₫)</p>";
    }
    
    // Check if there are any products in the same category
    echo "<hr><h4>Products in Same Category:</h4>";
    $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idloaihang = ? AND idhanghoa != ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$current->idloaihang, $testProduct->idhanghoa]);
    $categoryCount = $stmt->fetch(PDO::FETCH_OBJ);
    echo "<p>Products in same category: {$categoryCount->count}</p>";
    
    // Check if there are any products from the same brand
    echo "<h4>Products from Same Brand:</h4>";
    $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idThuongHieu = ? AND idhanghoa != ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$current->idThuongHieu, $testProduct->idhanghoa]);
    $brandCount = $stmt->fetch(PDO::FETCH_OBJ);
    echo "<p>Products from same brand: {$brandCount->count}</p>";
    
    // Check total products
    echo "<h4>Total Products in Database:</h4>";
    $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idhanghoa != ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$testProduct->idhanghoa]);
    $totalCount = $stmt->fetch(PDO::FETCH_OBJ);
    echo "<p>Total other products: {$totalCount->count}</p>";
}
?>
</body>
</html>