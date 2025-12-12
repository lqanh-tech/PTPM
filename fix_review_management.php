<?php
/**
 * Fix Review Management - Add missing columns and create views
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "<h1>Fix Review Management System</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p style='color:green'>✓ Database connected</p>";
    
    // Check if product_reviews table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'product_reviews'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>✗ Table 'product_reviews' does not exist!</p>";
        echo "<p>Please run setup_product_reviews_system.sql first</p>";
        exit;
    }
    echo "<p style='color:green'>✓ Table 'product_reviews' exists</p>";
    
    // Check and add status column
    $stmt = $conn->query("SHOW COLUMNS FROM product_reviews LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange'>⚠ Column 'status' not found, adding...</p>";
        $conn->exec("ALTER TABLE product_reviews ADD COLUMN status ENUM('visible', 'hidden', 'deleted') DEFAULT 'visible' COMMENT 'Trạng thái hiển thị'");
        echo "<p style='color:green'>✓ Column 'status' added</p>";
    } else {
        echo "<p style='color:green'>✓ Column 'status' exists</p>";
    }
    
    // Check and add admin_note column
    $stmt = $conn->query("SHOW COLUMNS FROM product_reviews LIKE 'admin_note'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange'>⚠ Column 'admin_note' not found, adding...</p>";
        $conn->exec("ALTER TABLE product_reviews ADD COLUMN admin_note TEXT NULL COMMENT 'Ghi chú của admin'");
        echo "<p style='color:green'>✓ Column 'admin_note' added</p>";
    } else {
        echo "<p style='color:green'>✓ Column 'admin_note' exists</p>";
    }
    
    // Check and add hidden_at column
    $stmt = $conn->query("SHOW COLUMNS FROM product_reviews LIKE 'hidden_at'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange'>⚠ Column 'hidden_at' not found, adding...</p>";
        $conn->exec("ALTER TABLE product_reviews ADD COLUMN hidden_at DATETIME NULL COMMENT 'Thời gian ẩn'");
        echo "<p style='color:green'>✓ Column 'hidden_at' added</p>";
    } else {
        echo "<p style='color:green'>✓ Column 'hidden_at' exists</p>";
    }
    
    // Check and add hidden_by column
    $stmt = $conn->query("SHOW COLUMNS FROM product_reviews LIKE 'hidden_by'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange'>⚠ Column 'hidden_by' not found, adding...</p>";
        $conn->exec("ALTER TABLE product_reviews ADD COLUMN hidden_by VARCHAR(50) NULL COMMENT 'Admin ẩn'");
        echo "<p style='color:green'>✓ Column 'hidden_by' added</p>";
    } else {
        echo "<p style='color:green'>✓ Column 'hidden_by' exists</p>";
    }
    
    // Update existing reviews to have 'visible' status if null
    $conn->exec("UPDATE product_reviews SET status = 'visible' WHERE status IS NULL");
    echo "<p style='color:green'>✓ Updated existing reviews with default status</p>";
    
    // Test query
    echo "<h2>Test Query:</h2>";
    $stmt = $conn->query("SELECT COUNT(*) as total, 
        SUM(CASE WHEN status = 'visible' OR status IS NULL THEN 1 ELSE 0 END) as visible,
        SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden,
        SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted
        FROM product_reviews");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
    // Get sample reviews
    echo "<h2>Sample Reviews:</h2>";
    $stmt = $conn->query("SELECT pr.*, h.tenhanghoa as product_name 
        FROM product_reviews pr 
        LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa 
        ORDER BY pr.ngay_tao DESC LIMIT 5");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($reviews, true) . "</pre>";
    
    echo "<h2 style='color:green'>✓ Fix completed successfully!</h2>";
    echo "<p>Now try accessing the Review Management page again.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
