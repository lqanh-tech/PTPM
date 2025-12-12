<?php
/**
 * Fix Review Visibility - Update view to only show visible reviews
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<h1>Fix Review Visibility</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p style='color:green'>✓ Database connected</p>";
    
    // Update the view to only count visible reviews
    $viewSql = "CREATE OR REPLACE VIEW `v_product_review_stats` AS
    SELECT 
        pr.ma_san_pham,
        COUNT(*) as total_reviews,
        AVG(pr.rating) as average_rating,
        SUM(CASE WHEN pr.rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN pr.rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN pr.rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN pr.rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN pr.rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM product_reviews pr
    WHERE pr.is_approved = 1 
      AND (pr.status = 'visible' OR pr.status IS NULL)
    GROUP BY pr.ma_san_pham";
    
    $conn->exec($viewSql);
    echo "<p style='color:green'>✓ View v_product_review_stats updated to only count visible reviews</p>";
    
    // Test: Check current review status
    echo "<h2>Current Review Status:</h2>";
    $stmt = $conn->query("SELECT id, ma_san_pham, ma_nguoi_dung, rating, status, 
        SUBSTRING(comment, 1, 50) as comment_preview 
        FROM product_reviews ORDER BY ngay_tao DESC");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>User</th><th>Rating</th><th>Status</th><th>Comment</th></tr>";
    foreach ($reviews as $review) {
        $statusColor = $review['status'] === 'hidden' ? 'orange' : ($review['status'] === 'deleted' ? 'red' : 'green');
        echo "<tr>";
        echo "<td>{$review['id']}</td>";
        echo "<td>{$review['ma_san_pham']}</td>";
        echo "<td>{$review['ma_nguoi_dung']}</td>";
        echo "<td>{$review['rating']}</td>";
        echo "<td style='color:{$statusColor}'>{$review['status']}</td>";
        echo "<td>{$review['comment_preview']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test: Check what will be shown on frontend
    echo "<h2>Reviews that will be shown on frontend (visible only):</h2>";
    $stmt = $conn->query("SELECT id, ma_san_pham, ma_nguoi_dung, rating, status 
        FROM product_reviews 
        WHERE is_approved = 1 AND (status = 'visible' OR status IS NULL)
        ORDER BY ngay_tao DESC");
    $visibleReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total visible reviews: " . count($visibleReviews) . "</p>";
    echo "<pre>" . print_r($visibleReviews, true) . "</pre>";
    
    echo "<h2 style='color:green'>✓ Fix completed!</h2>";
    echo "<p>Now hidden reviews will NOT appear on the frontend product pages.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
