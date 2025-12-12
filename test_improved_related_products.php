<!DOCTYPE html>
<html>
<head>
    <title>Test Improved Related Products System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tier-section { border-left: 4px solid #007bff; padding-left: 15px; margin: 20px 0; }
        .tier-1 { border-color: #28a745; }
        .tier-2 { border-color: #007bff; }
        .tier-3 { border-color: #17a2b8; }
        .tier-4 { border-color: #ffc107; }
        .tier-5 { border-color: #dc3545; }
        .tier-6 { border-color: #6c757d; }
        .product-card { border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1><i class="fas fa-cogs me-2"></i>Test Improved Related Products System</h1>
    
    <?php
    require_once 'bootstrap.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
    require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

    $db = new Database();
    $hanghoa = new hanghoa($db->getConnection());

    // Get a test product (first available product)
    $sql = "SELECT * FROM hanghoa ORDER BY idhanghoa ASC LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $testProduct = $stmt->fetch(PDO::FETCH_OBJ);

    if ($testProduct) {
        echo "<div class='alert alert-info'>";
        echo "<h4>Testing with Product:</h4>";
        echo "<p><strong>ID:</strong> {$testProduct->idhanghoa}</p>";
        echo "<p><strong>Name:</strong> {$testProduct->tenhanghoa}</p>";
        echo "<p><strong>Category ID:</strong> {$testProduct->idloaihang}</p>";
        echo "<p><strong>Brand ID:</strong> {$testProduct->idThuongHieu}</p>";
        echo "<p><strong>Price:</strong> " . number_format($testProduct->giathamkhao) . "₫</p>";
        echo "</div>";

        // Test the improved system
        echo "<h3><i class='fas fa-layer-group me-2'></i>Related Products Results</h3>";
        
        $relatedProducts = $hanghoa->getRelatedProducts($testProduct->idhanghoa, 8);
        
        if (!empty($relatedProducts)) {
            echo "<div class='alert alert-success'>";
            echo "<i class='fas fa-check-circle me-2'></i>";
            echo "Found " . count($relatedProducts) . " related products using multi-tier system!";
            echo "</div>";
            
            // Group by recommendation type
            $groupedProducts = [];
            foreach ($relatedProducts as $product) {
                $type = $product->recommendation_type ?? 'unknown';
                if (!isset($groupedProducts[$type])) {
                    $groupedProducts[$type] = [];
                }
                $groupedProducts[$type][] = $product;
            }
            
            // Display by tiers
            $tierInfo = [
                'same_category_brand' => ['name' => 'Tier 1: Cùng thương hiệu & danh mục', 'class' => 'tier-1', 'icon' => 'fas fa-crown'],
                'same_brand' => ['name' => 'Tier 2: Cùng thương hiệu', 'class' => 'tier-2', 'icon' => 'fas fa-tag'],
                'same_category' => ['name' => 'Tier 3: Cùng danh mục', 'class' => 'tier-3', 'icon' => 'fas fa-th-large'],
                'similar_price' => ['name' => 'Tier 4: Tầm giá tương tự', 'class' => 'tier-4', 'icon' => 'fas fa-dollar-sign'],
                'bestseller' => ['name' => 'Tier 5: Sản phẩm bán chạy', 'class' => 'tier-5', 'icon' => 'fas fa-fire'],
                'newest' => ['name' => 'Tier 6: Sản phẩm mới', 'class' => 'tier-6', 'icon' => 'fas fa-star']
            ];
            
            foreach ($tierInfo as $type => $info) {
                if (isset($groupedProducts[$type])) {
                    echo "<div class='tier-section {$info['class']}'>";
                    echo "<h4><i class='{$info['icon']} me-2'></i>{$info['name']} (" . count($groupedProducts[$type]) . " sản phẩm)</h4>";
                    
                    foreach ($groupedProducts[$type] as $product) {
                        echo "<div class='product-card'>";
                        echo "<div class='row align-items-center'>";
                        echo "<div class='col-md-8'>";
                        echo "<h6 class='mb-1'>{$product->tenhanghoa}</h6>";
                        echo "<small class='text-muted'>ID: {$product->idhanghoa} | Category: {$product->idloaihang} | Brand: {$product->idThuongHieu}</small>";
                        echo "</div>";
                        echo "<div class='col-md-4 text-end'>";
                        echo "<span class='badge bg-primary'>" . number_format($product->giathamkhao) . "₫</span>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                }
            }
            
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<i class='fas fa-exclamation-triangle me-2'></i>";
            echo "No related products found. This might indicate an issue with the database or the algorithm.";
            echo "</div>";
            
            // Debug information
            echo "<h4>Debug Information:</h4>";
            
            // Check total products
            $sql = "SELECT COUNT(*) as total FROM hanghoa WHERE idhanghoa != ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idhanghoa]);
            $total = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>Total other products in database: {$total->total}</p>";
            
            // Check same category
            $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idloaihang = ? AND idhanghoa != ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idloaihang, $testProduct->idhanghoa]);
            $sameCategory = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>Products in same category: {$sameCategory->count}</p>";
            
            // Check same brand
            $sql = "SELECT COUNT(*) as count FROM hanghoa WHERE idThuongHieu = ? AND idhanghoa != ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$testProduct->idThuongHieu, $testProduct->idhanghoa]);
            $sameBrand = $stmt->fetch(PDO::FETCH_OBJ);
            echo "<p>Products from same brand: {$sameBrand->count}</p>";
        }
        
        // Test with different products
        echo "<hr><h3><i class='fas fa-flask me-2'></i>Test with Different Products</h3>";
        
        $sql = "SELECT * FROM hanghoa ORDER BY RAND() LIMIT 3";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
        $randomProducts = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        foreach ($randomProducts as $rp) {
            $related = $hanghoa->getRelatedProducts($rp->idhanghoa, 4);
            echo "<div class='card mb-3'>";
            echo "<div class='card-header'>";
            echo "<strong>{$rp->tenhanghoa}</strong> (ID: {$rp->idhanghoa})";
            echo "</div>";
            echo "<div class='card-body'>";
            if (!empty($related)) {
                echo "<span class='badge bg-success me-2'>" . count($related) . " related products</span>";
                $types = array_unique(array_column($related, 'recommendation_type'));
                foreach ($types as $type) {
                    $typeCount = count(array_filter($related, function($p) use ($type) { return $p->recommendation_type == $type; }));
                    echo "<span class='badge bg-secondary me-1'>{$type}: {$typeCount}</span>";
                }
            } else {
                echo "<span class='badge bg-warning'>No related products</span>";
            }
            echo "</div>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<i class='fas fa-exclamation-circle me-2'></i>";
        echo "No products found in database to test with.";
        echo "</div>";
    }
    ?>
    
    <div class="mt-4">
        <h3><i class='fas fa-info-circle me-2'></i>System Features</h3>
        <div class="row">
            <div class="col-md-6">
                <h5>Multi-Tier Fallback System:</h5>
                <ul>
                    <li><strong>Tier 1:</strong> Same category + same brand (highest priority)</li>
                    <li><strong>Tier 2:</strong> Same brand, different category</li>
                    <li><strong>Tier 3:</strong> Same category, different brand</li>
                    <li><strong>Tier 4:</strong> Similar price range (±50%)</li>
                    <li><strong>Tier 5:</strong> Best sellers (products with most reviews)</li>
                    <li><strong>Tier 6:</strong> Newest products (last resort)</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Smart Features:</h5>
                <ul>
                    <li>Dynamic titles based on recommendation type</li>
                    <li>Colored badges showing recommendation reason</li>
                    <li>Excludes discontinued products (trang_thai = 2)</li>
                    <li>Prioritizes products with images</li>
                    <li>No duplicate recommendations</li>
                    <li>Quick comparison for similar products</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>