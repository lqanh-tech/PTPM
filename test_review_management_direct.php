<?php
/**
 * Direct Test for Review Management
 * Access this page after logging into admin panel
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Review Management</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Test Review Management API</h1>
    
    <h2>1. Session Status:</h2>
    <?php if (isset($_SESSION['ADMIN'])): ?>
        <p class="success">✓ Admin session active: <?= htmlspecialchars($_SESSION['ADMIN']) ?></p>
    <?php else: ?>
        <p class="error">✗ Admin session NOT active</p>
        <p><a href="lequocanh/administrator/index.php">Login to Admin Panel first</a></p>
    <?php endif; ?>
    
    <h2>2. API Test:</h2>
    <div id="apiResult">Loading...</div>
    
    <h2>3. Direct Database Test:</h2>
    <?php
    try {
        require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM product_reviews");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p class='success'>✓ Database connected. Total reviews: $count</p>";
        
        // Get sample reviews
        $stmt = $conn->query("SELECT pr.*, h.tenhanghoa as product_name 
            FROM product_reviews pr 
            LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa 
            ORDER BY pr.ngay_tao DESC LIMIT 3");
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Reviews:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($reviews, true)) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <script>
    async function testAPI() {
        const resultDiv = document.getElementById('apiResult');
        try {
            const response = await fetch('lequocanh/api/review_management.php?action=list&page=1&status=all', {
                credentials: 'include'
            });
            const text = await response.text();
            
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    resultDiv.innerHTML = `
                        <p class="success">✓ API working!</p>
                        <p>Total reviews: ${result.data.stats.total_reviews}</p>
                        <p>Reviews returned: ${result.data.reviews.length}</p>
                        <pre>${JSON.stringify(result, null, 2)}</pre>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="error">✗ API Error: ${result.error}</p>`;
                }
            } catch (e) {
                resultDiv.innerHTML = `<p class="error">✗ JSON Parse Error</p><pre>${text}</pre>`;
            }
        } catch (error) {
            resultDiv.innerHTML = `<p class="error">✗ Fetch Error: ${error.message}</p>`;
        }
    }
    
    testAPI();
    </script>
</body>
</html>
