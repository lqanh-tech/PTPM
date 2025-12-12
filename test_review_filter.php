<?php
/**
 * Test Review Filter - Verify hidden reviews are not shown
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<h1>Test Review Filter</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all reviews
    echo "<h2>All Reviews in Database:</h2>";
    $stmt = $conn->query("SELECT id, ma_san_pham, rating, status, SUBSTRING(comment, 1, 30) as comment FROM product_reviews");
    $allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($allReviews, true) . "</pre>";
    
    // Test API query (same as product_reviews.php)
    echo "<h2>Reviews that will be shown on frontend (API query):</h2>";
    $productId = 143; // iPhone 13 Pro - has hidden review
    $stmt = $conn->prepare("SELECT pr.*, pr.ma_nguoi_dung as user_name
        FROM product_reviews pr
        WHERE pr.ma_san_pham = ? 
        AND pr.is_approved = 1
        AND (pr.status = 'visible' OR pr.status IS NULL)
        ORDER BY pr.ngay_tao DESC");
    $stmt->execute([$productId]);
    $visibleReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Product ID: $productId (iPhone 13 Pro)</p>";
    echo "<p>Visible reviews count: " . count($visibleReviews) . "</p>";
    
    if (count($visibleReviews) == 0) {
        echo "<p style='color:green'>✓ CORRECT! Hidden review is NOT shown for this product.</p>";
    } else {
        echo "<p style='color:red'>✗ ERROR! Reviews are still showing:</p>";
        echo "<pre>" . print_r($visibleReviews, true) . "</pre>";
    }
    
    // Test for product with visible review
    echo "<h2>Test Product with Visible Review:</h2>";
    $productId2 = 78; // ASUS ROG Phone 6D - has visible review
    $stmt = $conn->prepare("SELECT pr.*, pr.ma_nguoi_dung as user_name
        FROM product_reviews pr
        WHERE pr.ma_san_pham = ? 
        AND pr.is_approved = 1
        AND (pr.status = 'visible' OR pr.status IS NULL)
        ORDER BY pr.ngay_tao DESC");
    $stmt->execute([$productId2]);
    $visibleReviews2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Product ID: $productId2 (ASUS ROG Phone 6D)</p>";
    echo "<p>Visible reviews count: " . count($visibleReviews2) . "</p>";
    
    if (count($visibleReviews2) > 0) {
        echo "<p style='color:green'>✓ CORRECT! Visible review IS shown for this product.</p>";
    } else {
        echo "<p style='color:red'>✗ ERROR! Visible review is not showing.</p>";
    }
    
    echo "<h2 style='color:green'>✓ Test completed!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
