<?php
/**
 * Test Review Management and Support System
 */

require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

echo "=== TESTING REVIEW MANAGEMENT & SUPPORT SYSTEM ===\n\n";

$db = Database::getInstance();
$conn = $db->getConnection();

// Test 1: Check database views
echo "1. CHECKING DATABASE VIEWS\n";
$requiredViews = [
    'v_product_review_stats',
    'v_review_management_stats',
    'v_review_reports_list',
    'v_support_tickets_list'
];

$stmt = $conn->query("SHOW TABLES LIKE 'v_%'");
$existingViews = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($requiredViews as $view) {
    $exists = in_array($view, $existingViews);
    echo "   " . ($exists ? "✓" : "✗") . " $view\n";
}

// Test 2: Check product reviews
echo "\n2. CHECKING PRODUCT REVIEWS\n";
$stmt = $conn->query("SELECT COUNT(*) as total FROM product_reviews");
$reviewCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   Total reviews: $reviewCount\n";

if ($reviewCount > 0) {
    $stmt = $conn->query("SELECT * FROM product_reviews LIMIT 2");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reviews as $review) {
        echo "   - Review #{$review['id']}: {$review['rating']} stars, Status: {$review['status']}\n";
    }
}

// Test 3: Check review management stats view
echo "\n3. CHECKING REVIEW MANAGEMENT STATS\n";
try {
    $stmt = $conn->query("SELECT * FROM v_review_management_stats");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats) {
        echo "   Total: {$stats['total_reviews']}\n";
        echo "   Visible: {$stats['visible_reviews']}\n";
        echo "   Hidden: {$stats['hidden_reviews']}\n";
        echo "   Deleted: {$stats['deleted_reviews']}\n";
    } else {
        echo "   ✗ No stats available\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Check support tickets
echo "\n4. CHECKING SUPPORT TICKETS\n";
$stmt = $conn->query("SELECT COUNT(*) as total FROM support_tickets");
$ticketCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   Total tickets: $ticketCount\n";

if ($ticketCount > 0) {
    $stmt = $conn->query("SELECT * FROM support_tickets LIMIT 2");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tickets as $ticket) {
        echo "   - Ticket #{$ticket['ticket_number']}: {$ticket['subject']}, Status: {$ticket['status']}\n";
    }
}

// Test 5: Check support messages
echo "\n5. CHECKING SUPPORT MESSAGES\n";
$stmt = $conn->query("SELECT COUNT(*) as total FROM support_messages");
$messageCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   Total messages: $messageCount\n";

// Test 6: Test API endpoints (without authentication)
echo "\n6. TESTING API ENDPOINTS\n";

$endpoints = [
    'Review Management List' => 'http://localhost/lequocanh/api/review_management.php?action=list',
    'Support Tickets Admin List' => 'http://localhost/lequocanh/api/support_tickets.php?action=admin_list',
];

foreach ($endpoints as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Extract response body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    
    echo "   $name:\n";
    echo "      HTTP Code: $httpCode\n";
    
    if ($httpCode === 403) {
        echo "      ✓ Correctly requires authentication\n";
    } else if ($httpCode === 200) {
        $json = json_decode($body, true);
        if ($json && isset($json['success'])) {
            echo "      ✓ Returns valid JSON\n";
        } else {
            echo "      ✗ Invalid JSON response\n";
        }
    } else {
        echo "      ✗ Unexpected HTTP code\n";
    }
}

// Test 7: Check routing in center.php
echo "\n7. CHECKING ADMIN ROUTING\n";
$centerFile = 'lequocanh/administrator/elements_LQA/center.php';
if (file_exists($centerFile)) {
    $content = file_get_contents($centerFile);
    $routes = ['review_management', 'support_tickets'];
    foreach ($routes as $route) {
        $exists = strpos($content, "'$route'") !== false || strpos($content, "\"$route\"") !== false;
        echo "   " . ($exists ? "✓" : "✗") . " Route: $route\n";
    }
} else {
    echo "   ✗ center.php not found\n";
}

// Test 8: Check support button in index.php
echo "\n8. CHECKING SUPPORT BUTTON\n";
$indexFile = 'lequocanh/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    $hasButton = strpos($content, 'req=support') !== false || strpos($content, 'customer/support.php') !== false;
    $hasAnimation = strpos($content, 'pulse') !== false || strpos($content, '@keyframes') !== false;
    echo "   " . ($hasButton ? "✓" : "✗") . " Support button exists\n";
    echo "   " . ($hasAnimation ? "✓" : "✗") . " Animation CSS exists\n";
} else {
    echo "   ✗ index.php not found\n";
}

// Test 9: Check SQL LIMIT/OFFSET fix
echo "\n9. CHECKING SQL LIMIT/OFFSET FIX\n";
$apiFiles = [
    'lequocanh/api/review_management.php',
    'lequocanh/api/support_tickets.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasIntval = strpos($content, 'intval($limit)') !== false || strpos($content, 'intval($offset)') !== false;
        $hasBindLimit = preg_match('/LIMIT\s+\?/', $content);
        
        echo "   " . basename($file) . ":\n";
        echo "      " . ($hasIntval ? "✓" : "✗") . " Uses intval() for LIMIT/OFFSET\n";
        echo "      " . (!$hasBindLimit ? "✓" : "✗") . " No bind parameters in LIMIT clause\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nNEXT STEPS:\n";
echo "1. Login as admin at: http://localhost:20080/lequocanh/administrator/\n";
echo "2. Check 'Quản lý bình luận' in left menu\n";
echo "3. Check 'Hỗ trợ khách hàng' in left menu\n";
echo "4. Login as user and check 'Hỗ trợ' button in header\n";
echo "5. Clear browser cache (Ctrl+Shift+Delete) if pages show blank\n";
