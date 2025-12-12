<?php
/**
 * Debug Review Management API
 */

require_once 'bootstrap.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';

SessionManager::start();

// Simulate admin session
$_SESSION['ADMIN'] = 'admin';

echo "=== DEBUG REVIEW MANAGEMENT API ===\n\n";

// Test 1: Check database connection
echo "1. Testing database connection...\n";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "   ✅ Database connected\n\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Check product_reviews table
echo "2. Checking product_reviews table...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM product_reviews");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   ✅ Found {$count} reviews in database\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Check columns
echo "3. Checking required columns...\n";
try {
    $stmt = $conn->query("SHOW COLUMNS FROM product_reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'ma_san_pham', 'ma_nguoi_dung', 'rating', 'comment', 'status'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✅ Column '{$col}' exists\n";
        } else {
            echo "   ❌ Column '{$col}' MISSING\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Get sample reviews
echo "4. Getting sample reviews...\n";
try {
    $sql = "SELECT 
                pr.*,
                h.tenhanghoa as product_name
            FROM product_reviews pr
            LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
            LIMIT 5";
    
    $stmt = $conn->query($sql);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviews)) {
        echo "   ⚠️  No reviews found\n\n";
    } else {
        echo "   ✅ Found " . count($reviews) . " reviews:\n";
        foreach ($reviews as $review) {
            echo "      - ID: {$review['id']}, Product: {$review['product_name']}, Rating: {$review['rating']}\n";
            echo "        Status: " . ($review['status'] ?? 'NULL') . "\n";
            echo "        User: {$review['ma_nguoi_dung']}\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Test the actual API query
echo "5. Testing API query...\n";
try {
    $page = 1;
    $limit = 20;
    $offset = 0;
    $status = 'all';
    
    $where = [];
    $params = [];
    
    if ($status !== 'all') {
        $where[] = "pr.status = ?";
        $params[] = $status;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT 
                pr.*,
                h.tenhanghoa as product_name,
                h.hinhanh as product_image,
                pr.ma_nguoi_dung as user_name,
                (SELECT COUNT(*) FROM review_reports WHERE review_id = pr.id AND status = 'pending') as report_count
            FROM product_reviews pr
            LEFT JOIN hanghoa h ON pr.ma_san_pham = h.idhanghoa
            {$whereClause}
            ORDER BY pr.ngay_tao DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    echo "   SQL: " . str_replace("\n", " ", $sql) . "\n";
    echo "   Params: " . json_encode($params) . "\n";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   ✅ Query executed successfully\n";
    echo "   ✅ Found " . count($reviews) . " reviews\n\n";
    
    if (!empty($reviews)) {
        echo "   Sample data:\n";
        print_r($reviews[0]);
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Test 6: Test view
echo "6. Testing v_review_management_stats view...\n";
try {
    $stmt = $conn->query("SELECT * FROM v_review_management_stats");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "   ✅ View works\n";
        echo "   Stats: " . json_encode($stats) . "\n\n";
    } else {
        echo "   ⚠️  View returned no data\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 7: Simulate API call
echo "7. Simulating API call...\n";
try {
    $_GET['action'] = 'list';
    $_GET['page'] = 1;
    $_GET['status'] = 'all';
    
    ob_start();
    include 'lequocanh/api/review_management.php';
    $output = ob_get_clean();
    
    echo "   API Output:\n";
    echo "   " . substr($output, 0, 500) . "...\n\n";
    
    $json = json_decode($output, true);
    if ($json) {
        echo "   ✅ Valid JSON response\n";
        echo "   Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['data'])) {
            echo "   Reviews count: " . count($json['data']['reviews'] ?? []) . "\n";
        }
        if (isset($json['error'])) {
            echo "   ❌ Error: " . $json['error'] . "\n";
        }
    } else {
        echo "   ❌ Invalid JSON response\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETED ===\n";
