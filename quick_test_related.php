<?php
require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/hanghoaCls.php';

echo "Testing getRelatedProducts method...\n\n";

$hanghoa = new hanghoa($connection);

// Test with product ID 86
$productId = 86;
$product = $hanghoa->HanghoaGetbyId($productId);

if ($product) {
    echo "Product: {$product->tenhanghoa}\n";
    echo "Brand ID: {$product->idThuongHieu}\n";
    echo "Price: " . number_format($product->giathamkhao) . "₫\n\n";
    
    $related = $hanghoa->getRelatedProducts($productId, 4);
    
    echo "Related products found: " . count($related) . "\n\n";
    
    foreach ($related as $r) {
        echo "  - {$r->tenhanghoa} (ID: {$r->idhanghoa})\n";
        echo "    Brand: {$r->idThuongHieu}, Price: " . number_format($r->giathamkhao) . "₫\n";
    }
    
    echo "\n✅ Test completed successfully!\n";
} else {
    echo "❌ Product not found\n";
}
